<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * S65 — le HUITIÈME filet : la lecture nue d'un identifiant de requête.
 *
 * ```php
 * Model::find($request->x)      // au lieu de Model::companywise()->find(...)
 * ```
 *
 * C'est la famille la plus ancienne du chantier : S15, S30, S45 à S53 l'ont
 * chassée route par route. **S47** avait relevé « ~250 occurrences » et jugé
 * l'outil trop bruyant pour décider ; **S57** l'a affiné (570 → 43) ; **S59**
 * l'a rendu honnête (position, même identifiant, cartes des aides) ; **S64**
 * en a fermé neuf, dont un virement qui sortait de l'argent d'un compte d'en
 * face.
 *
 * Il restait un **script**. Un script se lance ; un filet mord tout seul. Ce
 * fichier fait du second ce que `docs/outils/instrument-lectures-nues.py` était
 * déjà, et il exige que **chaque** occurrence porte sa raison.
 *
 * ## Ce que l'analyse exige d'une garde
 *
 * | critère | pourquoi |
 * |---|---|
 * | **position** | une garde en aval n'a rien empêché |
 * | **même identifiant** | une garde sur A ne protège pas B — la famille S45→S53 |
 * | **carte des aides** | `contrepartieHorsPerimetre($request)` ne nomme aucun champ : sa carte est lue dans `app/Traits/` |
 *
 * ⚠️ **Ce filet ne dit pas qu'une occurrence est une faille.** Il dit qu'aucune
 * ne peut être ajoutée sans qu'on ait écrit ce qu'il advient d'un identifiant
 * étranger.
 */
class NakedReadCoverageTest extends TestCase
{
    private const RACINES = ['app/Http', 'app/Repositories', 'app/Services'];

    /**
     * Les passerelles de paiement en ligne : le module est **coupé**.
     *
     * `config('payments.online_payout')` vaut `false` (D10), et
     * `OnlinePayoutModuleDisabledTest` établit que **chacune** de leurs routes
     * est derrière `if (onlinePayoutEnabled())` — donc non enregistrée.
     *
     * ⚠️ Ces lectures sont **réellement nues**. Elles ne sont pas exemptées
     * parce qu'elles seraient correctes, mais parce qu'elles sont
     * **inatteignables**. Le jour où le module rouvre, elles redeviennent des
     * failles : c'est écrit dans `config/payments.php`, qui dit déjà « corriger
     * les lectures nues avant de repasser le drapeau à `true` ».
     */
    private const DERRIERE_UN_MODULE_COUPE = [
        'AamarpayController::payment',
        'AdminAamarpayController::payment',
        'AdminBkashController::bkashExecute',
        'AdminSkrillController::makePayment',
        'AdminSkrillController::storePayment',
        'BkashController::bkashExecute',
        'OnlinePaymentController::stripePost',
        'OnlinePaymentController::paypalpayment',
        'PayoutController::stripePost',
        'PayoutController::razorpayPost',
        'PayoutController::paypalpayment',
        'SkrillController::storePayment',
    ];

    /** Les flux d'authentification, globaux par nature. */
    private const FLUX_GLOBAUX = [
        'MerchantRepository::otpVerification' => 'un code à usage unique se vérifie sur le NUMÉRO qui l\'a demandé, avant toute session : il n\'y a pas encore de société. Le périmètre viendra du compte trouvé, pas de l\'ambiance',
        'CompanyRepository::otpVerification' => 'idem, sur l\'e-mail, à l\'inscription d\'une société — par définition avant qu\'elle existe',
        'CompanyRepository::resendOTP' => 'idem — renvoi du même code, même flux',
    ];

    /** Surface super-administrateur : il administre les sociétés, il n'est pas dans l'une d'elles. */
    private const SUPER_ADMIN = [
        'CompanyRepository::switchPlan' => 'changement de plan d\'une société : acte de PLATEFORME. Le `user_id` désigne le propriétaire de la société administrée',
    ];

    /**
     * Sûr **par l'ordre** : une garde placée plus tôt dans la chaîne d'appel a
     * déjà refusé quand cette lecture s'exécute.
     *
     * ⚠️ C'est la catégorie la plus fragile du filet : elle dépend d'un ordre
     * d'appel, pas d'une garde sur place. Une réorganisation du contrôleur la
     * casse en silence. Chaque ligne nomme donc **où** vit la garde qui protège.
     */
    private const SUR_PAR_ORDRE = [
        'ParcelController::transferToHubMultipleParcel' => 'la relecture `Hub::find($request->hub_id)` suit `$this->repo->transferToHubMultipleParcel($request)`, qui commence par `if (blank(Hub::companywise()->find($request->hub_id))) return false` (S45). Le contrôleur ne relit le hub que si le dépôt l\'a accepté',
    ];

