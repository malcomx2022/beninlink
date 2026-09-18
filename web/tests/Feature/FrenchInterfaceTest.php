<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Services\Parcel\ParcelStage;
use Tests\TestCase;

/**
 * Lot 4 de la charte web (2026-09-18) — francisation de l'interface.
 *
 * La locale du produit est le **français** (`config/app.php`, et
 * `.claude/rules/i18n.md`). Le chantier 1 avait basculé l'essentiel, mais il
 * restait des poches, et la plus visible était la pire : **la page de connexion
 * était entièrement en anglais en dur** — la première page que voit une PME.
 *
 * Deux mécanismes distincts produisaient cet anglais, et le second était invisible
 * à la lecture des vues :
 *
 *  1. des chaînes écrites en dur (`Sign in`, `placeholder="Enter image"`) ;
 *  2. des appels `__('Phrase anglaise')` — des clés JSON — dont `lang/fr.json`
 *     ne portait **que 5 entrées** : les 45 autres retombaient sur la clé, donc
 *     sur l'anglais, sans que rien ne le signale.
 *
 * Ces tests fixent les deux, et font échouer la suite si de l'anglais revient.
 */
class FrenchInterfaceTest extends TestCase
{
    /**
     * Ce qui reste volontairement en anglais, et pourquoi. Tout le reste est un
     * défaut. Cette liste est la partie la plus utile du test : elle force à
     * justifier une exception plutôt qu'à la laisser passer.
     */
    private const EXCLUS = [
        // L'installeur n'utilise AUCUN `__()` : ce n'est pas de l'anglais résiduel
        // dans une application francisée, c'est un module non internationalisé.
        // L'interner demande un fichier de langue et la reprise de sept vues :
        // chantier propre, et il tourne une fois, chez l'intégrateur.
        'resources/views/installer/',
        // Deux vues d'exemple SSLCommerz : la passerelle est coupée (S21,
        // `config/payments.php`) et surtout **aucune route ne mène à ces vues**
        // (`exampleEasyCheckout` n'est pas routée). Les traduire serait du travail
        // jeté sur un formulaire d'adresse américain (« 1234 Main St »).
        'resources/views/backend/merchant_panel/sslecommerz/',
    ];

    /** Noms de marque : une traduction serait une faute, pas un progrès. */
    private const MARQUES = ['NEXMO SMS', 'TWILIO SMS', 'REVE SMS', 'Razorpay'];

    /** @return string[] chemins des vues à contrôler */
    private function vues(): array
    {
        $trouvees = [];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );
        foreach ($it as $f) {
            if (! $f->isFile() || ! str_ends_with($f->getFilename(), '.blade.php')) {
                continue;
            }
            $relatif = str_replace(base_path() . '/', '', $f->getPathname());
            foreach (self::EXCLUS as $exclu) {
                if (str_starts_with($relatif, $exclu)) {
                    continue 2;
                }
            }
            $trouvees[$relatif] = $f->getPathname();
        }

