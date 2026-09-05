<?php

namespace Tests\Feature;

use App\Enums\Status;
use App\Models\Backend\Merchant;
use App\Repositories\Merchant\MerchantInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * Le solde d'ouverture d'un marchand, et ce qu'il fait à son solde courant.
 *
 * ⚠️ Le socle **écrasait** `current_balance` avec le solde d'ouverture à chaque
 * enregistrement de la fiche. Ré-enregistrer un marchand pour corriger son
 * adresse ou son numéro effaçait donc tout ce qui s'était accumulé depuis son
 * ouverture — encaissements, frais, retraits — sans le moindre avertissement et
 * sans laisser de trace au relevé.
 *
 * C'est l'un des deux chemins que le rapprochement (**D9**) montrait comme
 * « écart inexpliqué » : le solde partait, le relevé restait.
 *
 * La règle est celle de D9 : `current_balance` est un cache du relevé,
 * `opening_balance + Σ(recettes) − Σ(dépenses)`. Corriger l'ouverture déplace
 * donc le solde courant du **même écart** ; ne pas la toucher ne touche à rien.
 */
class MerchantOpeningBalanceTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private Merchant $marchand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();

        $this->marchand = Merchant::firstOrFail();
        $this->marchand->opening_balance = 10000;
        // Le marchand a travaillé depuis : 42 500 F encaissés nets.
        $this->marchand->current_balance = 52500;
        $this->marchand->save();

        $this->actingAs($this->marchand->user->fresh());
    }

    private function enregistrer(array $surcharges = []): bool
    {
        $utilisateur = $this->marchand->user;

        return app(MerchantInterface::class)->update($this->marchand->id, new Request($surcharges + [
            'area' => ['inside_city', 'sub_city', 'outside_city'],
            'charge' => ['inside_city' => 1, 'sub_city' => 2, 'outside_city' => 3],
            'name' => $utilisateur->name,
            'mobile' => $utilisateur->mobile,
            'email' => $utilisateur->email,
            'address' => 'Cotonou, Akpakpa',
            'hub' => $utilisateur->hub_id,
            'status' => Status::ACTIVE,
            'business_name' => $this->marchand->business_name,
            'vat' => '',
            'payment_period' => 7,
            'wallet_use_activation' => Status::INACTIVE,
        ]));
    }

    private function solde(): float
    {
        return (float) Merchant::find($this->marchand->id)->current_balance;
    }

    private function ouverture(): float
    {
        return (float) Merchant::find($this->marchand->id)->opening_balance;
    }

    /**
     * Le cas qui faisait mal : ré-enregistrer la fiche sans toucher au solde
     * d'ouverture. Le socle ramenait le solde courant à 10 000 — les 42 500 F
     * gagnés depuis disparaissaient.
     */
    public function test_saving_the_form_unchanged_leaves_the_balance_alone(): void
    {
        $this->assertTrue($this->enregistrer(['opening_balance' => 10000]));

        $this->assertSame(52500.0, $this->solde());
        $this->assertSame(10000.0, $this->ouverture());
    }

    /** Sans solde d'ouverture posté du tout, rien ne bouge non plus. */
    public function test_saving_without_an_opening_balance_leaves_the_balance_alone(): void
    {
        $this->assertTrue($this->enregistrer(['opening_balance' => '']));

        $this->assertSame(52500.0, $this->solde());
        $this->assertSame(10000.0, $this->ouverture());
    }

    /**
     * Corriger l'ouverture à la hausse : le solde courant suit du **même
     * écart**, pas de la valeur d'ouverture. On corrige une saisie, on
     * n'efface pas une activité.
     */
    public function test_raising_the_opening_balance_moves_the_current_one_by_the_same_amount(): void
    {
        $this->enregistrer(['opening_balance' => 25000]);

        $this->assertSame(25000.0, $this->ouverture());
        $this->assertSame(52500.0 + 15000, $this->solde());
    }

    /** Et à la baisse, symétriquement. */
    public function test_lowering_the_opening_balance_moves_the_current_one_down(): void
    {
        $this->enregistrer(['opening_balance' => 4000]);

        $this->assertSame(4000.0, $this->ouverture());
        $this->assertSame(52500.0 - 6000, $this->solde());
    }

    /** Ramener l'ouverture à zéro n'annule que l'ouverture. */
    public function test_zeroing_the_opening_balance_only_removes_the_opening(): void
    {
        $this->enregistrer(['opening_balance' => 0]);

        $this->assertSame(0.0, $this->ouverture());
        $this->assertSame(42500.0, $this->solde());
    }

    /**
     * La propriété qui résume tout : après l'enregistrement, le rapprochement
     * de D9 ne voit aucun écart. C'est exactement ce que la commande signalait
     * avant, sans pouvoir l'expliquer.
     */
    public function test_the_reconciliation_sees_no_drift_after_an_edit(): void
    {
        // Un marchand dont le solde répond à son relevé (aucune écriture, donc
        // solde = ouverture), puis une correction de l'ouverture.
        $this->marchand->opening_balance = 10000;
        $this->marchand->current_balance = 10000;
        $this->marchand->save();

        $this->enregistrer(['opening_balance' => 30000]);

        $this->assertSame(30000.0, $this->solde());
        $this->artisan('beninlink:ecarts-marchands')
            ->expectsOutputToContain('Aucun écart')
            ->assertExitCode(0);
    }
}
