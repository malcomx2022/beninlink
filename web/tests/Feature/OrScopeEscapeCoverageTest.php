<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * S61 — le SEPTIÈME filet : le `OR` qui sort du périmètre.
 *
 * ```
 * Model::companywise()->where(A)->orWhere(B)
 * SQL : company_id = X AND A OR B          ← le OR de PREMIER NIVEAU s'échappe
 * ```
 *
 * `AND` lie plus fort que `OR`. Un `orWhere` posé **au même niveau** que la
 * portée la neutralise donc pour toute la branche de droite. Le groupe de `OR`
 * doit vivre dans une **fermeture**.
 *
 * ## Pourquoi un filet, et pourquoi celui-ci était nécessaire
 *
 * Cette famille a mordu **deux fois**, et les six filets précédents plus
 * l'instrument des lectures nues l'ont tous manquée :
 *
 * | | |
 * |---|---|
 * | **S57** | `ParcelRepository::parcelSearchs` — nom, téléphone et adresse des clients de TOUTES les sociétés |
 * | **S60** | `FundTransferRepository::fundTransferSearch` — coordonnées bancaires ET soldes, sur l'écran voisin de celui corrigé en S58 |
 *
 * Dans les deux cas **`companywise()` était là**. Ce n'est pas une garde
 * manquante : c'est une garde que la *structure de la requête* annule. Aucun
 * filet cherchant une garde absente ne pouvait la voir, et l'instrument des
 * lectures nues cherche une **lecture** (`Model::find($request->x)`), pas une
 * structure.
 *
 * ⚠️ **Ce filet ne juge pas.** Un `OR` au niveau de la portée peut être
 * **délibéré** — c'est le cas des quatre occurrences actuelles, qui ajoutent la
 * catégorie de livraison de la **plateforme** (`id = 1`) au catalogue de la
 * société. Il exige seulement que chacune soit écrite quelque part, avec sa
 * raison.
 */
class OrScopeEscapeCoverageTest extends TestCase
{
    /** Les racines analysées. */
    private const RACINES = ['app/Http', 'app/Repositories', 'app/Services', 'app/Models'];

    /**
     * Les `OR` posés au niveau de la portée **volontairement**, avec leur motif.
     *
     * Clé : `Classe::methode`. Une occurrence nouvelle n'a pas sa place ici sans
     * qu'on ait écrit pourquoi le `OR` doit élargir le périmètre.
     */
    private const DELIBEREES = [
        'DeliveryChargeRepository::categories' => 'catalogue des catégories de livraison : `(company_id = X OR id = 1)` ajoute la catégorie de la PLATEFORME, partagée par toutes les sociétés. `categorys` ne porte aucune `company_id` (constat S32) et `ParcelCatalogScopeTest::test_the_platform_category_is_shared_by_every_company` l\'établit depuis S46',
        'DeliveryZoneRepository::categories' => 'idem — même catalogue, même catégorie de plateforme',
        'MerchantParcelRepository::deliveryCategories' => 'idem, côté panneau marchand',
        'ParcelRepository::deliveryCategories' => 'idem, côté back-office',
    ];

    /* ────────────────────────────── l'analyse ───────────────────────────── */

    private const PORTEE = '/companywise\s*\(\s*\)|where\s*\(\s*[\'"]company_id[\'"]/';
    private const OR_APPEL = '/->\s*(orWhere[A-Za-z]*)\s*\(/';

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

