<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Enums\StatementType;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Merchant;
use App\Models\Backend\MerchantStatement;
use App\Models\Backend\Parcel;
use App\Models\Backend\VatStatement;
use App\Repositories\Parcel\ParcelInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\BuildsAccountingFixtures;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * `beninlink:ecarts-marchands` — le rapprochement du solde et du relevé.
 *
 * `merchants.current_balance` n'est qu'un cache : la vérité est le relevé.
 * L'annulation d'une livraison partielle créditait le solde d'une TVA
 * recalculée alors que sa ligne de relevé portait la TVA réellement prélevée —
 * 14,40 F d'écart par colis, définitif. Le correctif arrête l'hémorragie ; il
 * ne rattrape pas le passé.
 *
 * ⚠️ Les écarts sont ici **fabriqués à la main**, et il ne peut pas en être
 * autrement : depuis le correctif, le code ne sait plus en produire. On
 * reconstitue donc l'état que la version fautive laissait — mêmes colis, mêmes
 * lignes de relevé, seul le solde décalé.
 */
class MerchantBalanceDriftCommandTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;
    use BuildsAccountingFixtures;

    /** Ce que l'annulation sur-créditait : 216 F recalculés contre 201,60 F prélevés. */
    private const ECART = 14.4;

    private Merchant $marchand;
    private DeliveryMan $livreur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();

        $this->marchand = Merchant::firstOrFail();
        $this->marchand->current_balance = 0;
        $this->marchand->opening_balance = 0;
        $this->marchand->save();

        $this->actingAs($this->marchand->user->fresh());
        $this->livreur = $this->livreur();
    }

    /**
     * Rejoue une livraison partielle puis son annulation, et remet le solde
     * dans l'état que la version fautive laissait.
     */
    private function colisPartielAnnule(string $suivi = 'BL-PARTIEL'): Parcel
    {
        $colis = $this->colisConfie($this->marchand, $this->livreur, $suivi);

        $repo = app(ParcelInterface::class);
        $repo->parcelPartialDelivered($colis->id, new Request(['cash_collection' => 12000]));
        $repo->parcelPartialDeliveredCancel($colis->id, new Request());

        // Fixture HISTORIQUE : le calcul courant arrondit désormais à 202 XOF.
        // La commande doit encore comprendre les anciennes lignes à 201,60,
        // sans modifier leur formule d'explication ni leur régularisation.
        VatStatement::where('parcel_id', $colis->id)->update(['amount' => 201.6]);
        MerchantStatement::where('parcel_id', $colis->id)->where('amount', 202)
            ->update(['amount' => 201.6]);

        $marchand = Merchant::find($this->marchand->id);
        $marchand->current_balance = (float) $marchand->current_balance + self::ECART;
        $marchand->save();

        return $colis->fresh();
    }

    private function solde(): float
    {
        return (float) Merchant::find($this->marchand->id)->current_balance;
    }

    // ---- le constat -------------------------------------------------------

    /** Sans écart, la commande le dit et ne propose rien. */
    public function test_it_reports_nothing_when_every_balance_matches(): void
    {
        $this->artisan('beninlink:ecarts-marchands')
            ->expectsOutputToContain('Aucun écart')
            ->assertExitCode(0);
    }

    /** Un colis concerné : l'écart apparaît, et il est expliqué. */
    public function test_it_finds_the_drift_and_explains_it(): void
    {
        $this->colisPartielAnnule();

        $this->artisan('beninlink:ecarts-marchands')
            ->expectsOutputToContain('1 marchand(s) dont le solde ne répond pas au relevé')
            ->assertExitCode(0);

        // Rien n'est touché tant qu'on ne le demande pas.
        $this->assertSame(self::ECART, $this->solde());
    }

    /** Deux colis, deux fois l'écart : il s'accumule, colis par colis. */
    public function test_the_drift_accumulates_parcel_by_parcel(): void
    {
        $this->colisPartielAnnule('BL-A');
        $this->colisPartielAnnule('BL-B');

        $this->assertSame(2 * self::ECART, $this->solde());

        $this->artisan('beninlink:ecarts-marchands --corriger')->assertExitCode(0);

        $this->assertSame(0.0, $this->solde());
    }

    // ---- la correction ----------------------------------------------------

    /** `--corriger` réaligne le solde sur le relevé, et rien d'autre. */
    public function test_correcting_realigns_the_balance_on_the_statement(): void
    {
        $this->colisPartielAnnule();
        $lignes = MerchantStatement::count();

        $this->artisan('beninlink:ecarts-marchands --corriger')
            ->expectsOutputToContain('1 solde(s) réaligné(s)')
            ->assertExitCode(0);

        $this->assertSame(0.0, $this->solde());
        // Aucune ligne ajoutée : le relevé était juste, c'est le cache qui
        // avait dérivé. Lui écrire une ligne le rendrait faux à son tour.
        $this->assertSame($lignes, MerchantStatement::count());
    }

    /** Corriger deux fois ne fait rien la seconde. */
    public function test_correcting_is_idempotent(): void
    {
        $this->colisPartielAnnule();

        $this->artisan('beninlink:ecarts-marchands --corriger')->assertExitCode(0);
        $this->artisan('beninlink:ecarts-marchands --corriger')
            ->expectsOutputToContain('Aucun écart')
            ->assertExitCode(0);

        $this->assertSame(0.0, $this->solde());
    }

    /**
     * Le cœur de la prudence : un écart qui ne s'explique **pas** par les
     * annulations n'est pas corrigé. Il peut venir d'un retrait passé par une
     * passerelle en ligne — qui débite le solde sans écrire au relevé — ou
     * d'une fiche marchand ré-enregistrée avec un solde d'ouverture, qui
     * écrase le solde courant. Réaligner sur le relevé rendrait alors au
     * marchand un argent qui lui a bel et bien été versé.
     */
    public function test_an_unexplained_drift_is_never_corrected(): void
    {
        // Un débit sans ligne de relevé, comme le font les passerelles en ligne.
        $marchand = Merchant::find($this->marchand->id);
        $marchand->current_balance = -25000;
        $marchand->save();

        $this->artisan('beninlink:ecarts-marchands --corriger')
            ->expectsOutputToContain('Aucun écart n\'est entièrement expliqué')
            ->assertExitCode(1);

        $this->assertSame(-25000.0, $this->solde());
    }

    /**
     * Et un écart mixte — une partielle annulée **plus** un débit hors relevé —
     * n'est pas corrigé non plus : la commande ne rattrape que ce qu'elle sait
     * expliquer entièrement.
     */
    public function test_a_mixed_drift_is_left_alone(): void
    {
        $this->colisPartielAnnule();

        $marchand = Merchant::find($this->marchand->id);
        $marchand->current_balance = (float) $marchand->current_balance - 5000;
        $marchand->save();
        $solde = $this->solde();

        $this->artisan('beninlink:ecarts-marchands --corriger')->assertExitCode(1);

        $this->assertSame($solde, $this->solde());
    }

    /** Le solde d'ouverture fait partie du relevé : il ne crée pas d'écart. */
    public function test_an_opening_balance_is_not_a_drift(): void
    {
        $marchand = Merchant::find($this->marchand->id);
        $marchand->opening_balance = 75000;
        $marchand->current_balance = 75000;
        $marchand->save();

        $this->artisan('beninlink:ecarts-marchands')
            ->expectsOutputToContain('Aucun écart')
            ->assertExitCode(0);
    }

    /** `--marchand=` limite la portée à un compte. */
    public function test_it_can_be_limited_to_one_merchant(): void
    {
        $this->colisPartielAnnule();

        $voisin = $this->marchandDUneAutreSociete();
        $voisin->current_balance = 9999;
        $voisin->save();

        $this->artisan('beninlink:ecarts-marchands --corriger --marchand=' . $this->marchand->id)
            ->assertExitCode(0);

        $this->assertSame(0.0, $this->solde());
        $this->assertSame(9999.0, (float) Merchant::find($voisin->id)->current_balance);
    }

    /**
     * Le rapprochement lit le relevé, pas les colis : un mouvement écrit
     * normalement se retrouve des deux côtés et ne produit aucun écart.
     */
    public function test_a_normal_delivery_produces_no_drift(): void
    {
        $colis = $this->colisConfie($this->marchand, $this->livreur, 'BL-NORMAL');
        app(ParcelInterface::class)->parcelDelivered($colis->id, new Request());

        $this->assertSame(self::PAYABLE, $this->solde());

        $this->artisan('beninlink:ecarts-marchands')
            ->expectsOutputToContain('Aucun écart')
            ->assertExitCode(0);
    }
}