    /* ────────────────────────────── l'analyse ───────────────────────────── */

    private const MODELES = ['Parcel', 'Merchant', 'DeliveryMan', 'Hub', 'Account', 'User',
        'Income', 'Expense', 'Salary', 'SalaryGenerate', 'Payment', 'HubPayment', 'FundTransfer',
        'BankTransaction', 'Invoice', 'Wallet', 'CashReceivedFromDeliveryman', 'CustomsAlert', 'Config'];

    private const GARDES = ['companywise(', 'contrepartieHorsPerimetre(', 'identifiantsHorsPerimetre(',
        'catalogueHorsPerimetre(', 'ticketsVisibles(', 'marchandDeLaSociete(', 'abort_if(', 'abort_unless('];

    private function sansCommentaires(string $source): string
    {
        $sortie = '';

        foreach (token_get_all($source) as $jeton) {
            if (is_array($jeton)) {
                $sortie .= in_array($jeton[0], [T_COMMENT, T_DOC_COMMENT], true)
                    ? str_repeat(' ', strlen($jeton[1]))
                    : $jeton[1];
            } else {
                $sortie .= $jeton;
            }
        }

        return $sortie;
    }

    /** Les champs que les aides de garde du projet couvrent, LUS dans les traits. */
    private function champsDesAides(): array
    {
        static $champs = null;

        if ($champs !== null) {
            return $champs;
        }

        $champs = [];

        foreach (glob(base_path('app/Traits/*.php')) as $fichier) {
            if (preg_match_all('/\'(\w+)\'\s*=>\s*\w+::class/', file_get_contents($fichier), $m)) {
                $champs = array_merge($champs, $m[1]);
            }
        }

        return $champs = array_unique($champs);
    }

    private function methodes(string $source): array
    {
        $res = [];

        if (!preg_match_all('/function\s+(\w+)\s*\([^)]*\)[^{;]*\{/', $source, $m, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            return $res;
        }

        foreach ($m as $t) {
            $i = $t[0][1] + strlen($t[0][0]) - 1;
            $prof = 0;

            for ($j = $i, $n = strlen($source); $j < $n; $j++) {
                if ($source[$j] === '{') {
                    $prof++;
                } elseif ($source[$j] === '}' && --$prof === 0) {
                    $res[] = [$t[1][0], substr($source, $i, $j - $i)];
                    break;
                }
            }
        }

        return $res;
    }

    /** @return array<string,string> `Classe::methode` => extrait */
    public function lecturesNues(string $source, string $classe): array
    {
        $motif = '/\b(' . implode('|', self::MODELES) . ')::(find|where|firstWhere)\s*\(\s*([^;)]{0,140})/';
        $aide = '/\w*HorsPerimetre\s*\(/';
        $trouves = [];

        foreach ($this->methodes($this->sansCommentaires($source)) as [$nom, $corps]) {
            $gardes = [];

            foreach (self::GARDES as $g) {
                $pos = strpos($corps, $g);
                if ($pos !== false) {
                    $gardes[] = $pos;
                }
            }

            if (!preg_match_all($motif, $corps, $m, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
                continue;
            }

            foreach ($m as $l) {
                $arg = $l[3][0];

                if (!preg_match('/(?:\$request|request\(\))->(\w+)/', $arg, $c) || str_contains($arg, 'company_id')) {
                    continue;
                }

                $protegee = false;

                foreach ($gardes as $g) {
                    if ($g >= $l[0][1]) {
                        continue;
                    }

                    $entre = substr($corps, $g, $l[0][1] - $g);

                    if (str_contains($entre, $c[1])
                        || (in_array($c[1], $this->champsDesAides(), true) && preg_match($aide, $entre))) {
                        $protegee = true;
                        break;
                    }
                }

                if (!$protegee) {
                    $trouves[$classe . '::' . $nom] = trim($l[1][0] . '::' . $l[2][0] . '(' . $arg);
                }
            }
        }

        return $trouves;
    }

    private function toutesLesOccurrences(): array
    {
        $trouves = [];

        foreach (self::RACINES as $racine) {
            $chemin = base_path($racine);

            if (!is_dir($chemin)) {
                continue;
            }

            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($chemin)) as $f) {
                if ($f->getExtension() === 'php') {
                    $trouves += $this->lecturesNues(file_get_contents($f->getPathname()), $f->getBasename('.php'));
                }
            }
        }

        return $trouves;
    }

    private function classees(): array
    {
        return array_merge(
            self::DERRIERE_UN_MODULE_COUPE,
            array_keys(self::FLUX_GLOBAUX),
            array_keys(self::SUPER_ADMIN),
            array_keys(self::SUR_PAR_ORDRE),
        );
    }

    /* ─────────────── 1. tout est classé : rien de neuf ne passe ─────────── */

