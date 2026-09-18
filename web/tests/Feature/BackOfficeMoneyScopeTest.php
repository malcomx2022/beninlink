<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\Backend\Account;
use App\Models\Backend\Expense;
use App\Models\Backend\FundTransfer;
use App\Models\Backend\HubPayment;
use App\Models\Backend\Hub;
use App\Models\Backend\Income;
use App\Models\Backend\Merchant;
use App\Models\Backend\Payment;
use App\Models\CashReceivedFromDeliveryman;
use App\Models\User;
use App\Repositories\Account\AccountInterface;
use App\Repositories\CashReceivedFromDeliveryman\ReceivedInterface;
use App\Repositories\Expense\ExpenseInterface;
use App\Repositories\FundTransfer\FundTransferInterface;
use App\Repositories\HubPaymentRequest\HubPaymentRequestInterface;
use App\Repositories\Income\IncomeInterface;
use App\Repositories\MerchantManage\Payment\PaymentInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S30 — l'argent du back-office, cinquième passe sur l'arriéré du filet.
 *
 * Sept dépôts touchant à des comptes bancaires lisaient **nu**, et pour six
 * d'entre eux c'était avant un mouvement d'argent :
 *
 * | Dépôt | Ce que l'écriture faisait sur la ligne d'une AUTRE société |
 * |---|---|
 * | `Income::update` | touche son compte bancaire **et** le relevé de son marchand |
 * | `Expense::update` | **rend le solde** au compte rattaché à la dépense lue |
 * | `FundTransfer::update` | rejoue un **virement** entre ses deux comptes |
 * | `MerchantManage\Payment::update` | réécrit sa demande de versement, et la **réaffecte** à un autre marchand |
 * | `MerchantManage\Payment::cancelReject` | remet son versement rejeté **en attente de paiement** |
 * | `Account::update` | réécrit son compte bancaire (titulaire, banque, numéro) |
 * | `HubPaymentRequest::update` | réécrit sa demande et la **rattache** à l'entrepôt de l'agent connecté |
 *
 * Deux dissymétries connues, dans les deux sens : `Expense` et `Account` lisaient
 * déjà `companywise()` et écrivaient nu ; `CashReceivedFromDeliveryman` écrivait
 * scopé et lisait nu.
 *
 * ⚠️ Et **troisième occurrence de l'angle mort du filet** :
 * `MerchantmanagePaymentController::processed()` — le décaissement d'un versement
 * marchand — lit son identifiant dans le **corps** de la requête. Comme
 * `HubPayment::processed()` et `SalaryController::update()` avant lui,
 * `WebIsolationCoverageTest` ne pouvait pas le voir.
 */
class BackOfficeMoneyScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const AUTRE = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->actingAs($this->agent());
    }

    /** Les sept lectures : rien ne sort du périmètre. */
    public function test_no_money_record_of_another_company_can_be_read(): void
    {
        $ouvertes = [];

        $lectures = [
            'income' => fn () => app(IncomeInterface::class)->get($this->recetteDe(self::AUTRE)->id),
            'expense' => fn () => app(ExpenseInterface::class)->get($this->depenseDe(self::AUTRE)->id),
            'fund_transfer' => fn () => app(FundTransferInterface::class)->get($this->virementDe(self::AUTRE)->id),
            'merchant_payment' => fn () => app(PaymentInterface::class)->get($this->versementDe(self::AUTRE)->id),
            'account' => fn () => app(AccountInterface::class)->get($this->compteDe(self::AUTRE, 500000)->id),
            'hub_payment_request' => fn () => app(HubPaymentRequestInterface::class)->get($this->demandeHubDe(self::AUTRE)->id),
            'cash_received' => fn () => app(ReceivedInterface::class)->get($this->remiseDe(self::AUTRE)->id),
        ];

        foreach ($lectures as $nom => $appel) {
            if (filled($appel())) {
                $ouvertes[] = $nom;
            }
        }

        $this->assertSame([], $ouvertes, "Écrans d'argent ouverts sur la ligne d'une autre société :\n - "
            . implode("\n - ", $ouvertes));
    }

    /**
     * 🔴 Les six écritures : elles refusent, et **aucun solde ne bouge**.
     *
     * Le solde des comptes est l'assertion qui compte : une écriture qui « échoue »
     * après avoir crédité un compte aurait laissé le désordre derrière elle.
     */
    public function test_no_write_moves_money_of_another_company(): void
    {
        $recette = $this->recetteDe(self::AUTRE);
        $depense = $this->depenseDe(self::AUTRE);
        $virement = $this->virementDe(self::AUTRE);
        $versement = $this->versementDe(self::AUTRE);
        $compte = $this->compteDe(self::AUTRE, 500000);
        $demandeHub = $this->demandeHubDe(self::AUTRE);

        // ⚠️ Mon compte est cree AVANT l'instantane : sinon il apparait comme un solde
        // « qui a bouge » alors qu'il vient seulement de naitre. Premiere version de ce
        // test : faux positif.
        $monCompte = $this->compteDe(settings()->id, 900000);

        $soldes = Account::withoutGlobalScopes()->pluck('balance', 'id')->map(fn ($s) => (float) $s)->all();

        $abouties = [];
        $ecritures = [
            'income/update' => fn () => app(IncomeInterface::class)->update($recette->id, new Request([
                'account_head_id' => $recette->account_head_id, 'amount' => 1, 'account_id' => $monCompte->id,
                'date' => now()->toDateString(), 'title' => 'Détournement',
            ])),
            // ⚠️ `account_head` et non `account_head_id` — c'est le nom que la methode lit.
            // Avec le mauvais nom elle echouait d'elle-meme, et le refus ne prouvait rien.
            'expense/update' => fn () => app(ExpenseInterface::class)->update($depense->id, new Request([
                'account_head' => 6, 'amount' => 1, 'account_id' => $monCompte->id,
                'date' => now()->toDateString(), 'title' => 'Détournement', 'note' => 'x',
                'user_id' => '', 'parcel_id' => '', 'merchant_id' => null,
            ])),
            'fund_transfer/update' => fn () => app(FundTransferInterface::class)->update($virement->id, new Request([
                'from_account' => $monCompte->id, 'to_account' => $monCompte->id, 'amount' => 1,
                'date' => now()->toDateString(),
            ])),
            'merchant_payment/update' => fn () => app(PaymentInterface::class)->update(new Request([
                'id' => $versement->id, 'merchant' => Merchant::firstOrFail()->id, 'amount' => 1,
            ])),
            'merchant_payment/cancelReject' => fn () => app(PaymentInterface::class)->cancelReject($versement->id),
            // ⚠️ Requete COMPLETE (gateway, type, status, opening_balance) : sans elle la
            // methode posait `status` a `null` sur une colonne NOT NULL, echouait toute
            // seule, et le refus ne prouvait rien.
            'account/update' => fn () => app(AccountInterface::class)->update($compte->id, new Request([
                'gateway' => 2, 'type' => 1, 'user' => $this->agent()->id, 'status' => 1,
                'account_holder_name' => 'Pirate', 'account_no' => '000',
                'bank' => 1, 'branch_name' => 'Cotonou', 'opening_balance' => 1000,
            ])),
            'hub_payment_request/update' => fn () => app(HubPaymentRequestInterface::class)->update($demandeHub->id, new Request([
                'amount' => 1, 'description' => 'Détournement',
            ])),
        ];

        foreach ($ecritures as $nom => $appel) {
            if ($appel()) {
                $abouties[] = $nom;
            }
        }

        $this->assertSame([], $abouties, "Écritures abouties sur l'argent d'une autre société :\n - "
            . implode("\n - ", $abouties));

        $apres = Account::withoutGlobalScopes()->pluck('balance', 'id')->map(fn ($s) => (float) $s)->all();
        $bouges = array_keys(array_diff_assoc($apres, $soldes));

        $this->assertSame([], $bouges, 'des soldes de comptes ont bougé : ' . implode(', ', $bouges));
    }

    /** Et rien n'a été réaffecté ni renommé au passage. */
    public function test_nothing_of_the_neighbours_money_was_rewritten(): void
    {
        $versement = $this->versementDe(self::AUTRE);
        $compte = $this->compteDe(self::AUTRE, 500000);
        $demandeHub = $this->demandeHubDe(self::AUTRE);

        $marchandDorigine = $versement->merchant_id;
        $statutDorigine = (int) $versement->fresh()->status;
        $titulaire = $compte->account_holder_name;
        $entrepot = $demandeHub->hub_id;

        app(PaymentInterface::class)->update(new Request(['id' => $versement->id, 'merchant' => Merchant::firstOrFail()->id, 'amount' => 1]));
        app(PaymentInterface::class)->cancelReject($versement->id);
        app(AccountInterface::class)->update($compte->id, new Request([
            'gateway' => 2, 'type' => 1, 'user' => $this->agent()->id, 'status' => 1,
            'account_holder_name' => 'Pirate', 'account_no' => '000',
            'bank' => 1, 'branch_name' => 'Cotonou', 'opening_balance' => 1000,
        ]));
        app(HubPaymentRequestInterface::class)->update($demandeHub->id, new Request(['amount' => 1, 'description' => 'x']));

        $this->assertSame($marchandDorigine, $versement->fresh()->merchant_id, 'le versement a été réaffecté');
        $this->assertSame($statutDorigine, (int) $versement->fresh()->status, 'le statut du versement a changé');
        $this->assertSame($titulaire, $compte->fresh()->account_holder_name, 'le compte bancaire a été renommé');
        $this->assertSame($entrepot, $demandeHub->fresh()->hub_id, 'la demande a été rattachée à un autre entrepôt');
    }

    /**
     * Le contrôle négatif : mes propres lignes restent lisibles. Sans lui, un
     * périmètre qui ne renverrait jamais rien ferait passer tout ce qui précède.
     */
    public function test_my_own_money_records_are_still_readable(): void
    {
        $this->assertNotNull(app(IncomeInterface::class)->get($this->recetteDe(settings()->id)->id));
        $this->assertNotNull(app(ExpenseInterface::class)->get($this->depenseDe(settings()->id)->id));
        $this->assertNotNull(app(FundTransferInterface::class)->get($this->virementDe(settings()->id)->id));
        $this->assertNotNull(app(PaymentInterface::class)->get($this->versementDe(settings()->id)->id));
        $this->assertNotNull(app(AccountInterface::class)->get($this->compteDe(settings()->id, 1000)->id));
        $this->assertNotNull(app(HubPaymentRequestInterface::class)->get($this->demandeHubDe(settings()->id)->id));
        $this->assertNotNull(app(ReceivedInterface::class)->get($this->remiseDe(settings()->id)->id));
    }

    /**
     * ⚠️ Le décaissement d'un versement marchand, avec l'identifiant dans le corps.
     * Le filet ne voit pas cette route ; ce test si.
     */
    public function test_the_payout_screens_refuse_a_payment_of_another_company(): void
    {
        $sien = $this->versementDe(self::AUTRE);
        $monCompte = $this->compteDe(settings()->id, 900000);
        $controleur = app(\App\Http\Controllers\MerchantmanagePaymentController::class);

        $appels = [
            'edit' => fn () => $controleur->edit($sien->id),
            'process' => fn () => $controleur->process($sien->id),
        ];

        foreach ($appels as $nom => $appel) {
            try {
                $appel();
                $this->fail("MerchantmanagePaymentController::{$nom}() s'est ouvert sur le versement d'une autre société");
            } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
                $this->assertSame(404, $e->getStatusCode());
            }
        }

        $requete = new \App\Http\Requests\Merchantmanage\Payment\ProcessRequest();
        $requete->replace(['id' => $sien->id, 'from_account' => $monCompte->id, 'transaction_id' => 'X']);

        try {
            $controleur->processed($requete);
            $this->fail('le décaissement a accepté le versement d\'une autre société');
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }

        $this->assertSame(900000.0, (float) $monCompte->fresh()->balance, 'notre compte a été débité');
    }


    /**
     * Les sept suppressions et les deux transitions de statut. Elles étaient déjà
     * **gardées** — la forme « je cherche nu, puis je compare `company_id` » — ou
     * déjà scopées. Rien à corriger ; tout à inscrire.
     */
    public function test_no_delete_or_transition_reaches_another_company(): void
    {
        $recette = $this->recetteDe(self::AUTRE);
        $depense = $this->depenseDe(self::AUTRE);
        $virement = $this->virementDe(self::AUTRE);
        $versement = $this->versementDe(self::AUTRE);
        $compte = $this->compteDe(self::AUTRE, 500000);
        $demandeHub = $this->demandeHubDe(self::AUTRE);
        $remise = $this->remiseDe(self::AUTRE);

        $abouties = [];
        $appels = [
            'income/delete' => fn () => app(IncomeInterface::class)->delete($recette->id),
            'expense/delete' => fn () => app(ExpenseInterface::class)->delete($depense->id),
            'fund_transfer/delete' => fn () => app(FundTransferInterface::class)->delete($virement->id),
            'merchant_payment/delete' => fn () => app(PaymentInterface::class)->delete($versement->id),
            'merchant_payment/reject' => fn () => app(PaymentInterface::class)->reject($versement->id),
            'merchant_payment/cancelProcess' => fn () => app(PaymentInterface::class)->cancelProcess($versement->id),
            'account/delete' => fn () => app(AccountInterface::class)->delete($compte->id),
            'hub_payment_request/delete' => fn () => app(HubPaymentRequestInterface::class)->delete($demandeHub->id),
            'cash_received/delete' => fn () => app(ReceivedInterface::class)->delete($remise->id),
        ];

        foreach ($appels as $nom => $appel) {
            if ($appel()) {
                $abouties[] = $nom;
            }
        }

        $this->assertSame([], $abouties, "Suppressions ou transitions abouties sur l'argent d'une autre "
            . "société :\n - " . implode("\n - ", $abouties));

        $this->assertNotNull(Income::withoutGlobalScopes()->find($recette->id));
        $this->assertNotNull(Expense::withoutGlobalScopes()->find($depense->id));
        $this->assertNotNull(FundTransfer::withoutGlobalScopes()->find($virement->id));
        $this->assertNotNull(Payment::withoutGlobalScopes()->find($versement->id));
        $this->assertNotNull(Account::withoutGlobalScopes()->find($compte->id));
        $this->assertNotNull(HubPayment::withoutGlobalScopes()->find($demandeHub->id));
        $this->assertNotNull(CashReceivedFromDeliveryman::withoutGlobalScopes()->find($remise->id));
    }

    /**
     * Et la route morte retirée au passage : `POST admin/income/search-account/{id}`.
     * Personne ne l'appelait — même l'écran des revenus appelle celle des dépenses —
     * et son contrôleur passait l'objet `Request` a `find()`, qui le traite comme un
     * tableau de clés (`Request` est `Arrayable`) et renvoyait donc une **collection**
     * au lieu d'un compte. Signalée à la 3e passe, retirée ici.
     */
    public function test_the_dead_income_account_lookup_route_is_gone(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringNotContainsString('income/search-account', $routes);

        // Et l'unique appelant réel, celui des dépenses, est toujours là.
        $this->assertStringContainsString('expense/search-account', $routes);
    }

    /* ─────────────────────────── fixtures ───────────────────────────────── */

    private function agent(): User
    {
        return User::firstWhere('email', 'agent.argent@example.test') ?? tap(new User(), function ($a) {
            $a->company_id = settings()->id;
            $a->name = 'Agent argent';
            $a->email = 'agent.argent@example.test';
            $a->mobile = '0022997004444';
            $a->password = bcrypt('secret');
            $a->user_type = UserType::ADMIN;
            $a->save();
        });
    }

    private array $cache = [];

    /** Une seule instance par (type, société) : les fixtures sont partagées entre tests. */
    private function memo(string $cle, callable $fabrique)
    {
        return $this->cache[$cle] ??= $fabrique();
    }

    private function posteComptable(): void
    {
        $this->memo('poste', function () {
            for ($i = 1; $i <= 6; $i++) {
                if (blank(\App\Models\Backend\AccountHead::find($i))) {
                    \App\Models\Backend\AccountHead::forceCreate([
                        'id' => $i, 'type' => \App\Enums\AccountHeads::EXPENSE,
                        'name' => 'Poste ' . $i, 'status' => 1,
                    ]);
                }
            }

            return true;
        });
    }

    private function compteDe(int $societe, float $solde): Account
    {
        return $this->memo("compte:$societe:$solde", fn () => Account::forceCreate([
            'company_id' => $societe, 'balance' => $solde,
            'account_holder_name' => 'Titulaire ' . $societe,
            'account_no' => 'CPT-' . $societe . '-' . $solde,
        ]));
    }

    private function recetteDe(int $societe): Income
    {
        return $this->memo("recette:$societe", fn () => Income::forceCreate([
            'company_id' => $societe, 'title' => 'Recette de ' . $societe,
            'account_id' => $this->compteDe($societe, 500000)->id,
            'amount' => 40000, 'date' => now()->toDateString(),
        ]));
    }

    /**
     * ⚠️ La depense doit porter SON ecriture bancaire : `update()` termine par
     * `BankTransaction::where('expense_id',$id)->first()` puis ecrit dessus. Sans elle,
     * la methode leve sur `null` et son `catch` rend `false` — un refus qui ne vient
     * pas du perimetre.
     */
    private function depenseDe(int $societe): Expense
    {
        return $this->memo("depense:$societe", function () use ($societe) {
            // `SeedsTenant` n'inclut pas `AccountHeadSeeder` : le poste comptable 6 —
            // celui que `update()` traite a part, sans aller lire son libelle — doit
            // exister pour la cle etrangere de `expenses.account_head_id`.
            $this->posteComptable();

            $depense = Expense::forceCreate([
                'company_id' => $societe, 'title' => 'Depense de ' . $societe,
                'account_head_id' => 6,
                'account_id' => $this->compteDe($societe, 500000)->id,
                'amount' => 25000, 'date' => now()->toDateString(),
            ]);

            \App\Models\Backend\BankTransaction::forceCreate([
                'company_id' => $societe,
                'account_id' => $depense->account_id,
                'expense_id' => $depense->id,
                'type' => \App\Enums\AccountHeads::EXPENSE,
                'amount' => $depense->amount,
                'date' => now(),
                'note' => 'Depense',
            ]);

            return $depense;
        });
    }

    private function virementDe(int $societe): FundTransfer
    {
        return $this->memo("virement:$societe", fn () => FundTransfer::forceCreate([
            'company_id' => $societe,
            'from_account' => $this->compteDe($societe, 500000)->id,
            'to_account' => $this->compteDe($societe, 500000)->id,
            'amount' => 15000, 'date' => now()->toDateString(),
        ]));
    }

    private function versementDe(int $societe): Payment
    {
        return $this->memo("versement:$societe", fn () => Payment::forceCreate([
            'company_id' => $societe,
            'merchant_id' => Merchant::where('company_id', $societe)->first()?->id ?? Merchant::firstOrFail()->id,
            'amount' => 60000,
        ]));
    }

    private function demandeHubDe(int $societe): HubPayment
    {
        return $this->memo("demandeHub:$societe", fn () => HubPayment::forceCreate([
            'company_id' => $societe,
            'hub_id' => Hub::forceCreate(['company_id' => $societe, 'name' => 'Entrepot ' . $societe, 'status' => 1])->id,
            'amount' => 30000,
        ]));
    }

    /**
     * ⚠️ Cette fixture doit porter `user_id`, `hub_id` ET `delivery_man_id`.
     * Le modele journalise `user.hub.name` et `deliveryman.user.name` ; avec ces
     * relations a `null`, le trait `LogsActivity` de Spatie leve une `TypeError` en
     * remontant la chaine. Ce n'est PAS un defaut du socle : `ReceivedRepository::store()`
     * renseigne toujours les trois. Ma premiere version de la fixture ne le faisait pas,
     * et j'ai failli prendre le plantage pour un bug de l'application.
     */
    private function remiseDe(int $societe): CashReceivedFromDeliveryman
    {
        return $this->memo("remise:$societe", function () use ($societe) {
            $entrepot = Hub::forceCreate(['company_id' => $societe, 'name' => 'Entrepot remise ' . $societe, 'status' => 1]);

            $agent = new User();
            $agent->company_id = $societe;
            $agent->name = 'Chef de hub ' . $societe;
            $agent->email = 'hub.' . $societe . '@example.test';
            $agent->mobile = '00229970055' . $societe;
            $agent->password = bcrypt('secret');
            $agent->user_type = UserType::INCHARGE;
            $agent->hub_id = $entrepot->id;
            $agent->save();

            $livreurUtilisateur = new User();
            $livreurUtilisateur->company_id = $societe;
            $livreurUtilisateur->name = 'Livreur ' . $societe;
            $livreurUtilisateur->email = 'livreur.argent.' . $societe . '@example.test';
            $livreurUtilisateur->mobile = '00229970066' . $societe;
            $livreurUtilisateur->password = bcrypt('secret');
            $livreurUtilisateur->user_type = UserType::DELIVERYMAN;
            $livreurUtilisateur->save();

            $livreur = \App\Models\Backend\DeliveryMan::forceCreate([
                'company_id' => $societe, 'user_id' => $livreurUtilisateur->id,
            ]);

            return CashReceivedFromDeliveryman::forceCreate([
                'company_id' => $societe,
                'user_id' => $agent->id,
                'hub_id' => $entrepot->id,
                'delivery_man_id' => $livreur->id,
                'account_id' => $this->compteDe($societe, 500000)->id,
                'amount' => 12000, 'date' => now(),
            ]);
        });
    }
}
