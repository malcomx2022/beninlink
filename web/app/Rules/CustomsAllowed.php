<?php

namespace App\Rules;

use App\Services\Customs\CustomsService;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Refuse la creation d'un colis d'export couvert par une regle BLOQUANTE.
 *
 * La regle vit ici, dans `StoreRequest`, parce que c'est le seul point commun
 * aux TROIS chemins de creation : l'API marchand, le panneau marchand et
 * l'administration. Un controle place dans un controleur en laisserait deux
 * ouverts, et un controle en JavaScript n'en fermerait aucun — c'est la lecon
 * de S2.
 *
 * `DataAwareRule` : le blocage depend du couple pays + categorie, donc la regle
 * a besoin du reste du formulaire, pas seulement de la valeur validee.
 */
class CustomsAllowed implements ValidationRule, DataAwareRule
{
    private array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $rule = app(CustomsService::class)->blockingRuleFor(
            $this->data['destination_country'] ?? null,
            $value
        );

        if ($rule) {
            // Le message nomme le document manquant : un refus sans motif
            // laisserait le marchand sans rien a faire.
            $fail(trans('customs.blocked', ['document' => $rule->required_document]));
        }
    }
}