    /** Les corps de méthode, par nom. */
    private function methodes(string $source): array
    {
        $res = [];

        if (!preg_match_all('/function\s+(\w+)\s*\([^)]*\)[^{;]*\{/', $source, $m, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            return $res;
        }

        foreach ($m as $trouve) {
            $i = $trouve[0][1] + strlen($trouve[0][0]) - 1;
            $prof = 0;

            for ($j = $i, $n = strlen($source); $j < $n; $j++) {
                if ($source[$j] === '{') {
                    $prof++;
                } elseif ($source[$j] === '}') {
                    if (--$prof === 0) {
                        $res[] = [$trouve[1][0], substr($source, $i, $j - $i)];
                        break;
                    }
                }
            }
        }

        return $res;
    }

    private function profondeur(string $corps, int $position): int
    {
        $p = 0;

        for ($i = 0; $i < $position; $i++) {
            if ($corps[$i] === '(' || $corps[$i] === '{') {
                $p++;
            } elseif ($corps[$i] === ')' || $corps[$i] === '}') {
                $p--;
            }
        }

        return $p;
    }

    /**
     * Les `OR` rencontrés à la **même profondeur** qu'une portée posée avant eux.
     *
     * Un `orWhere` plus profond — dans une fermeture, dans un `whereHas` — est
     * **enfermé**, donc sans danger : c'est exactement la correction appliquée en
     * S57 et en S60.
     *
     * @return array<string,string> `Classe::methode` => extrait
     */
    private function echappements(string $source, string $classe): array
    {
        $trouves = [];

        foreach ($this->methodes($this->sansCommentaires($source)) as [$nom, $corps]) {
            if (!preg_match_all(self::PORTEE, $corps, $mp, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            $portees = [];
            foreach ($mp[0] as $p) {
                $portees[] = [$p[1], $this->profondeur($corps, $p[1])];
            }

            if (!preg_match_all(self::OR_APPEL, $corps, $mo, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
                continue;
            }

            foreach ($mo as $o) {
                $pos = $o[0][1];
                $dOr = $this->profondeur($corps, $pos);

                foreach ($portees as [$pPos, $pProf]) {
                    if ($pPos < $pos && $pProf === $dOr) {
                        $trouves[$classe . '::' . $nom] = trim(preg_replace('/\s+/', ' ',
                            substr($corps, $pos, 70)));
                        break 2;
                    }
                }
            }
        }

        return $trouves;
    }

    /** Toutes les occurrences du dépôt. */
    private function toutesLesOccurrences(): array
    {
        $trouves = [];

        foreach (self::RACINES as $racine) {
            $chemin = base_path($racine);

            if (!is_dir($chemin)) {
                continue;
            }

            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($chemin));

            foreach ($it as $fichier) {
                if ($fichier->getExtension() !== 'php') {
                    continue;
                }

                $source = file_get_contents($fichier->getPathname());
                $trouves += $this->echappements($source, $fichier->getBasename('.php'));
            }
        }

        return $trouves;
    }

    /* ─────────────── 1. tout est classé : rien de neuf ne passe ─────────── */

    public function test_every_or_at_scope_level_is_deliberate(): void
    {
        $trouves = $this->toutesLesOccurrences();

        $nonClassees = array_values(array_diff(array_keys($trouves), array_keys(self::DELIBEREES)));
        sort($nonClassees);

        $detail = array_map(fn ($c) => $c . '   →   ' . $trouves[$c], $nonClassees);

        $this->assertSame([], $nonClassees, "Un `orWhere` est posé au MÊME niveau qu'une portée : "
            . "`company_id = X AND A OR B` laisse sortir la branche de droite. Soit le groupe de `OR` "
            . "doit être ENFERMÉ dans une fermeture (S57, S60), soit l'élargissement est voulu et il "
            . "s'inscrit dans DELIBEREES avec sa raison :\n - " . implode("\n - ", $detail));
    }

    /** Et l'inverse : une déclaration qui ne correspond plus à rien s'en va. */
    public function test_no_declaration_points_to_a_vanished_occurrence(): void
    {
        $connues = array_keys($this->toutesLesOccurrences());
        $fantomes = array_values(array_diff(array_keys(self::DELIBEREES), $connues));

        $this->assertSame([], $fantomes, "DELIBEREES déclare des occurrences qui n'existent plus — "
            . "le `OR` a été enfermé, ou la méthode a disparu :\n - " . implode("\n - ", $fantomes));
    }

    /* ─────────────── 2. le filet voit-il encore quelque chose ? ──────────── */

    /**
     * Le témoin d'ÉNUMÉRATION. Une racine renommée, un `token_get_all` qui
     * change de comportement, et le filet passerait au vert sans rien examiner.
     */
    public function test_the_net_still_finds_the_known_occurrences(): void
    {
        $this->assertCount(
            count(self::DELIBEREES),
            $this->toutesLesOccurrences(),
            'le filet ne retrouve plus les occurrences connues : son analyse est cassée',
        );
    }

    /**
     * Le témoin de DÉTECTION, et c'est le plus important.
     *
     * Il soumet à l'analyse les deux formes, côte à côte : celle qui s'échappe
     * et celle qui est enfermée. Le filet doit voir la première et **ignorer la
     * seconde**. Sans ce témoin, une analyse qui signalerait *tout* `orWhere`
     * passerait pour fonctionnelle tout en étant inutilisable — et une analyse
     * qui n'en signalerait aucun passerait pour rassurante.
     */
    public function test_the_analysis_separates_an_escape_from_an_enclosed_group(): void
    {
        $source = <<<'PHP'
        <?php
        class Temoin {
            public function fuit($request) {
                return Modele::companywise()
                    ->where('a', $request->x)
                    ->orWhere('b', $request->x);
            }
            public function enferme($request) {
                return Modele::companywise()->where(function ($q) use ($request) {
                    $q->where('a', $request->x)->orWhere('b', $request->x);
                });
            }
        }
        PHP;

        $trouves = array_keys($this->echappements($source, 'Temoin'));

        $this->assertContains('Temoin::fuit', $trouves,
            'l\'analyse ne voit plus un `orWhere` posé au niveau de la portée : le filet ne sert à rien');

        $this->assertNotContains('Temoin::enferme', $trouves,
            'l\'analyse signale un `orWhere` pourtant ENFERMÉ dans une fermeture : elle crie sur du code '
            . 'juste, et un filet qui crie trop cesse d\'être lu');
    }

    /**
     * Le commentaire ne doit pas compter.
     *
     * Les deux corrections de cette famille (S57, S60) **citent** la chaîne
     * fautive dans leur commentaire pour expliquer le piège. Une analyse lisant
     * la source brute signalerait donc précisément les deux méthodes qui
     * documentent la règle — elle accuserait la documentation au lieu du code.
     * C'est la leçon de S56, appliquée ici.
     */
    public function test_a_commented_escape_is_not_reported(): void
    {
        $source = <<<'PHP'
        <?php
        class Temoin {
            public function documente($request) {
                // Modele::companywise()->where('a', 1)->orWhere('b', 2)  <- le piege
                return Modele::companywise()->where('a', $request->x);
            }
        }
        PHP;

        $this->assertSame([], $this->echappements($source, 'Temoin'),
            'l\'analyse lit les commentaires : elle accuse le code qui DOCUMENTE le piège');
    }
}
