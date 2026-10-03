<?php

namespace App\Services\Pricing;

use App\Enums\Status;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\DeliveryZone;
use InvalidArgumentException;

/**
 * La grille nationale (tranche × zone) lue dans un **fichier**, et posée en base
 * sans jamais réécrire ce qui existe (**D4**, S72).
 *
 * Jusqu'ici la grille vivait à deux endroits : dans l'écran *Réglages → Zones et
 * barème* pour la production, et dans une constante de `PiloteDataset` pour la
 * recette. Deux copies, deux occasions de dériver. Ce service lit **un seul
 * fichier CSV** (`database/bareme/grille-nationale.csv` par défaut) que la
 * commande `beninlink:zones-tarifaires --grille=` et le jeu pilote partagent :
 * la recette et la production partent de la même grille, sans ressaisie.
 *
 * ⚠️ Même règle que les forfaits CEDEAO de `ZoneCatalog` : une ligne (société,
 * catégorie, zone, tranche) **déjà présente n'est jamais réécrite**. Le fichier est
 * un point de départ ; le montant que le transporteur a ajusté à l'écran fait foi,
 * et relancer l'installation ne le ramène pas à la valeur d'usine. C'est ce qui
 * autorise à relancer la commande à chaque déploiement.
 *
 * Le fichier ne porte **pas** la zone CEDEAO : elle se facture au forfait par pays,
 * pas à la tranche (`ZoneCatalog::PAYS`). Une colonne `cedeao` est refusée.
 */
class GridFile
{
    /** Le fichier versionné, relatif à la racine de `web/`. */
    public const DEFAUT = 'database/bareme/grille-nationale.csv';

    private const SEPARATEUR = ';';
    private const COLONNES_FIXES = ['categorie', 'poids_max'];
    private const ZONES_NATIONALES = [DeliveryZone::COTONOU, DeliveryZone::PERIPHERIE, DeliveryZone::INTERIEUR];

    /**
     * Lit et valide le fichier. Tout ou rien : la première ligne fautive refuse
     * le fichier entier, pour qu'une grille à moitié posée n'existe jamais.
     *
     * @return list<array{categorie:string, poids_max:int, montants:array<string,int>}>
     */
    public function lire(string $chemin): array
    {
        // Un chemin relatif se lit depuis la racine de `web/` : c'est ainsi que la
        // documentation l'écrit (`--grille=database/bareme/grille-nationale.csv`),
        // et le planificateur ou `deploy.sh` ne lancent pas toujours artisan de là.
        if (!is_readable($chemin) && is_readable(base_path($chemin))) {
            $chemin = base_path($chemin);
        }
        if (!is_readable($chemin)) {
            throw new InvalidArgumentException("Fichier de grille introuvable : {$chemin}");
        }

        $lignes = array_values(array_filter(
            array_map('trim', file($chemin, FILE_IGNORE_NEW_LINES)),
            fn (string $l) => $l !== '' && !str_starts_with($l, '#'),
        ));
        if ($lignes === []) {
            throw new InvalidArgumentException('Fichier de grille vide.');
        }

        $entete = array_map(fn ($c) => strtolower(trim($c)), explode(self::SEPARATEUR, array_shift($lignes)));
        $zones = array_slice($entete, count(self::COLONNES_FIXES));

        if (array_slice($entete, 0, 2) !== self::COLONNES_FIXES) {
            throw new InvalidArgumentException('En-tête attendu : « categorie;poids_max;<zones…> ».');
        }
        if ($zones === [] || array_diff($zones, self::ZONES_NATIONALES) !== []) {
            throw new InvalidArgumentException(
                'Colonnes de zones admises : ' . implode(', ', self::ZONES_NATIONALES)
                . ' — la CEDEAO se facture au forfait par pays, pas à la tranche.'
            );
        }
        if (count($zones) !== count(array_unique($zones))) {
            throw new InvalidArgumentException('Une zone apparaît deux fois dans l’en-tête.');
        }

        $out = [];
        $vues = [];
        foreach ($lignes as $numero => $ligne) {
            $cellules = array_map('trim', explode(self::SEPARATEUR, $ligne));
            if (count($cellules) !== count($entete)) {
                throw new InvalidArgumentException(sprintf('Ligne %d : %d colonnes, %d attendues.', $numero + 2, count($cellules), count($entete)));
            }

            [$categorie, $poids] = $cellules;
            if ($categorie === '') {
                throw new InvalidArgumentException(sprintf('Ligne %d : catégorie vide.', $numero + 2));
            }
            if (!ctype_digit($poids) || (int) $poids <= 0) {
                throw new InvalidArgumentException(sprintf('Ligne %d : tranche « %s » — un poids maximal entier en kg, supérieur à zéro.', $numero + 2, $poids));
            }
            $cle = $categorie . '|' . (int) $poids;
            if (isset($vues[$cle])) {
                throw new InvalidArgumentException(sprintf('Ligne %d : la tranche %s kg de « %s » est déclarée deux fois.', $numero + 2, $poids, $categorie));
            }
            $vues[$cle] = true;

            $montants = [];
            foreach ($zones as $i => $code) {
                $brut = $cellules[count(self::COLONNES_FIXES) + $i];
                // FCFA entiers, sans décimales (règle du projet) : « 1 500 » est toléré, « 1500.5 » refusé.
                $propre = str_replace([' ', "\u{A0}", "\u{202F}"], '', $brut);
                if (!ctype_digit($propre)) {
                    throw new InvalidArgumentException(sprintf('Ligne %d, zone %s : « %s » n’est pas un montant en FCFA entiers.', $numero + 2, $code, $brut));
                }
                $montants[$code] = (int) $propre;
            }

            $out[] = ['categorie' => $categorie, 'poids_max' => (int) $poids, 'montants' => $montants];
        }

        return $out;
    }

