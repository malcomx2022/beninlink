<?php

namespace App\Rules;

use App\Models\Backend\DeliveryZone;
use App\Models\Backend\Merchant;
use App\Services\Parcel\DeliveryChargeResolver;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Refuse un colis dont la **route n'est pas tarifée** (**D4**, étape 5 bis).
 *
 * Depuis que le colis porte une zone et un délai, `ChargeCalculator` facture par
 * `resolveByZone()`. Celui-ci rend `null` quand rien n'est saisi pour la route —
 * une zone sans grille, ou un pays d'export sans forfait, le cas des pays CEDEAO
 * que le métier n'a pas encore chiffrés. Le calcul lève alors, ce qui est la
 * bonne décision mais la mauvaise expérience : l'opérateur verrait une erreur
 * serveur au lieu d'un message sur le bon champ.
 *
 * Comme `CustomsAllowed`, la règle vit dans les `StoreRequest` : c'est le seul
 * point commun aux **trois** chemins de création. Un contrôle posé dans un
 * contrôleur en laisserait deux ouverts — la leçon de S2.
 *
 * `DataAwareRule` : la route dépend de la catégorie, du poids, du délai et du
 * pays, pas seulement de la zone validée.
 */
class DeliveryRoutePriced implements ValidationRule, DataAwareRule
{
    private array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Pas de zone : le colis suit le barème hérité, rien à vérifier ici.
        if (blank($value)) {
            return;
        }

        $zone = DeliveryZone::companywise()->find($value);
        if ($zone === null) {
            $fail(trans('delivery_zone.zone_not_found'));

            return;
        }

        $merchant = $this->marchand();
        if ($merchant === null) {
            // Le marchand est validé par sa propre règle ; sans lui on ne peut
            // pas trancher, et on ne va pas inventer un second message.
            return;
        }

        $montant = app(DeliveryChargeResolver::class)->resolveByZone(
            $merchant->id,
            isset($this->data['category_id']) ? (int) $this->data['category_id'] : null,
            $this->data['weight'] ?? 0,
            (int) $zone->id,
            isset($this->data['delay_id']) ? (int) $this->data['delay_id'] : null,
            $this->data['destination_country'] ?? null,
        );

        if ($montant !== null) {
            return;
        }

        $fail($zone->isExport()
            ? trans('delivery_zone.country_not_priced', ['zone' => $zone->name])
            : trans('delivery_zone.zone_not_priced', ['zone' => $zone->name]));
    }

    /**
     * Le marchand du formulaire, ou celui du compte connecté.
     *
     * L'administration choisit le marchand (`merchant_id`) ; le panneau marchand
     * et l'API n'envoient rien — c'est le compte qui le porte.
     */
    private function marchand(): ?Merchant
    {
        if (!empty($this->data['merchant_id'])) {
            return Merchant::companywise()->find($this->data['merchant_id']);
        }

        $userId = auth()->id();

        return $userId === null ? null : Merchant::companywise()->where('user_id', $userId)->first();
    }
}
