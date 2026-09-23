<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\Account;
use App\Models\Backend\AccountHead;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\DeliverymanStatement;
use App\Models\Backend\Hub;
use App\Models\Backend\Income;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\MerchantStatement;
use App\Models\User;
use App\Models\Backend\Expense;
use App\Models\Backend\Salary;
use App\Models\Backend\Payroll\SalaryGenerate;
use App\Repositories\Expense\ExpenseInterface;
use App\Repositories\Income\IncomeInterface;
use App\Repositories\Salary\SalaryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * S48 — les CONTREPARTIES d'une ecriture comptable.
 *
 * Le motif se repete pour la quatrieme fois, et c'est ce qui le rend
 * interessant : S45 l'a trouve sur l'agent d'un mouvement de colis, S46 sur les
 * catalogues d'un colis, S47 sur le catalogue d'un compte. A chaque fois la
 * RESSOURCE etait gardee et le SECOND identifiant ne l'etait pas.
 *
 * Ici c'est le module comptable, et la ressource y est bien gardee depuis S30 :
 * `Income::companywise()->find($id)`, `Expense::companywise()->find($id)`. Mais
 * une ecriture de recette ou de depense ne touche pas que sa propre ligne —
 * elle DEPLACE DE L'ARGENT sur une contrepartie nommee dans la requete :
 *
 * | Poste | Contrepartie lue | Ce que le socle en faisait |
 * |---|---|---|
 * | 1 | `Merchant::find($request->merchant_id)` | `current_balance + amount`, puis `save()` |
 * | 2 | `DeliveryMan::find($request->delivery_man_id)` | idem |
 * | 7 | `Hub::find($request->hub_id)` | idem |
 * | 3 | `$request->user_id` | inscrit sur la recette |
 * | tous | `Account::find($request->account_id)` | le compte de TRESORERIE mouvemente |
 *
 * Aucune de ces cinq lectures n'avait de perimetre. Une recette saisie chez
 * nous creditait donc le solde d'un marchand, d'un livreur ou d'un entrepot
 * d'une AUTRE societe, en lui attachant un releve portant NOTRE `company_id` —
 * et pouvait mouvementer le compte de tresorerie d'en face.
 */
class AccountingCounterpartyScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private const AUTRE = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTenant();
        $this->postesComptables();
        $this->actingAs($this->agentDe(settings()->id));
    }

    /**
     * Poste 1 — le marchand. Le solde d'un marchand d'une autre societe etait
     * credite, et un `MerchantStatement` portant NOTRE `company_id` lui etait
     * attache.
     */
    public function test_an_income_never_credits_a_merchant_of_another_company(): void
    {
        $sien = Merchant::where('company_id', self::AUTRE)->firstOrFail();
        $avant = (float) $sien->current_balance;

        $this->assertFalse(
            (bool) app(IncomeInterface::class)->store($this->requete([
                'account_head_id' => 1, 'merchant_id' => $sien->id,
            ])),
            'une recette a credite le marchand d\'une autre societe',
        );

        $this->assertSame($avant, (float) $sien->fresh()->current_balance);
        $this->assertSame(0, MerchantStatement::where('merchant_id', $sien->id)->count());
        $this->assertSame(0, Income::count());

        // Controle negatif : notre marchand est bien credite.
        $mien = $this->marchandDe(settings()->id);
        $this->assertTrue((bool) app(IncomeInterface::class)->store($this->requete([
            'account_head_id' => 1, 'merchant_id' => $mien->id,
        ])));
        $this->assertSame(1000.0, (float) $mien->fresh()->current_balance);
    }

    /** Poste 2 — le livreur. Meme geste, meme ecriture de solde. */
    public function test_an_income_never_credits_a_deliveryman_of_another_company(): void
    {
        $sien = $this->livreurDe(self::AUTRE);
        $avant = (float) $sien->current_balance;

        $this->assertFalse((bool) app(IncomeInterface::class)->store($this->requete([
            'account_head_id' => 2, 'delivery_man_id' => $sien->id,
        ])));

        $this->assertSame($avant, (float) $sien->fresh()->current_balance);
        $this->assertSame(0, DeliverymanStatement::where('delivery_man_id', $sien->id)->count());
        $this->assertSame(0, Income::count());

        $mien = $this->livreurDe(settings()->id);
        $this->assertTrue((bool) app(IncomeInterface::class)->store($this->requete([
            'account_head_id' => 2, 'delivery_man_id' => $mien->id,
        ])));
        $this->assertSame(1000.0, (float) $mien->fresh()->current_balance);
    }

    /** Poste 7 — l'entrepot. */
    public function test_an_income_never_credits_a_hub_of_another_company(): void
    {
        $sien = Hub::forceCreate([
            'company_id' => self::AUTRE, 'name' => 'Entrepot voisin', 'status' => Status::ACTIVE, 'current_balance' => 0,
        ]);

        $this->assertFalse((bool) app(IncomeInterface::class)->store($this->requete([
            'account_head_id' => 7, 'hub_id' => $sien->id,
        ])));

        $this->assertSame(0.0, (float) $sien->fresh()->current_balance);
        $this->assertSame(0, Income::count());
    }

    /**
     * ⚠️ Le compte de TRESORERIE, commun a tous les postes. C'est la
     * contrepartie la plus sensible du lot : elle porte l'argent de la societe.
     */
    public function test_an_income_never_moves_the_cash_account_of_another_company(): void
    {
        $sonCompte = Account::forceCreate([
            'company_id' => self::AUTRE, 'balance' => 500000,
            'account_holder_name' => 'Titulaire voisin', 'account_no' => 'CPT-VOISIN',
        ]);
        $mien = $this->marchandDe(settings()->id);

        $this->assertFalse((bool) app(IncomeInterface::class)->store($this->requete([
            'account_head_id' => 1, 'merchant_id' => $mien->id, 'account_id' => $sonCompte->id,
        ])));

        $this->assertSame(500000.0, (float) $sonCompte->fresh()->balance, 'le compte de tresorerie d\'en face a bouge');
        $this->assertSame(0.0, (float) $mien->fresh()->current_balance, 'le marchand a ete credite malgre le refus');
        $this->assertSame(0, Income::count());
    }

    /**
     * La piece rattachee a l'ecriture. Le socle inscrivait `parcel_id` sans le
     * verifier : l'ecran des recettes rend la colonne `parcel` avec son
     * `tracking_id` et son client — le colis d'une autre societe s'affichait
     * donc dans notre comptabilite.
     */
    public function test_an_income_never_attaches_a_parcel_of_another_company(): void
    {
        $sonColis = Parcel::forceCreate([
            'company_id' => self::AUTRE,
            'merchant_id' => Merchant::where('company_id', self::AUTRE)->firstOrFail()->id,
            'tracking_id' => 'BL-VOISIN-S48',
            'customer_name' => 'Client du voisin', 'customer_phone' => '0022990000000',
            'customer_address' => 'Ailleurs', 'cash_collection' => 5000, 'current_payable' => 4000,
            'status' => 1,
        ]);
        $mien = $this->marchandDe(settings()->id);

        $this->assertFalse(
            (bool) app(IncomeInterface::class)->store($this->requete([
                'account_head_id' => 1, 'merchant_id' => $mien->id, 'parcel_id' => $sonColis->id,
            ])),
            'une recette a ete rattachee au colis d\'une autre societe',
        );
        $this->assertSame(0, Income::count());
        $this->assertSame(0.0, (float) $mien->fresh()->current_balance);
    }

    /* ─────────── les huit portes, chacune avec son controle ─────────────── */

    /**
     * ⚠️ Le sabotage a montre qu'un seul des huit points d'appel etait prouve.
     * Une garde partagee par un trait donne l'illusion d'une couverture que ses
     * appelants n'ont pas — quatrieme fois que ce reflexe rattrape un lot.
     *
     * Chaque cas porte son controle negatif : avec NOTRE contrepartie, l'ecriture
     * doit passer. Sans cette moitie, on mesurerait un refus deja acquis pour
     * une autre raison — une fixture incomplete, un champ manquant.
     */
    public function test_every_accounting_door_refuses_a_foreign_counterparty(): void
    {
        $sonCompte = Account::forceCreate([
            'company_id' => self::AUTRE, 'balance' => 500000,
            'account_holder_name' => 'Titulaire voisin', 'account_no' => 'CPT-VOISIN',
        ]);
        $sonMarchand = Merchant::where('company_id', self::AUTRE)->firstOrFail();
        $sonAgent = $this->agentDe(self::AUTRE);
        $monMarchand = $this->marchandDe(settings()->id);
        $monAgent = auth()->user();

        $recette = app(IncomeInterface::class);
        $depense = app(ExpenseInterface::class);
        $paie = app(SalaryInterface::class);

        // Une recette a soi, pour que `update` ait une prise.
        $this->assertTrue((bool) $recette->store($this->requete(['account_head_id' => 1, 'merchant_id' => $monMarchand->id])));
        $laRecette = Income::firstOrFail();

        $portes = [
            'income/update' => [
                fn (array $c) => $recette->update($laRecette->id, $this->requete($c + ['account_head_id' => 1])),
                ['merchant_id' => $sonMarchand->id], ['merchant_id' => $monMarchand->id],
            ],
            'expense/store' => [
                fn (array $c) => $depense->store($this->requete($c + ['account_head' => 1])),
                ['merchant_id' => $sonMarchand->id], ['merchant_id' => $monMarchand->id],
            ],
            'salary/generate-store' => [
                fn (array $c) => $paie->salaryGenerateStore($this->requete($c + ['month' => '2026-09'])),
                ['user_id' => $sonAgent->id], ['user_id' => $monAgent->id],
            ],
            'salary/store' => [
                fn (array $c) => $paie->store($this->requete($c + ['month' => '2026-09'])),
                ['user_id' => $sonAgent->id], ['user_id' => $monAgent->id],
            ],
        ];

        foreach ($portes as $ou => [$appel, $etranger, $mien]) {
            $this->assertFalse((bool) $appel($etranger), "{$ou} : une contrepartie etrangere a ete acceptee");
            $this->assertTrue((bool) $appel($mien), "{$ou} : notre propre contrepartie a ete refusee");
        }

        // ⚠️ Les trois `update` ne sont atteints qu'avec une ressource A NOUS et
        // une contrepartie etrangere : sur une ressource etrangere, c'est la
        // garde de la ressource qui refuse d'abord, et la garde des
        // contreparties resterait verte sous sabotage. Le sabotage l'a dit.
        $this->assertTrue((bool) $depense->store($this->requete(['account_head' => 1, 'merchant_id' => $monMarchand->id])));
        $laDepense = Expense::orderByDesc('id')->firstOrFail();

        $this->assertTrue((bool) $paie->salaryGenerateStore($this->requete(['user_id' => $monAgent->id, 'month' => '2026-10'])));
        $leBulletin = SalaryGenerate::orderByDesc('id')->firstOrFail();

        $this->assertTrue((bool) $paie->store($this->requete(['user_id' => $monAgent->id, 'month' => '2026-10'])));
        $laPaie = Salary::orderByDesc('id')->firstOrFail();

        $misesAJour = [
            'expense/update' => [
                fn (array $c) => $depense->update($laDepense->id, $this->requete($c + ['account_head' => 1])),
                ['merchant_id' => $sonMarchand->id], ['merchant_id' => $monMarchand->id],
            ],
            'salary/generate-update' => [
                fn (array $c) => $paie->salaryGenerateUpdate($this->requete($c + ['id' => $leBulletin->id, 'month' => '2026-10'])),
                ['user_id' => $sonAgent->id], ['user_id' => $monAgent->id],
            ],
            'salary/update' => [
                fn (array $c) => $paie->update($laPaie->id, $this->requete($c + ['month' => '2026-10'])),
                ['account_id' => $sonCompte->id, 'user_id' => $monAgent->id],
                ['account_id' => $this->monCompte()->id, 'user_id' => $monAgent->id],
            ],
        ];

        foreach ($misesAJour as $ou => [$appel, $etranger, $mien]) {
            $this->assertFalse((bool) $appel($etranger), "{$ou} : une contrepartie etrangere a ete acceptee");
            $this->assertTrue((bool) $appel($mien), "{$ou} : notre propre contrepartie a ete refusee");
        }

        // Le compte de TRESORERIE, commun aux huit portes.
        $this->assertFalse(
            (bool) $depense->store($this->requete([
                'account_head' => 1, 'merchant_id' => $monMarchand->id, 'account_id' => $sonCompte->id,
            ])),
            'une depense a mouvemente le compte de tresorerie d\'une autre societe',
        );
        $this->assertSame(500000.0, (float) $sonCompte->fresh()->balance);
    }

    /**
     * ⚠️ La RESSOURCE elle-meme, trouvee en gardant ses contreparties.
     * `SalaryGenerate::find($request->id)` etait nu dans `salaryGenerateUpdate`,
     * alors que tout le reste du depot est `companywise()` depuis S30 : le
     * bulletin de paie d'une autre societe etait modifiable en changeant
     * l'identifiant du corps.
     */
    public function test_a_payslip_of_another_company_cannot_be_updated(): void
    {
        $sien = SalaryGenerate::forceCreate([
            'company_id' => self::AUTRE, 'user_id' => $this->agentDe(self::AUTRE)->id,
            'month' => '2026-09', 'amount' => 50000, 'note' => 'Voisin',
        ]);

        $this->assertFalse((bool) app(SalaryInterface::class)->salaryGenerateUpdate($this->requete([
            'id' => $sien->id, 'user_id' => auth()->id(), 'month' => '2026-09',
        ])));

        $this->assertSame(50000.0, (float) $sien->fresh()->amount, 'le bulletin d\'une autre societe a ete modifie');
    }

    /* ───────────────────────────── fixtures ─────────────────────────────── */

    private function requete(array $champs): Request
    {
        return new Request($champs + [
            'account_id' => $this->monCompte()->id,
            'amount' => 1000,
            'date' => date('Y-m-d'),
            'title' => 'Ecriture S48',
            'note' => 'S48',
            'parcel_id' => '',
        ]);
    }

    private function monCompte(): Account
    {
        return Account::firstWhere('account_no', 'CPT-MAISON') ?? Account::forceCreate([
            'company_id' => settings()->id, 'balance' => 500000,
            'account_holder_name' => 'Titulaire maison', 'account_no' => 'CPT-MAISON',
        ]);
    }

    private function postesComptables(): void
    {
        foreach (range(1, 7) as $i) {
            if (blank(AccountHead::find($i))) {
                AccountHead::forceCreate(['id' => $i, 'type' => 1, 'name' => 'Poste ' . $i, 'status' => 1]);
            }
        }
    }

    private function agentDe(int $societe): User
    {
        $agent = new User();
        $agent->company_id = $societe;
        $agent->name = 'Agent comptable';
        $agent->email = 'agent.s48.' . $societe . '@example.test';
        $agent->mobile = '00229976100' . $societe;
        $agent->password = bcrypt('secret');
        $agent->user_type = UserType::ADMIN;
        $agent->save();

        return $agent;
    }

    private function marchandDe(int $societe): Merchant
    {
        $utilisateur = new User();
        $utilisateur->company_id = $societe;
        $utilisateur->name = 'Marchand S48';
        $utilisateur->email = 'marchand.s48.' . $societe . '@example.test';
        $utilisateur->mobile = '00229976200' . $societe;
        $utilisateur->password = bcrypt('secret');
        $utilisateur->user_type = UserType::MERCHANT;
        $utilisateur->save();

        return Merchant::forceCreate([
            'company_id' => $societe, 'user_id' => $utilisateur->id,
            'business_name' => 'PME S48 ' . $societe, 'current_balance' => 0,
        ]);
    }

    private function livreurDe(int $societe): DeliveryMan
    {
        $utilisateur = new User();
        $utilisateur->company_id = $societe;
        $utilisateur->name = 'Livreur S48';
        $utilisateur->email = 'livreur.s48.' . $societe . '@example.test';
        $utilisateur->mobile = '00229976300' . $societe;
        $utilisateur->password = bcrypt('secret');
        $utilisateur->user_type = UserType::DELIVERYMAN;
        $utilisateur->unique_id = 'L-S48-' . $societe;
        $utilisateur->save();

        return DeliveryMan::forceCreate([
            'company_id' => $societe, 'user_id' => $utilisateur->id, 'status' => Status::ACTIVE,
            'delivery_charge' => 500, 'pickup_charge' => 200, 'return_charge' => 300,
            'opening_balance' => 0, 'current_balance' => 0,
        ]);
    }
}