    /**
     * Les lignes du fichier qui n'existent pas encore en base pour cette société.
     * Sert au constat de la commande, avant d'écrire.
     *
     * @param list<array{categorie:string, poids_max:int, montants:array<string,int>}> $lignes
     */
    public function manquantes(int $companyId, array $lignes): int
    {
        $zones = $this->zonesDe($companyId, $lignes);
        $manquantes = 0;
        foreach ($lignes as $ligne) {
            $categorieId = Deliverycategory::where('company_id', $companyId)->where('title', $ligne['categorie'])->value('id');
            foreach ($ligne['montants'] as $code => $montant) {
                if ($categorieId === null || !$this->existe($companyId, (int) $categorieId, $zones[$code]->id, $ligne['poids_max'])) {
                    $manquantes++;
                }
            }
        }

        return $manquantes;
    }

    /**
     * Pose la grille : catégories créées si elles manquent, lignes **créées si
     * elles manquent, jamais réécrites**.
     *
     * @param list<array{categorie:string, poids_max:int, montants:array<string,int>}> $lignes
     * @return array{creees:int, conservees:int, categories:array<string, Deliverycategory>}
     */
    public function installer(int $companyId, array $lignes): array
    {
        $zones = $this->zonesDe($companyId, $lignes);
        $categories = [];
        $creees = 0;
        $conservees = 0;

        foreach ($lignes as $position => $ligne) {
            $categorie = $categories[$ligne['categorie']] ??= $this->categorie($companyId, $ligne['categorie']);

            foreach ($ligne['montants'] as $code => $montant) {
                if ($this->existe($companyId, $categorie->id, $zones[$code]->id, $ligne['poids_max'])) {
                    $conservees++;
                    continue;
                }

                // Pas d'`updateOrCreate` : `company_id` n'est pas assignable en masse
                // sur ces modèles du socle (même piège que `ZoneCatalog::zones()`).
                $row = new DeliveryCharge();
                $row->company_id = $companyId;
                $row->category_id = $categorie->id;
                $row->zone_id = $zones[$code]->id;
                $row->weight = $ligne['poids_max'];
                $row->amount = $montant;
                $row->position = $position + 1;
                $row->status = Status::ACTIVE;
                $row->save();
                $creees++;
            }
        }

        return compact('creees', 'conservees', 'categories');
    }

    /** @return array<string, DeliveryZone> indexées par code */
    private function zonesDe(int $companyId, array $lignes): array
    {
        $codes = $lignes === [] ? [] : array_keys($lignes[0]['montants']);
        $zones = DeliveryZone::where('company_id', $companyId)->whereIn('code', $codes)->get()->keyBy('code');

        $absentes = array_diff($codes, $zones->keys()->all());
        if ($absentes !== []) {
            throw new InvalidArgumentException(
                'Zones absentes pour la société ' . $companyId . ' : ' . implode(', ', $absentes)
                . ' — poser le cadre d’abord (`ZoneCatalog::installer()`, ou `--installer` sans `--grille`).'
            );
        }

        return $zones->all();
    }

    private function categorie(int $companyId, string $titre): Deliverycategory
    {
        $categorie = Deliverycategory::where('company_id', $companyId)->where('title', $titre)->first();
        if ($categorie !== null) {
            return $categorie;
        }

        $categorie = new Deliverycategory();
        $categorie->company_id = $companyId;
        $categorie->title = $titre;
        $categorie->status = Status::ACTIVE;
        $categorie->position = (int) Deliverycategory::where('company_id', $companyId)->max('position') + 1;
        $categorie->save();

        return $categorie;
    }

    private function existe(int $companyId, int $categorieId, int $zoneId, int $poids): bool
    {
        return DeliveryCharge::where('company_id', $companyId)
            ->where('category_id', $categorieId)
            ->where('zone_id', $zoneId)
            ->where('weight', $poids)
            ->exists();
    }
}
