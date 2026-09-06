<?php

namespace App\Repositories\DeliveryZone;

use App\Enums\Status;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\DeliveryDelay;
use App\Models\Backend\DeliveryZone;
use App\Models\Backend\DeliveryZoneCountry;
use App\Models\Backend\MerchantDeliveryCharge;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Saisie du barème par zones (**D4**, étape 4).
 *
 * Les étapes 1 à 3 ont donné le schéma, la résolution et la conversion du
 * barème hérité. Il manquait le geste : sans écran, seule
 * `beninlink:zones-tarifaires` savait écrire ces tables, et **rien** ne savait
 * saisir les forfaits CEDEAO — la valeur que le métier doit encore fixer.
 *
 * Trois principes tenus ici, et vérifiés par `DeliveryZoneScreensTest` :
 *
 *  - **le code d'une zone ne change jamais.** `ChargeCalculator::codRateForZone()`
 *    et `ZoneGridConverter` reconnaissent une zone à son code : le renommer
 *    détacherait silencieusement le taux COD. Le libellé, lui, se modifie ;
 *  - **une zone qui porte des tarifs ne se supprime pas.** `zone_id` est en
 *    `nullOnDelete` : la supprimer transformerait ses lignes en lignes
 *    héritées — avec un `amount` que plus personne ne lit, et un tarif qui
 *    change sans que personne l'ait demandé ;
 *  - **`company_id` s'écrit toujours à la main.** Sur les modèles du socle
 *    (`DeliveryCharge`) il n'est pas assignable en masse : un `updateOrCreate`
 *    créerait une ligne sans société, invisible et rejouée à chaque passage.
 */
class DeliveryZoneRepository implements DeliveryZoneInterface
{
    public function zones(): Collection
    {
        return DeliveryZone::companywise()->orderBy('position')->orderBy('id')->get();
    }

    public function delais(): Collection
    {
        return DeliveryDelay::companywise()->orderBy('position')->orderBy('id')->get();
    }

    /** La zone facturée au pays, s'il y en a une. */
    public function zoneExport(): ?DeliveryZone
    {
        return $this->zones()->first(fn (DeliveryZone $zone) => $zone->isExport());
    }

    public function pays(DeliveryZone $zone): Collection
    {
        return $zone->countries()->orderBy('name')->get();
    }

    /** Mêmes catégories que l'écran hérité : celles de la société, plus la n° 1. */
    public function categories(): Collection
    {
        return Deliverycategory::where(function ($query) {
            $query->companywise();
            $query->orWhere('id', 1);
        })->get();
    }

    /**
     * Les tranches de poids d'une catégorie, et le montant de chaque zone.
     *
     * L'union des tranches héritées et des tranches déjà zonées : une société
     * qui n'a pas encore converti son barème voit quand même ses poids, avec
     * des montants à remplir. L'écran sert donc aussi de première saisie.
     *
     * @return array<int, array{weight:int, amounts:array<int, float>}>
     */
    public function tranches(?int $categoryId): array
    {
        if ($categoryId === null) {
            return [];
        }

        $lignes = DeliveryCharge::companywise()->where('category_id', $categoryId)->get();

        $tranches = [];
        foreach ($lignes as $ligne) {
            $cle = (string) ((int) $ligne->weight);
            $tranches[$cle] ??= ['weight' => (int) $ligne->weight, 'amounts' => []];

            if ($ligne->zone_id !== null) {
                $tranches[$cle]['amounts'][(int) $ligne->zone_id] = (float) $ligne->amount;
            }
        }

        ksort($tranches, SORT_NUMERIC);

        return array_values($tranches);
    }