        return $trouvees;
    }

    // — les chaînes écrites en dur -------------------------------------------

    /**
     * Un `placeholder` est ce qu'on lit dans un champ vide : il ne peut pas rester
     * en anglais. 143 en portaient. Seule exception tolérée : `placeholder="TG"`,
     * un **code pays ISO** (Togo, `maxlength=2`) — le traduire le casserait.
     */
    public function test_no_view_carries_a_hardcoded_placeholder(): void
    {
        $fautes = [];
        foreach ($this->vues() as $relatif => $chemin) {
            preg_match_all('/placeholder="([^"{]*)"/', file_get_contents($chemin), $m);
            foreach ($m[1] as $valeur) {
                if ($valeur === '' || $valeur === 'TG') {
                    continue;
                }
                $fautes[] = "{$relatif} : placeholder=\"{$valeur}\"";
            }
        }

        $this->assertSame([], $fautes, "placeholders non traduits :\n" . implode("\n", $fautes));
    }

    /** Le titre de l'onglet est du texte lu ; il passe par une traduction. */
    public function test_every_page_title_goes_through_a_translation(): void
    {
        $fautes = [];
        foreach ($this->vues() as $relatif => $chemin) {
            foreach (file($chemin) as $no => $ligne) {
                if (str_contains($ligne, "@section('title',") && ! str_contains($ligne, '__(')) {
                    $fautes[] = $relatif . ':' . ($no + 1) . ' ' . trim($ligne);
                }
            }
        }

        $this->assertSame([], $fautes, "titres non traduits :\n" . implode("\n", $fautes));
    }

    // — les clés JSON, le mécanisme invisible --------------------------------

    /**
     * `__('Reset Password')` est une clé JSON : sans entrée dans `lang/fr.json`,
     * Laravel **rend la clé**, donc l'anglais, sans erreur ni avertissement.
     * C'est ce qui laissait 45 chaînes en anglais dans une application francisée.
     */
    public function test_every_english_json_key_used_in_a_view_is_translated(): void
    {
        $fr = json_decode(file_get_contents(lang_path('fr.json')), true);
        $this->assertIsArray($fr, 'lang/fr.json doit être un JSON valide');

        $manquantes = [];
        foreach ($this->vues() as $relatif => $chemin) {
            preg_match_all("/__\('([A-Z][^']{2,80})'\)/", file_get_contents($chemin), $m);
            foreach ($m[1] as $cle) {
                // Une clé à point est une clé de fichier (`levels.name`), pas du JSON.
                if (str_contains($cle, '.') || in_array($cle, self::MARQUES, true)) {
                    continue;
                }
                if (! array_key_exists($cle, $fr)) {
                    $manquantes[] = "{$relatif} : __('{$cle}')";
                }
            }
        }

        $this->assertSame(
            [],
            $manquantes,
            "clés JSON sans traduction française — elles s'affichent en anglais :\n"
                . implode("\n", $manquantes)
        );
    }

    /** Un nom de marque n'a PAS d'entrée : sans elle, Laravel rend la clé — voulu. */
    public function test_brand_names_are_deliberately_left_untranslated(): void
    {
        $fr = json_decode(file_get_contents(lang_path('fr.json')), true);

        foreach (self::MARQUES as $marque) {
            $this->assertArrayNotHasKey(
                $marque,
                $fr,
                "{$marque} est une marque : une entrée de traduction ressemblerait à une erreur"
            );
            $this->assertSame($marque, __($marque), 'la clé doit être rendue telle quelle');
        }
    }

    // — la devise ------------------------------------------------------------

    /**
     * Le socle libellait les montants en **taka bangladais**. Le produit est en
     * FCFA, et la devise est réglable par transporteur : la coder dans un fichier
     * de langue serait faux pour qui en choisit une autre.
     */
    public function test_no_french_or_english_label_mentions_the_bangladeshi_taka(): void
    {
        foreach (['fr', 'en'] as $locale) {
            foreach (glob(lang_path($locale . '/*.php')) as $fichier) {
                $this->assertDoesNotMatchRegularExpression(
                    '/\(\s*Tk\s*\)|\bBDT\b|\btaka\b/i',
                    file_get_contents($fichier),
                    basename($fichier) . " ({$locale}) mentionne une devise bangladaise"
                );
            }
        }

        // Les libellés effectivement servis.
        $this->assertSame('Montant', __('levels.amount'));
        $this->assertSame('Encaissement en espèces', __('parcel.cash_collection'));
    }

    // — les boîtes de dialogue -----------------------------------------------

    /**
     * Les confirmations passent par SweetAlert2, et le socle s'y trompait deux
     * fois : les textes étaient en dur, ET les **noms d'option** étaient faux.
     * `showOkButton` n'existe pas, et le bouton Annuler se règle par
     * `cancelButtonText`, pas `denyButtonText` : ces lignes ne faisaient rien, le
     * bouton gardait le « Cancel » par défaut de la bibliothèque. Traduire la
     * mauvaise option n'aurait rien changé à l'écran.
     */
    public function test_the_confirmation_dialogs_are_translated_and_use_real_options(): void
    {
        $scripts = [
            'public/backend/libs/js/custom.js',
            'public/backend/js/parcel/custom.js',
        ];

        foreach ($scripts as $relatif) {
            $source = file_get_contents(base_path($relatif));

            // Options inexistantes ou inopérantes.
            $this->assertStringNotContainsString('showOkButton', $source, $relatif);
            $this->assertStringNotContainsString('denyButtonText', $source, $relatif);

            // Plus de libellé de bouton en dur.
            $this->assertStringNotContainsString("confirmButtonText: 'Yes'", $source, $relatif);
            $this->assertStringNotContainsString("cancelButtonText: 'Cancel'", $source, $relatif);

            // Tout `text:` d'un dialogue vient d'une variable, jamais d'un littéral
            // anglais. (`title` est la valeur d'un data-title, traduite côté PHP.)
            $this->assertDoesNotMatchRegularExpression(
                "/text: '[A-Z][^']*'/",
                $source,
                "{$relatif} porte encore un message en dur"
            );
        }

        // Les trois variables que footer.blade.php doit injecter.
        $footer = file_get_contents(resource_path('views/backend/partials/footer.blade.php'));
        foreach (['yes', 'cancel', 'confirmUpdate', 'confirmCancel'] as $variable) {
            $this->assertMatchesRegularExpression(
                '/var ' . $variable . ' = /',
                $footer,
                "footer.blade.php doit définir {$variable}"
            );
        }
    }

    /**
     * La confirmation d'annulation nomme le statut **annulé**, pas l'action. Le
     * socle mettait ce nom en anglais dans un `data-title` (« pickup assign »).
     */
    public function test_a_cancellation_names_the_status_it_undoes_in_french(): void
    {
        $paires = [
            ParcelStatus::PICKUP_ASSIGN_CANCEL => ParcelStatus::PICKUP_ASSIGN,
            ParcelStatus::DELIVERED_CANCEL => ParcelStatus::DELIVERED,
            ParcelStatus::PARTIAL_DELIVERED_CANCEL => ParcelStatus::PARTIAL_DELIVERED,
            ParcelStatus::RECEIVED_WAREHOUSE_CANCEL => ParcelStatus::RECEIVED_WAREHOUSE,
        ];

        foreach ($paires as $annulation => $defait) {
            $this->assertSame($defait, ParcelStage::cancels($annulation), "code {$annulation}");
            $this->assertSame(
                trans('parcelStatus.' . $defait),
                ParcelStage::cancelledLabel($annulation),
                "code {$annulation}"
            );
        }

        // Un code qui n'annule rien garde son propre libellé.
        $this->assertNull(ParcelStage::cancels(ParcelStatus::PENDING));
        $this->assertSame(
            trans('parcelStatus.' . ParcelStatus::PENDING),
            ParcelStage::cancelledLabel(ParcelStatus::PENDING)
        );

        // Et le helper n'écrit plus d'anglais dans l'attribut.
        $helper = file_get_contents(base_path('app/Http/Helper/Helper.php'));
        $this->assertStringNotContainsString('data-title="pickup assign"', $helper);
        $this->assertStringContainsString('ParcelStage::cancelledLabel($key)', $helper);
    }

    // — la page d'entrée -----------------------------------------------------

    /** La page de connexion est la première du produit. Elle était en anglais. */
    public function test_the_login_page_has_no_english_left(): void
    {
        $source = file_get_contents(resource_path('views/auth/login.blade.php'));

        foreach ([
            'Please enter your user information.',
            '>Sign in</button>',
            '<b>OR</b>',
            '<b>Demo Login</b>',
            '>Sign up here</a>',
            '>Forgot Password</a>',
            "@section('title','Login')",
        ] as $anglais) {
            $this->assertStringNotContainsString($anglais, $source, $anglais);
        }

        $this->assertSame('Se connecter', __('auth.sign_in'));
        $this->assertSame('Mot de passe oublié ?', __('auth.forgot_password'));
    }

    // — parité des fichiers de langue ----------------------------------------

    /**
     * Une clé ajoutée en français et oubliée en anglais s'affiche… en français
     * pour un utilisateur anglophone, sans erreur. La parité se vérifie.
     */
    public function test_the_files_touched_keep_fr_en_parity(): void
    {
        foreach (['placeholder', 'auth', 'levels'] as $fichier) {
            $fr = require lang_path("fr/{$fichier}.php");
            $en = require lang_path("en/{$fichier}.php");

            $this->assertSame(
                [],
                array_diff(array_keys($fr), array_keys($en)),
                "clés présentes en fr et absentes en en — {$fichier}.php"
            );
        }
    }
}
