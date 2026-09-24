<?php

namespace Tests\Feature;

use App\Enums\CustomsAlertStatus;
use App\Enums\CustomsLevel;
use App\Enums\ParcelStatus;
use App\Mail\MerchantFeedMail;
use App\Models\Backend\CustomsAlert;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\MerchantShops;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * Le canal COURRIEL du fil marchand.
 *
 * `.claude/rules/customs.md` demande « notification (email/push) » à la
 * création d'une alerte douanière. Le fil et le push étaient branchés ; le
 * courriel ne l'était pas — `MerchantNotification` portait `toPush()` et
 * `toDatabase()`, et son propre docbloc admettait que « l'e-mail n'a toujours
 * pas de gabarit ». Ce filet couvre la moitié qui manquait.
 *
 * Quatre propriétés, et chacune a son sabotage :
 *
 *  1. une alerte douanière ENVOIE un courriel ;
 *  2. un changement de statut de colis n'en envoie PAS — la sélectivité est un
 *     choix, pas un oubli ;
 *  3. le message porte la marque du transporteur DU DESTINATAIRE (**F4**) ;
 *  4. il part EN FILE (**D13**) et son échec ne renverse pas l'écriture métier.
 */
class MerchantFeedMailTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
        $this->merchant = Merchant::firstOrFail();
    }

    private function colis(string $tracking = 'BL-EXPORT-1'): Parcel
    {
        $parcel = new Parcel();
        $parcel->forceFill([
            'company_id' => $this->merchant->company_id,
            'merchant_id' => $this->merchant->id,
            'merchant_shop_id' => MerchantShops::firstOrFail()->id,
            'customer_name' => 'Aicha K.',
            'customer_phone' => '0022996000000',
            'customer_address' => 'Cotonou',
            'category_id' => 1,
            'delivery_type_id' => 1,
            'cash_collection' => 50000,
            'current_payable' => 49450,
            'tracking_id' => $tracking,
            'status' => ParcelStatus::PENDING,
        ])->save();

        return $parcel;
    }

    /** L'écriture réelle : c'est l'observer qui doit réagir, pas un appel direct. */
    private function alerteDouaniere(?Parcel $parcel = null): CustomsAlert
    {
        $parcel ??= $this->colis();

        return CustomsAlert::create([
            'company_id' => $this->merchant->company_id,
            'merchant_id' => $this->merchant->id,
            'parcel_id' => $parcel->id,
            'customs_rule_id' => null,
            'country_code' => 'NG',
            'country_name' => 'Nigeria',
            'goods_category' => 'textile',
            'level' => CustomsLevel::WARNING,
            'required_document' => "Certificat d'origine",
            'message' => "Certificat d'origine exigé.",
            'status' => CustomsAlertStatus::PENDING,
        ]);
    }

    /* ─────────────────────── 1. la propriété ───────────────────────────────── */

    public function test_une_alerte_douaniere_envoie_un_courriel_au_marchand(): void
    {
        Mail::fake();

        $parcel = $this->colis();
        $this->alerteDouaniere($parcel);

        $destinataire = $this->merchant->user->email;

        Mail::assertQueued(MerchantFeedMail::class, function (MerchantFeedMail $mail) use ($destinataire, $parcel) {
            $rendu = $mail->render();

            // `e()` et non la chaîne brute : Blade échappe l'apostrophe de
            // « d'origine » en `&#039;`. Chercher la forme non échappée ferait
            // échouer un gabarit pourtant juste.
            return $mail->hasTo($destinataire)
                && str_contains($mail->contenu['title'], 'Nigeria')
                && str_contains($rendu, e("Certificat d'origine exigé."))
                && str_contains($rendu, $parcel->tracking_id);
        });
    }

    /* ─────────────────────── 2. la sélectivité ─────────────────────────────── */

    /**
     * ⚠️ Ce test garde une DÉCISION, pas une absence de code.
     *
     * Il serait plus simple de mettre tout le fil à l'e-mail. Un marchand à
     * trente colis par jour recevrait alors une centaine de courriels par
     * semaine, les marquerait indésirables, et perdrait du même coup l'alerte
     * douanière — celle qui, elle, justifie le canal. La liste
     * `MerchantNotification::COURRIEL` est donc volontairement étroite, et
     * l'élargir doit faire tomber ce test.
     */
    public function test_un_changement_de_statut_de_colis_n_envoie_pas_de_courriel(): void
    {
        Mail::fake();

        $parcel = $this->colis('BL-LOCAL-1');
        $parcel->status = ParcelStatus::DELIVERED;
        $parcel->save();

        // Le fil, lui, est bien alimenté : c'est le CANAL qui est sélectif,
        // pas l'événement qui serait muet.
        $this->assertNotEmpty(
            $this->merchant->user->notifications()->get(),
            'sans entrée au fil, ce test ne prouverait que la panne de l’observer',
        );

        Mail::assertNothingQueued();
    }

    /* ─────────────────────── 3. F4 : la marque du destinataire ─────────────── */

    /**
     * ⚠️ LE test de ce lot.
     *
     * L'observer peut se déclencher hors requête (import Excel, commande), et
     * là `settings()` retombe sur la société 1. Le marchand semé appartient à
     * la société **2** : les deux réponses sont donc DISTINGUABLES, et c'est
     * exactement ce qui manquait à `MerchantSignup` avant sa correction.
     */
    public function test_le_courriel_porte_la_marque_du_transporteur_du_destinataire(): void
    {
        Mail::fake();

        // La prémisse, mesurée — sans elle le test ne prouverait rien.
        $ambiante = GeneralSettings::orderBy('id')->firstOrFail();
        $sienne = GeneralSettings::findOrFail($this->merchant->user->company_id);
        $this->assertNotSame((int) $ambiante->id, (int) $sienne->id,
            'le marchand doit appartenir à une AUTRE société que la société ambiante');
        $this->assertNotSame($ambiante->email, $sienne->email,
            'les deux sociétés doivent être distinguables par leur courriel de contact');

        $this->alerteDouaniere();

        Mail::assertQueued(MerchantFeedMail::class, function (MerchantFeedMail $mail) use ($ambiante, $sienne) {
            $rendu = $mail->render();

            return $mail->marque['nom'] === $sienne->name
                && str_contains($rendu, $sienne->email)
                && !str_contains($rendu, $ambiante->email);
        });
    }

    /* ─────────────────────── 4. D13 : en file, et tolérant ─────────────────── */

    public function test_le_courriel_part_en_file_et_non_dans_la_requete(): void
    {
        $this->assertTrue(
            (new \ReflectionClass(MerchantFeedMail::class))->implementsInterface(ShouldQueue::class),
            'D13 — un envoi sortant ne se fait pas dans la requête',
        );

        Mail::fake();
        $this->alerteDouaniere();

        Mail::assertQueued(MerchantFeedMail::class);
        Mail::assertNotSent(MerchantFeedMail::class);
    }

    public function test_un_marchand_sans_adresse_ne_declenche_aucun_envoi(): void
    {
        Mail::fake();

        $user = $this->merchant->user;
        $user->email = '';
        $user->save();

        $this->alerteDouaniere();

        Mail::assertNothingQueued();
        $this->assertNotEmpty($user->refresh()->notifications()->get(),
            'le fil reste alimenté : c’est l’ENVOI qui est impossible, pas l’événement');
    }

    /**
     * Un courriel raté ne renverse pas l'alerte — même règle que le push.
     * L'alerte douanière est la donnée ; le message n'en est que l'écho.
     *
     * ⚠️ CE TEST MESURE UNE PAIRE, PAS UNE GARDE. Deux `catch` couvrent ce
     * chemin : celui d'`EmailChannel` et celui de `MerchantFeed::deliver()`.
     * Mesuré : rendre le premier inopérant **seul** laisse ce test VERT — le
     * second tient. Il faut les neutraliser **ensemble** pour le voir mordre
     * (leçon de S64 : un sabotage partiel ne mesure que le survivant).
     *
     * Le `catch` du canal n'est donc pas ce qui sauve l'alerte ; ce qu'il
     * apporte est le journal qui NOMME le canal fautif, et l'indépendance du
     * canal si `deliver()` changeait un jour. On le garde en le sachant.
     */
    public function test_un_envoi_rate_ne_fait_pas_echouer_l_ecriture_de_l_alerte(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('serveur SMTP injoignable'));

        $alerte = $this->alerteDouaniere();

        $this->assertDatabaseHas('customs_alerts', ['id' => $alerte->id]);
    }
}
