<?php

use App\Enums\Status;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * D4, étape 6 — les quatre colonnes héritées quittent les deux barèmes.
 *
 * `same_day`, `next_day`, `sub_city` et `outside_city` mélangeaient un délai
 * et un périmètre dans une seule dimension. Le modèle par zones les remplace
 * depuis le 2026-09-06 : zone × tranche de poids, plus un supplément de délai
 * **global**. Les deux ont coexisté le temps que chaque installation convertisse
 * son barème ; cette migration retire le premier.
 *
 * ## Elle est irréversible, et elle le dit
 *
 * `down()` recrée les colonnes, **vides**. Les montants d'origine ne sont pas
 * restaurables : ils ont été convertis en lignes zonées, et les lignes héritées
 * sont supprimées ici. Un retour en arrière rend donc le schéma, pas le barème.
 *
 * ## Pourquoi elle refuse de s'exécuter
 *
 * Sans repli, `ChargeCalculator` ne sait plus facturer un colis sans zone. Une
 * société qui n'a pas converti ne facturerait plus **rien** — et le
 * découvrirait au premier colis, pas au déploiement. La migration vérifie donc
 * d'abord, société par société, que plus rien ne dépend des colonnes, et
 * **s'arrête** sinon : un déploiement qui échoue proprement vaut mieux qu'une
 * facturation morte en silence.
 *
 * Le contrôle reprend les règles de `beninlink:bareme-herite`, volontairement
 * réécrites ici en SQL nu. Une migration est jouée une fois, parfois des années
 * après avoir été écrite : la faire dépendre d'un service applicatif, c'est
 * accepter qu'une refonte de ce service casse l'installation d'un nouveau
 * client. `LegacyGridAudit` reste l'outil de l'exploitant, à passer **avant**
 * le déploiement ; ceci est la ceinture de sécurité du dernier instant.
 *
 * ## Si elle refuse
 *
 * `beninlink:zones-tarifaires` a disparu avec les colonnes qu'il lisait. Une
 * installation qui n'a pas converti doit donc revenir à la version précédente,
 * convertir, vérifier avec `beninlink:bareme-herite`, puis redéployer. Le
 * message d'erreur le rappelle, parce que personne ne lira ce fichier à 3 h du
 * matin.
 */
return new class extends Migration
{
    /** Même fenêtre que la porte : au-delà, un colis sans zone est de l'histoire. */
    private const FENETRE_JOURS = 30;

    private const TABLES = ['delivery_charges', 'merchant_delivery_charges'];

    private const COLONNES = ['same_day', 'next_day', 'sub_city', 'outside_city'];

    public function up(): void
    {
        // Une installation neuve n'a rien à convertir : les colonnes n'ont
        // jamais rien porté. Le contrôle ne doit pas l'empêcher de s'installer.
        if (Schema::hasColumn('delivery_charges', 'same_day')) {
            $this->refuserSiQuelqueChoseEnDepend();
        }

        // Les lignes héritées n'ont plus d'objet : le contrôle ci-dessus a
        // établi que chacune a son équivalent zoné.
        foreach (self::TABLES as $table) {
            DB::table($table)->whereNull('zone_id')->delete();
        }

        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                foreach (self::COLONNES as $colonne) {
                    if (Schema::hasColumn($table, $colonne)) {
                        $t->dropColumn($colonne);
                    }
                }
            });
        }
    }

    /**
     * Le schéma revient, pas les montants. C'est dit dans le docblock et
     * répété ici : le prochain qui lira ce `down()` cherchera ses tarifs.
     */
    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                foreach (self::COLONNES as $colonne) {
                    if (!Schema::hasColumn($table, $colonne)) {
                        $t->decimal($colonne, 16, 2)->default(0);
                    }
                }
            });
        }
    }

    private function refuserSiQuelqueChoseEnDepend(): void
    {
        $blocages = [];

        foreach (DB::table('general_settings')->orderBy('id')->get() as $societe) {
            foreach ($this->blocagesDe((int) $societe->id) as $blocage) {
                $blocages[] = sprintf('société %d (%s) : %s', $societe->id, $societe->name ?? '—', $blocage);
            }
        }

        if ($blocages === []) {
            return;
        }

        throw new RuntimeException(
            "Le barème hérité porte encore quelque chose — les quatre colonnes ne peuvent pas être retirées.\n\n"
            . implode("\n", array_map(fn ($l) => '  - ' . $l, $blocages))
            . "\n\nÀ faire, dans cet ordre, depuis la version PRÉCÉDENTE du code (celle qui a encore\n"
            . "`beninlink:zones-tarifaires`, disparu avec les colonnes qu'il lisait) :\n"
            . "  1. php artisan beninlink:zones-tarifaires --societe=N            (constat des écarts)\n"
            . "  2. php artisan beninlink:zones-tarifaires --societe=N --appliquer\n"
            . "  3. php artisan beninlink:bareme-herite                           (doit sortir en succès)\n"
            . "  4. redéployer cette version, puis php artisan migrate\n"
        );
    }

    /** @return array<int, string> */
    private function blocagesDe(int $societe): array
    {
        $blocages = [];

        $heritees = DB::table('delivery_charges')
            ->where('company_id', $societe)->whereNull('zone_id')->get();

        $negociees = DB::table('merchant_delivery_charges')
            ->where('company_id', $societe)->whereNull('zone_id')->count();

        $colisSansZone = Schema::hasColumn('parcels', 'zone_id')
            ? DB::table('parcels')
                ->where('company_id', $societe)
                ->whereNull('zone_id')
                ->where('created_at', '>=', Carbon::now()->subDays(self::FENETRE_JOURS))
                ->count()
            : 0;

        if ($heritees->isEmpty() && $negociees === 0 && $colisSansZone === 0) {
            return [];
        }

        $zones = DB::table('delivery_zones')
            ->where('company_id', $societe)
            ->where('status', Status::ACTIVE)
            ->whereNotIn('code', ['cedeao'])
            ->get();

        if ($zones->isEmpty()) {
            return ['aucune zone nationale configurée — elle facture encore par les quatre colonnes'];
        }

        // Chaque tranche héritée doit exister dans chaque zone nationale, sans
        // quoi la création serait refusée là où elle passait hier.
        foreach ($heritees as $ligne) {
            foreach ($zones as $zone) {
                $existe = DB::table('delivery_charges')
                    ->where('company_id', $societe)
                    ->where('category_id', $ligne->category_id)
                    ->where('zone_id', $zone->id)
                    ->where('weight', $ligne->weight)
                    ->exists();

                if (!$existe) {
                    $blocages[] = sprintf(
                        'catégorie %s, zone « %s » : tranche %s sans tarif zoné',
                        $ligne->category_id,
                        $zone->name,
                        $ligne->weight,
                    );
                }
            }
        }

        if ($negociees > 0) {
            $blocages[] = "{$negociees} barème(s) négocié(s) marchand encore sur les colonnes héritées";
        }

        if ($colisSansZone > 0) {
            $blocages[] = sprintf(
                '%d colis créé(s) sans zone sur %d jours — un écran ou une app en circulation utilise encore le chemin hérité',
                $colisSansZone,
                self::FENETRE_JOURS,
            );
        }

        return array_values(array_unique($blocages));
    }
};
