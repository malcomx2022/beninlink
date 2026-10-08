<?php

namespace Tests\Feature;

use Illuminate\Support\Arr;
use Tests\TestCase;

/**
 * **S112** — le catalogue français ne garde plus d'anglais.
 *
 * Après S111, le porteur a commencé la vérification visuelle du back-office (E5) : le
 * menu disait « Web avant » → « Partner ». Un relevé de `lang/fr/` a trouvé 208
 * entrées identiques à leur jumelle de `lang/en/`, dont deux fichiers jamais traduits :
 * `reports.php` (les écrans de rapports, 95 entrées) et `permissions.php` (l'écran des
 * rôles et des droits, 66 entrées), plus `status.php` (« Active » / « Inactive » sur
 * toutes les listes) et « Admin », « Hub », « Dashboard » ici et là.
 *
 * Ce filet tient le catalogue : une entrée française **identique** à l'anglaise n'est
 * admise que si sa valeur est dans `PERMISES` — un nom de marque (bKash, MTN MoMo,
 * Visa), un sigle (IFU, RCCM, MRR), ou un mot qui s'écrit de même dans les deux
 * langues (Action, Description, Signature). Ajouter une clé anglaise à `lang/fr/`,
 * c'est la traduire, ou dire ici pourquoi elle ne se traduit pas.
 */
class FrenchCatalogueTest extends TestCase
{
    /** Valeurs qui restent les mêmes en français : marques, sigles, mots identiques. */
    private const PERMISES = [
        // mots identiques en français
        'Action', 'Actions', 'Administration', 'Blog', 'Code', 'Contact', 'Date', 'Description', 'Express',
        'Fragile', 'image', 'Image', 'Info', 'Logo', 'Favicon', 'Menu', 'Message', 'Mobile', 'Modules', 'Note',
        'Notifications', 'Original', 'Pages', 'Permissions', 'Plan', 'Position', 'Question', 'Section', 'Sections',
        'Service', 'Services', 'Signature', 'Slug', 'Source', 'Ticket', 'Total', 'Type', 'Version', 'Zone', 'Zones',
        'Email', 'Net',
        // marques et noms propres
        'Aamarpay', 'BeninLink', 'Bkash', 'bKash', 'Dhaka', 'Facebook', 'Google', 'Mobile Money (FedaPay)',
        'Moov Money', 'MTN MoMo', 'Nagad', 'Payoneer', 'Paypal', 'Razorpay', 'Rocket', 'Skrill', 'SSL Commerz',
        'SSLCommerz', 'Stripe', 'Visa',
        // sigles et formats
        'ARR', 'CAC', 'CSV', 'Excel', 'FAQ', 'IFU', 'LTV', 'MRR', 'NID', 'PDF', 'RCCM',
        // gabarits sans mot à traduire
        ':customer — :address', ':name — IFU :ifu — RCCM :rccm — :address',
    ];

    /** @return array<string, array{0: string, 1: string}> fichier => [chemin fr, chemin en] */
    private function paires(): array
    {
        $paires = [];
        foreach (glob(lang_path('en/*.php')) as $anglais) {
            $paires[basename($anglais)] = [lang_path('fr/' . basename($anglais)), $anglais];
        }

        return $paires;
    }

    public function test_every_english_file_has_its_french_twin(): void
    {
        $paires = $this->paires();
        $this->assertGreaterThan(80, count($paires));
        foreach ($paires as $fichier => [$francais]) {
            $this->assertFileExists($francais, "lang/fr/{$fichier}");
        }
    }

    public function test_no_french_entry_is_left_in_english(): void
    {
        $restes = [];
        foreach ($this->paires() as $fichier => [$francais, $anglais]) {
            $fr = Arr::dot(require $francais);
            $en = Arr::dot(require $anglais);
            foreach ($fr as $cle => $valeur) {
                if (! is_string($valeur) || ! isset($en[$cle]) || $valeur !== $en[$cle]) {
                    continue;
                }
                if (! preg_match('/[a-z]{3,}/i', $valeur) || in_array($valeur, self::PERMISES, true)) {
                    continue;
                }
                $restes[] = "{$fichier} : {$cle} => {$valeur}";
            }
        }

        $this->assertSame([], $restes, "entrées de lang/fr/ restées en anglais :\n" . implode("\n", $restes));
    }

    /** Les libellés que le porteur a vus, et ceux de toutes les listes. */
    public function test_the_labels_seen_on_screen_read_in_french(): void
    {
        app()->setLocale('fr');

        $this->assertSame('Site public', __('levels.front_web'));
        $this->assertSame('Partenaire', __('levels.partner'));
        $this->assertSame('Actif', __('status.' . \App\Enums\Status::ACTIVE));
        $this->assertSame('Inactif', __('status.' . \App\Enums\Status::INACTIVE));
        $this->assertSame('Rapports', __('reports.title'));
        $this->assertSame('Tableau de bord', __('permissions.dashboard'));
        $this->assertSame('Agence', __('userType.' . \App\Enums\UserType::HUB));
    }
}
