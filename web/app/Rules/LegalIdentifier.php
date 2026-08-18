<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Formats des identifiants légaux béninois.
 *
 * Rassemblés dans une règle unique parce qu'ils sont demandés à **quatre
 * formulaires** (inscription publique et création admin, pour le marchand comme
 * pour la société) : dupliquer les expressions régulières garantirait qu'elles
 * divergent.
 *
 * Les formats sont volontairement **tolérants** : ils écartent les saisies
 * manifestement fausses sans prétendre valider l'existence de l'identifiant,
 * que seule l'administration peut confirmer. Un contrôle trop strict bloquerait
 * des entreprises réelles dont le numéro sort du gabarit attendu.
 */
class LegalIdentifier implements ValidationRule
{
    public const IFU = 'ifu';
    public const RCCM = 'rccm';
    public const CNSS = 'cnss';

    public function __construct(private readonly string $type)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Champ vide : c'est à `required` / `nullable` de trancher, pas au format.
        if ($value === null || $value === '') {
            return;
        }

        $value = trim((string) $value);

        match ($this->type) {
            self::IFU => $this->validateIfu($value, $fail),
            self::RCCM => $this->validateRccm($value, $fail),
            self::CNSS => $this->validateCnss($value, $fail),
            default => null,
        };
    }

    /** IFU : exactement 13 chiffres. */
    private function validateIfu(string $value, Closure $fail): void
    {
        if (!preg_match('/^\d{13}$/', $value)) {
            $fail(__('validation.ifu'));
        }
    }

    /**
     * RCCM : lettres, chiffres, espaces, tirets et barres obliques.
     * Format usuel au Bénin « RB/COT/24 B 1234 », mais les greffes en produisent
     * des variantes — on n'impose donc pas le préfixe RB.
     */
    private function validateRccm(string $value, Closure $fail): void
    {
        if (!preg_match('#^[A-Za-z0-9/\-\s]{4,50}$#', $value)) {
            $fail(__('validation.rccm'));
        }
    }

    /** CNSS : numéro employeur, chiffres et séparateurs. */
    private function validateCnss(string $value, Closure $fail): void
    {
        if (!preg_match('#^[0-9/\-\s]{4,30}$#', $value)) {
            $fail(__('validation.cnss'));
        }
    }
}