    public function test_every_naked_read_is_accounted_for(): void
    {
        $trouves = $this->toutesLesOccurrences();

        $nonClassees = array_values(array_diff(array_keys($trouves), $this->classees()));
        sort($nonClassees);

        $detail = array_map(fn ($c) => $c . '   →   ' . $trouves[$c], $nonClassees);

        $this->assertSame([], $nonClassees, "Un identifiant de requête est lu SANS périmètre, et aucune garde "
            . "posée avant lui ne porte sur ce champ. Soit la lecture prend sa portée "
            . "(`Model::companywise()->find(...)` ou une aide dont la carte couvre le champ), soit elle "
            . "s'inscrit ci-dessus avec sa raison :\n - " . implode("\n - ", $detail));
    }

    /** Et l'inverse : une déclaration qui ne correspond plus à rien s'en va. */
    public function test_no_declaration_points_to_a_vanished_read(): void
    {
        $connues = array_keys($this->toutesLesOccurrences());
        $fantomes = array_values(array_diff($this->classees(), $connues));

        $this->assertSame([], $fantomes, "Une déclaration ne correspond plus à aucune lecture nue — elle a pris "
            . "sa portée, ou la méthode a disparu :\n - " . implode("\n - ", $fantomes));
    }

    /* ─────────────── 2. le filet voit-il encore quelque chose ? ──────────── */

    public function test_the_net_still_finds_the_known_reads(): void
    {
        $this->assertGreaterThan(15, count($this->toutesLesOccurrences()),
            'le filet ne retrouve plus les lectures connues : son analyse est cassée');
    }

    /**
     * Le témoin de DÉTECTION — les quatre formes côte à côte.
     *
     * Il exige que l'analyse voie la lecture nue et **ignore** les trois qui ne
     * le sont pas. Une analyse qui signalerait tout passerait pour fonctionnelle
     * tout en étant inutilisable ; une qui ne signalerait rien passerait pour
     * rassurante.
     */
    public function test_the_analysis_tells_a_naked_read_from_a_guarded_one(): void
    {
        $source = <<<'PHP'
        <?php
        class Temoin {
            public function nue($request) {
                return Merchant::find($request->merchant_id);
            }
            public function portee($request) {
                return Merchant::companywise()->find($request->merchant_id);
            }
            public function gardeAvant($request) {
                if (blank(Merchant::companywise()->find($request->merchant_id))) { return false; }
                return Merchant::find($request->merchant_id);
            }
            public function gardeApres($request) {
                $m = Merchant::find($request->merchant_id);
                abort_if(blank(Merchant::companywise()->find($request->merchant_id)), 404);
                return $m;
            }
        }
        PHP;

        $trouves = array_keys($this->lecturesNues($source, 'Temoin'));

        $this->assertContains('Temoin::nue', $trouves, 'l\'analyse ne voit plus une lecture nue');
        $this->assertNotContains('Temoin::portee', $trouves, 'l\'analyse signale une lecture pourtant scopée');
        $this->assertNotContains('Temoin::gardeAvant', $trouves,
            'l\'analyse ignore une garde posée AVANT la lecture, sur le MÊME champ');

        $this->assertContains('Temoin::gardeApres', $trouves,
            'l\'analyse absout une garde posée APRÈS la lecture : elle n\'a pourtant rien empêché — '
            . 'c\'est le resserrement de S59');
    }

    /**
     * La carte des aides doit rester lue, pas devinée.
     *
     * `contrepartieHorsPerimetre($request)` ne contient le nom d'aucun champ :
     * sans la carte, huit lectures pourtant protégées seraient signalées (mesuré
     * en S59). La carte vit dans `app/Traits/` et l'analyse la lit.
     */
    public function test_the_helper_maps_are_read_not_guessed(): void
    {
        $this->assertContains('account_id', $this->champsDesAides(),
            'la carte des aides n\'est plus lue : `app/Traits/` a changé de forme');

        $source = <<<'PHP'
        <?php
        class Temoin {
            public function garde($request) {
                if ($this->contrepartieHorsPerimetre($request)) { return false; }
                return Account::find($request->account_id);
            }
        }
        PHP;

        $this->assertSame([], $this->lecturesNues($source, 'Temoin'),
            'une lecture couverte par la CARTE d\'une aide est signalée à tort');
    }

    /** Le commentaire ne doit pas compter (leçon S56). */
    public function test_a_commented_read_is_not_reported(): void
    {
        $source = <<<'PHP'
        <?php
        class Temoin {
            public function documente($request) {
                // Merchant::find($request->merchant_id)  <- la forme a proscrire
                return Merchant::companywise()->find($request->merchant_id);
            }
        }
        PHP;

        $this->assertSame([], $this->lecturesNues($source, 'Temoin'),
            'l\'analyse lit les commentaires : elle accuse le code qui DOCUMENTE la règle');
    }
}
