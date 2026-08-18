<?php

namespace App\Services\Customs;

use App\Enums\CustomsAlertStatus;
use App\Enums\CustomsLevel;
use App\Models\Backend\CustomsAlert;
use App\Models\Backend\CustomsRule;
use App\Models\Backend\Parcel;

/**
 * Regle douaniere applicable a un colis, et alerte qui en decoule.
 *
 * Point d'entree unique du module : la validation de la creation, l'API et le
 * back-office passent tous par ici. Aucune de ces regles ne vit dans un
 * formulaire ou dans du JavaScript — meme discipline que ChargeCalculator pour
 * les montants (voir S2).
 *
 * Un colis est un EXPORT des lors que son pays de destination est renseigne et
 * different du Benin. Sinon il n'y a rien a verifier : le socle reste un
 * transporteur domestique, et les colis existants n'ont pas de pays.
 */
class CustomsService
{
    /** Pays d'origine : un colis qui y reste n'est pas un export. */
    public const HOME_COUNTRY = 'BJ';

    /**
     * Regle applicable, ou null si le colis est domestique, si le pays ou la
     * categorie manquent, ou si aucune regle ne couvre ce couple.
     */
    public function ruleFor(?string $countryCode, ?string $category): ?CustomsRule
    {
        if (!$this->isExport($countryCode) || blank($category)) {
            return null;
        }

        return CustomsRule::companywise()
            ->active()
            ->where('country_code', strtoupper($countryCode))
            ->where('goods_category', $category)
            // Si plusieurs regles cohabitent, la plus severe l'emporte.
            ->orderByDesc('level')
            ->first();
    }

    public function isExport(?string $countryCode): bool
    {
        return filled($countryCode) && strtoupper($countryCode) !== self::HOME_COUNTRY;
    }

    /**
     * Le colis est-il refuse ? Seul le niveau BLOQUANT arrete la creation ;
     * le message rendu nomme le document manquant.
     */
    public function blockingRuleFor(?string $countryCode, ?string $category): ?CustomsRule
    {
        $rule = $this->ruleFor($countryCode, $category);

        return $rule && $rule->level == CustomsLevel::BLOCKING ? $rule : null;
    }

    /**
     * Enregistre l'alerte d'un colis qui vient d'etre cree.
     *
     * Les colonnes de la regle sont RECOPIEES : l'alerte doit continuer
     * d'afficher ce qui a ete annonce au marchand, meme si la regle change
     * ensuite. Un niveau BLOQUANT n'arrive jamais ici — la creation a ete
     * refusee avant, il n'y a pas de colis auquel rattacher l'alerte.
     */
    public function recordFor(Parcel $parcel): ?CustomsAlert
    {
        $rule = $this->ruleFor($parcel->destination_country, $parcel->customs_category);

        if (blank($rule)) {
            return null;
        }

        // Idempotent : la creation et la mise a jour appellent tous deux cette
        // methode, un colis ne doit pas accumuler la meme alerte.
        $existing = CustomsAlert::where('parcel_id', $parcel->id)
            ->where('customs_rule_id', $rule->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return CustomsAlert::create([
            'company_id' => $parcel->company_id,
            'merchant_id' => $parcel->merchant_id,
            'parcel_id' => $parcel->id,
            'customs_rule_id' => $rule->id,
            'country_code' => $rule->country_code,
            'country_name' => $rule->country_name,
            'goods_category' => $rule->goods_category,
            'level' => $rule->level,
            'required_document' => $rule->required_document,
            'message' => $rule->message,
            'status' => CustomsAlertStatus::PENDING,
        ]);
    }

    /**
     * Referentiel servi aux clients : la liste des pays et des categories
     * couverts, deduite des regles en base plutot que figee dans le code — une
     * regle ajoutee au back-office apparait ainsi sans redeploiement.
     */
    public function reference(): array
    {
        $rules = CustomsRule::companywise()->active()->orderBy('country_name')->get();

        return [
            'countries' => $rules->unique('country_code')->values()
                ->map(fn ($rule) => ['code' => $rule->country_code, 'name' => $rule->country_name])
                ->all(),
            'categories' => $rules->pluck('goods_category')->unique()->sort()->values()
                ->map(fn ($slug) => ['slug' => $slug, 'name' => trans('customs.category_' . $slug)])
                ->all(),
        ];
    }
}
