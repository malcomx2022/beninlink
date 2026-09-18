<?php

namespace Tests\Feature;

use App\Services\Sms\SmsTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\Concerns\SeedsTenant;
use Tests\TestCase;

/**
 * Les SMS sortants — le seul texte du produit qui atteint quelqu'un qui n'a
 * jamais ouvert le back-office.
 *
 * Trois défauts du socle sont fermés ici, et ces tests les gardent fermés :
 *
 *   1. la langue du message était celle de **l'agent qui clique** ;
 *   2. la marque et la devise venaient de `settings()`, donc de la société 1
 *      hors requête locataire (constat F4) ;
 *   3. les montants étaient en **taka**, ou sans devise du tout.
 *
 * S'y ajoute une contrainte propre au canal, que rien ne signalait : un SMS
 * tient 160 caractères en alphabet GSM 03.38 et **70** dès qu'un caractère en
 * sort. Écrire « entrepôt » au lieu de « centre de tri » double la facture.
 */
class SmsMessagesTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTenant;

    /** Les seuls fichiers autorisés à composer un SMS. */
    private const COMPOSITEURS = [
        'app/Repositories/Parcel/ParcelRepository.php',
        'app/Repositories/Wallet/WalletRepository.php',
        'app/Http/Services/SmsService.php',
    ];

    /* ─────────────────────────── Les gabarits ─────────────────────────── */

    /** Les deux langues servies déclarent exactement les mêmes clés. */
    public function test_both_served_languages_declare_the_same_keys(): void
    {
        $fr = array_keys($this->gabarits('fr'));
        $en = array_keys($this->gabarits('en'));

        sort($fr);
        sort($en);

        $this->assertSame($fr, $en, 'lang/fr/sms.php et lang/en/sms.php divergent');
        $this->assertNotEmpty($fr);
    }

    /** Toute clé rendue par le code existe dans les deux fichiers. */
    public function test_every_key_used_by_the_code_is_translated(): void
    {
        $fr = $this->gabarits('fr');
        $en = $this->gabarits('en');

        foreach ($this->appels() as $appel) {
            $this->assertArrayHasKey($appel['cle'], $fr, "sms.{$appel['cle']} absent de lang/fr");
            $this->assertArrayHasKey($appel['cle'], $en, "sms.{$appel['cle']} absent de lang/en");
        }
    }

    /** Et réciproquement : aucun gabarit mort. */
    public function test_every_declared_template_is_used(): void
    {
        $utilisees = array_unique(array_column($this->appels(), 'cle'));

        foreach (array_keys($this->gabarits('fr')) as $cle) {
            $this->assertContains($cle, $utilisees, "gabarit sms.{$cle} déclaré mais jamais rendu");
        }
    }

    /**
     * Le test qui coûte de l'argent s'il tombe.
     *
     * Un seul caractère hors de l'alphabet GSM 03.38 fait passer le message en
     * UCS-2 : 70 caractères utiles au lieu de 160, donc deux SMS facturés là où
     * un suffisait. L'alphabet accepte è é ù ì ò à ä ö ü ñ, É et Ç majuscules ;
     * il refuse ê â î ô û ë ï, le ç minuscule, l'apostrophe courbe, le tiret
     * demi-cadratin et l'espace insécable.
     *
     * Les valeurs injectées (noms, raisons sociales) ne sont pas concernées :
     * elles viennent de la base et on ne les maîtrise pas. Le gabarit, si.
     */
    public function test_no_template_leaves_the_gsm7_alphabet(): void
    {
        foreach (['fr', 'en'] as $langue) {
            foreach ($this->gabarits($langue) as $cle => $texte) {
                $fautifs = $this->horsGsm7($texte);
                $this->assertSame(
                    [],
                    $fautifs,
                    "lang/{$langue}/sms.php · {$cle} : caractère(s) hors GSM-7 " . implode(' ', $fautifs)
                    . " — le SMS bascule en UCS-2 et coûte le double.",
                );
            }
        }
    }

    /** Le garde-fou du garde-fou : le détecteur voit bien ce qu'il doit voir. */
    public function test_the_gsm7_check_catches_what_it_must(): void
    {
        $this->assertSame(['ê'], $this->horsGsm7('Colis prêt'));
        $this->assertSame(['ô'], $this->horsGsm7('En entrepôt'));
        $this->assertSame(['ç'], $this->horsGsm7('Colis reçu'));
        $this->assertSame(['’'], $this->horsGsm7("aujourd’hui"));
        $this->assertSame(["\u{00A0}"], $this->horsGsm7("1\u{00A0}500 FCFA"));
        $this->assertSame([], $this->horsGsm7('Colis livré à Cotonou, réglé.'));
    }

    /**
     * Chaque point d'appel fournit exactement les valeurs que son gabarit
     * attend. Une faute de frappe (`:phone` contre `:agent_phone`) laisserait
     * le marqueur brut dans le SMS reçu par le client, et rien ne le dirait :
     * `trans()` ne se plaint pas d'un marqueur non remplacé.
     */
    public function test_every_call_site_supplies_the_placeholders_its_template_needs(): void
    {
        $gabarits = $this->gabarits('fr');

        foreach ($this->appels() as $appel) {
            $attendus = $this->marqueurs($gabarits[$appel['cle']]);

            // `render()` fournit `:brand` d'office pour tous les appelants.
            $fournis = array_unique(array_merge($appel['params'], ['brand']));

            sort($attendus);
            sort($fournis);

            $this->assertSame(
                $attendus,
                $fournis,
                "{$appel['fichier']} ligne {$appel['ligne']} · sms.{$appel['cle']} : "
                . 'attendus ' . implode(', ', $attendus) . ' / fournis ' . implode(', ', $fournis),
            );
        }
    }

    /* ──────────────────────── La langue du message ─────────────────────── */

    /**
     * Le cœur de la correction : un agent qui travaille en anglais n'envoie
     * pas pour autant des SMS anglais à un client béninois.
     */
    public function test_the_agent_browsing_in_english_does_not_change_the_recipients_language(): void
    {
        // `App::setLocale()` est ce que fait `LanguageManager` quand la session
        // porte une langue — et il ECRIT dans `config('app.locale')` au passage.
        // C'est ce piege qui a fait tomber le premier jet de `SmsTemplate`.
        App::setLocale('en');
        session(['locale' => 'en']);

        $this->assertSame('en', config('app.locale'), 'le piege doit bien etre arme');

        $message = SmsTemplate::current()->render('delivered_customer', [
            'customer' => 'Aicha',
            'tracking' => 'BL-1',
            'url' => 'https://exemple.bj',
        ]);

        $this->assertStringContainsString('votre colis', $message);
        $this->assertStringNotContainsString('your parcel', $message);
    }

    /** Et une installation qui bascule sa langue par défaut, elle, suit. */
    public function test_an_installation_configured_in_english_sends_english(): void
    {
        config(['locales.default' => 'en']);

        $message = SmsTemplate::current()->render('delivered_customer', [
            'customer' => 'Aicha',
            'tracking' => 'BL-1',
            'url' => 'https://exemple.bj',
        ]);

        $this->assertStringContainsString('your parcel', $message);
    }

    /** Plus aucun fichier ne choisit la langue d'un SMS depuis la session. */
    public function test_no_composer_reads_the_session_to_choose_a_language(): void
    {
        foreach (self::COMPOSITEURS as $fichier) {
            $source = file_get_contents(base_path($fichier));
            $this->assertStringNotContainsString("session()->get('locale')", $source, $fichier);
            $this->assertStringNotContainsString("app()->getLocale()", $source, $fichier);
        }

        // Et le service prend la langue de l'installation, pas celle de la
        // requête en cours. `config('app.locale')` ne convient pas non plus :
        // `Application::setLocale()` y écrit, et `LanguageManager` l'appelle.
        $service = file_get_contents(base_path('app/Services/Sms/SmsTemplate.php'));
        $this->assertStringContainsString("config('locales.default'", $service);
        $this->assertStringNotContainsString("return (string) config('app.locale'", $service);
    }

    /* ──────────────────── Le texte, la marque, la devise ───────────────── */

    /**
     * Aucun message n'est plus écrit au point d'appel : tout `$msg` passe par
     * le service. C'est ce qui garantit qu'une phrase ajoutée demain sera
     * traduite, et non recopiée en anglais comme les vingt-trois précédentes.
     */
    public function test_no_message_is_still_written_at_the_call_site(): void
    {
        foreach (self::COMPOSITEURS as $fichier) {
            $source = file_get_contents(base_path($fichier));

            foreach (explode("\n", $source) as $numero => $ligne) {
                // Une affectation de message qui ne porte AUCUN littéral n'est
                // pas une composition (`$message = $response->current();` dans
                // le pilote Nexmo, par exemple).
                if (!preg_match('/\$(msg|message)\s*=\s*[^=].*[\'"]/', $ligne)) {
                    continue;
                }

                $this->assertMatchesRegularExpression(
                    '/\$(msg|message)\s*=\s*(SmsTemplate::|\$sms->)/',
                    trim($ligne),
                    "{$fichier} ligne " . ($numero + 1) . " : message composé sur place",
                );
            }
        }
    }

    /** Et personne d'autre que ces trois-là ne compose de SMS. */
    public function test_only_the_declared_files_compose_an_sms(): void
    {
        $trouves = [];
        foreach ($this->fichiersPhp(app_path()) as $chemin) {
            $source = file_get_contents($chemin);
            if (str_contains($source, 'SmsTemplate::')) {
                $trouves[] = ltrim(str_replace(base_path(), '', $chemin), '/');
            }
        }

        sort($trouves);
        $attendus = self::COMPOSITEURS;
        sort($attendus);

        $this->assertSame($attendus, $trouves);
    }

    /** Plus un taka, ni un montant nu. */
    public function test_no_amount_is_sent_in_taka_or_without_a_currency(): void
    {
        foreach (self::COMPOSITEURS as $fichier) {
            $source = file_get_contents(base_path($fichier));
            $this->assertStringNotContainsString('TK(', $source, $fichier);
            $this->assertDoesNotMatchRegularExpression('/\x{0985}-\x{09FF}/u', $source, $fichier);
        }

        // Tout gabarit qui parle d'un montant le reçoit par `:amount`, qui
        // porte toujours la devise.
        foreach ($this->appels() as $appel) {
            if (!in_array('amount', $appel['params'], true)) {
                continue;
            }
            $this->assertStringContainsString(
                '$sms->amount(',
                $appel['brut'],
                "{$appel['fichier']} ligne {$appel['ligne']} : montant non formaté",
            );
        }
    }

    /**
     * Le montant d'un SMS : entier, devise suffixée, séparateur de milliers en
     * espace **ordinaire**. `formatAmount()` sépare par une espace insécable,
     * juste à l'écran mais hors alphabet GSM-7.
     */
    public function test_an_amount_is_an_integer_with_its_currency_and_no_unbreakable_space(): void
    {
        $this->seedTenant();

        $montant = SmsTemplate::forCompany(1)->amount(12500.4);

        $this->assertSame('12 500 FCFA', $montant);
        $this->assertStringNotContainsString("\u{00A0}", $montant);
        $this->assertSame([], $this->horsGsm7($montant));
        $this->assertStringNotContainsString(',', $montant, 'le XOF ne porte pas de décimales');
    }

    /**
     * F4 — la marque et la devise viennent de la société de l'objet métier,
     * jamais de `settings()`. Hors requête locataire (webhook FedaPay, commande
     * console), `settings()` retombe sur la société 1 : le marchand recevait un
     * SMS signé d'un autre transporteur.
     */
    public function test_the_brand_and_the_currency_follow_the_company_of_the_record(): void
    {
        $this->seedTenant();

        \App\Models\Backend\GeneralSettings::where('id', 2)
            ->update(['name' => 'Transporteur Deux', 'currency' => 'XOF']);

        $message = SmsTemplate::forCompany(2)->render('wallet_recharged', [
            'merchant' => 'Boutique Lafia',
            'amount' => SmsTemplate::forCompany(2)->amount(5000),
        ]);

        $this->assertStringContainsString('Transporteur Deux', $message);
        $this->assertStringContainsString('5 000 XOF', $message);
        $this->assertStringNotContainsString('We Courier', $message);
    }

    /**
     * Un message rendu avec des données béninoises réalistes tient en deux SMS.
     * Au-delà, le transporteur paie trois fois pour un changement de statut.
     */
    public function test_a_rendered_message_never_exceeds_two_sms_parts(): void
    {
        $exemple = [
            'customer' => 'Aicha Dossou',
            'merchant' => 'Boutique Lafia Cotonou',
            'tracking' => 'BL-2026-000123',
            'agent' => 'Koffi Adjovi',
            'phone' => '+22997000000',
            'agent_phone' => '+22997000000',
            'address' => 'Carre 1234, Akpakpa, Cotonou',
            'date' => '18/09/2026',
            'hub' => 'Cotonou Centre',
            'url' => 'https://beninlink.bj',
            'amount' => '12 500 FCFA',
            'reference' => 'FDP-2026-000999',
            'code' => '48211',
            'brand' => 'BeninLink',
        ];

        foreach ($this->gabarits('fr') as $cle => $gabarit) {
            // Du marqueur le plus long au plus court : sinon `:agent` mange le
            // début de `:agent_phone`. Laravel fait exactement ce tri.
            $valeurs = $exemple;
            uksort($valeurs, fn ($a, $b) => strlen($b) <=> strlen($a));

            $rendu = $gabarit;
            foreach ($valeurs as $marqueur => $valeur) {
                $rendu = str_replace(':' . $marqueur, $valeur, $rendu);
            }

            $this->assertDoesNotMatchRegularExpression(
                '/:[a-z_]{3,}/',
                $rendu,
                "marqueur non remplacé dans {$cle} : {$rendu}",
            );
            $this->assertLessThanOrEqual(
                306,
                mb_strlen($rendu),
                "sms.{$cle} : " . mb_strlen($rendu) . ' caractères, soit plus de deux SMS',
            );
        }
    }

    /** Le code de vérification n'est plus la seule phrase anglaise du socle. */
    public function test_the_verification_code_message_is_translated(): void
    {
        $source = file_get_contents(base_path('app/Http/Services/SmsService.php'));

        $this->assertStringNotContainsString("' is your '", $source);
        $this->assertStringContainsString("render('otp'", $source);
        $this->assertStringContainsString(
            'code de vérification',
            trans('sms.otp', ['code' => '1', 'brand' => 'X'], 'fr'),
        );
    }

    /* ────────────────────────────── Outils ─────────────────────────────── */

    /** @return array<string,string> */
    private function gabarits(string $langue): array
    {
        return require base_path("lang/{$langue}/sms.php");
    }

    /** Les marqueurs `:xxx` d'un gabarit. @return string[] */
    private function marqueurs(string $gabarit): array
    {
        preg_match_all('/:([a-z_]+)/', $gabarit, $trouves);

        return array_values(array_unique($trouves[1]));
    }

    /**
     * Tous les appels `render('cle', [...])` du code, avec les paramètres
     * fournis. @return array<int,array{cle:string,params:string[],fichier:string,ligne:int,brut:string}>
     */
    private function appels(): array
    {
        $appels = [];

        foreach (self::COMPOSITEURS as $fichier) {
            $source = file_get_contents(base_path($fichier));
            preg_match_all("/render\('([a-z_]+)',\s*\[(.*?)\]\)/s", $source, $trouves, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

            foreach ($trouves as $t) {
                preg_match_all("/'([a-z_]+)'\s*=>/", $t[2][0], $params);
                $appels[] = [
                    'cle' => $t[1][0],
                    'params' => $params[1],
                    'fichier' => $fichier,
                    'ligne' => substr_count(substr($source, 0, $t[0][1]), "\n") + 1,
                    'brut' => $t[0][0],
                ];
            }
        }

        $this->assertNotEmpty($appels, 'aucun appel à SmsTemplate::render() trouvé');

        return $appels;
    }

    /** Les caractères d'un texte qui sortent de l'alphabet GSM 03.38. @return string[] */
    private function horsGsm7(string $texte): array
    {
        $alphabet = '@£$¥èéùìòÇ' . "\n" . 'Øø' . "\r" . 'ÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !"#¤%&\'()*+,-./0123456789'
            . ':;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà'
            . '^{}\\[~]|€'; // les dix caractères d'échappement, facturés deux septets

        $autorises = array_flip(preg_split('//u', $alphabet, -1, PREG_SPLIT_NO_EMPTY));
        $fautifs = [];

        foreach (preg_split('//u', $texte, -1, PREG_SPLIT_NO_EMPTY) as $caractere) {
            if (!isset($autorises[$caractere]) && !in_array($caractere, $fautifs, true)) {
                $fautifs[] = $caractere;
            }
        }

        return $fautifs;
    }

    /** @return string[] */
    private function fichiersPhp(string $racine): array
    {
        $fichiers = [];
        $iterateur = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($racine));

        foreach ($iterateur as $fichier) {
            if ($fichier->isFile() && $fichier->getExtension() === 'php') {
                $fichiers[] = $fichier->getPathname();
            }
        }

        return $fichiers;
    }
}