    /**
     * Enregistre la liste des zones : une ligne par zone, ajout et retrait.
     *
     * @param  array<int, array<string, mixed>>  $lignes
     * @return array{ecrites:int, supprimees:int, refusees:array<int, string>}
     */
    public function enregistrerZones(array $lignes): array
    {
        $ecrites = 0;
        $supprimees = 0;
        $refusees = [];
        $position = 0;

        foreach ($lignes as $ligne) {
            $id = (int) ($ligne['id'] ?? 0);
            $nom = trim((string) ($ligne['name'] ?? ''));
            $supprimer = (bool) ($ligne['delete'] ?? false);

            $zone = $id > 0 ? DeliveryZone::companywise()->find($id) : null;

            if ($id > 0 && $zone === null) {
                // Ligne d'une autre société glissée dans le formulaire : on ne
                // la touche pas, et on ne la signale pas non plus (S7).
                continue;
            }

            if ($supprimer) {
                if ($zone === null) {
                    continue;
                }
                if ($this->zonePorteDesTarifs($zone)) {
                    $refusees[] = $zone->name;
                    $position++;
                    continue;
                }
                $zone->delete();
                $supprimees++;
                continue;
            }

            if ($nom === '') {
                continue;
            }

            if ($zone === null) {
                $code = $this->codeLibre($ligne['code'] ?? $nom);
                if ($code === null) {
                    continue;
                }
                $zone = new DeliveryZone();
                $zone->company_id = settings()->id;
                $zone->code = $code;
            }

            // Le code n'est jamais réécrit : il identifie la zone pour le taux
            // COD et pour la conversion. Seul le libellé suit l'usage.
            $zone->name = $nom;
            $zone->position = $position++;
            $zone->status = (int) ($ligne['status'] ?? Status::ACTIVE);
            $zone->save();
            $ecrites++;
        }

        return ['ecrites' => $ecrites, 'supprimees' => $supprimees, 'refusees' => $refusees];
    }

    /** @param  array<int, array<string, mixed>>  $lignes */
    public function enregistrerDelais(array $lignes): int
    {
        $ecrits = 0;
        $position = 0;

        foreach ($lignes as $ligne) {
            $id = (int) ($ligne['id'] ?? 0);
            $nom = trim((string) ($ligne['name'] ?? ''));

            $delai = $id > 0 ? DeliveryDelay::companywise()->find($id) : null;

            if ($id > 0 && $delai === null) {
                continue;
            }

            if ((bool) ($ligne['delete'] ?? false)) {
                $delai?->delete();
                continue;
            }

            if ($nom === '') {
                continue;
            }

            if ($delai === null) {
                $code = $this->codeLibreDelai($ligne['code'] ?? $nom);
                if ($code === null) {
                    continue;
                }
                $delai = new DeliveryDelay();
                $delai->company_id = settings()->id;
                $delai->code = $code;
            }

            $delai->name = $nom;
            // Le supplément est **global** : c'est la décision du métier, et
            // c'est ce qui empêche de revenir aux quatre colonnes d'origine.
            $delai->surcharge = (float) ($ligne['surcharge'] ?? 0);
            $delai->position = $position++;
            $delai->status = (int) ($ligne['status'] ?? Status::ACTIVE);
            $delai->save();
            $ecrits++;
        }

        return $ecrits;
    }

    /**
     * Les pays d'une zone d'export et leur forfait.
     *
     * @param  array<int, array<string, mixed>>  $lignes
     */
    public function enregistrerPays(DeliveryZone $zone, array $lignes): int
    {
        $ecrits = 0;

        foreach ($lignes as $ligne) {
            $id = (int) ($ligne['id'] ?? 0);
            $nom = trim((string) ($ligne['name'] ?? ''));
            $code = strtoupper(trim((string) ($ligne['code'] ?? '')));

            $pays = $id > 0 ? DeliveryZoneCountry::where('zone_id', $zone->id)->find($id) : null;

            if ($id > 0 && $pays === null) {
                continue;
            }

            if ((bool) ($ligne['delete'] ?? false)) {
                $pays?->delete();
                continue;
            }

            if ($nom === '' || strlen($code) !== 2) {
                continue;
            }

            if ($pays === null) {
                $existant = DeliveryZoneCountry::where('zone_id', $zone->id)->where('code', $code)->first();
                if ($existant !== null) {
                    // Deux fois le même pays dans un envoi : on met à jour
                    // plutôt que de laisser l'index unique lever.
                    $pays = $existant;
                } else {
                    $pays = new DeliveryZoneCountry();
                    $pays->zone_id = $zone->id;
                    $pays->code = $code;
                }
            }

            $pays->name = $nom;
            $pays->flat_amount = (float) ($ligne['flat_amount'] ?? 0);
            $pays->status = (int) ($ligne['status'] ?? Status::ACTIVE);
            $pays->save();
            $ecrits++;
        }

        return $ecrits;
    }

    /**
     * La grille d'une catégorie : une ligne par tranche, une colonne par zone.
     *
     * Écrit **uniquement** des lignes zonées. Les quatre colonnes héritées ne
     * bougent pas : elles disparaîtront à l'étape 6, une fois les apps
     * déployées. Une installation qui ne saisit rien facture donc comme avant.
     *
     * @param  array<int, array<string, mixed>>  $lignes
     * @return int nombre de cellules écrites
     */
    public function enregistrerGrille(int $categoryId, array $lignes): int
    {
        $zones = $this->zones()->keyBy('id');
        $ecrites = 0;

        foreach ($lignes as $ligne) {
            // `delivery_charges.weight` est NOT NULL, defaut 0 : une categorie
            // sans poids (autre que « kg ») s'ecrit 0, jamais null. Normaliser
            // ici evite une contrainte violee au moment du `save()`.
            $brut = $ligne['weight'] ?? null;
            $poids = $brut === null || $brut === '' ? 0 : (int) $brut;

            if ((bool) ($ligne['delete'] ?? false)) {
                $this->lignesZonees($categoryId, $poids)->delete();
                continue;
            }

            foreach ((array) ($ligne['amounts'] ?? []) as $zoneId => $montant) {
                $zone = $zones->get((int) $zoneId);
                if ($zone === null || $montant === null || $montant === '') {
                    continue;
                }

                $cible = $this->lignesZonees($categoryId, $poids)
                    ->where('zone_id', $zone->id)
                    ->first() ?? new DeliveryCharge();

                $cible->company_id = settings()->id;
                $cible->category_id = $categoryId;
                $cible->zone_id = $zone->id;
                $cible->weight = $poids;
                $cible->amount = (float) $montant;
                $cible->position = $cible->position ?? 0;
                $cible->status = Status::ACTIVE;
                $cible->save();
                $ecrites++;
            }
        }

        return $ecrites;
    }

    /** Les lignes zonées d'une catégorie, pour une tranche. */
    private function lignesZonees(int $categoryId, int $poids)
    {
        return DeliveryCharge::companywise()
            ->where('category_id', $categoryId)
            ->whereNotNull('zone_id')
            ->where('weight', $poids);
    }

    /** Une zone est « occupée » dès qu'une ligne de barème la désigne. */
    private function zonePorteDesTarifs(DeliveryZone $zone): bool
    {
        return DeliveryCharge::where('zone_id', $zone->id)->exists()
            || MerchantDeliveryCharge::where('zone_id', $zone->id)->exists();
    }

    /** Code court, stable et libre pour la société — ou `null` s'il est pris. */
    private function codeLibre($source): ?string
    {
        $code = Str::limit(Str::slug((string) $source, '_'), 32, '');

        if ($code === '' || DeliveryZone::companywise()->where('code', $code)->exists()) {
            return null;
        }

        return $code;
    }

    private function codeLibreDelai($source): ?string
    {
        $code = Str::limit(Str::slug((string) $source, '_'), 32, '');

        if ($code === '' || DeliveryDelay::companywise()->where('code', $code)->exists()) {
            return null;
        }

        return $code;
    }
}
