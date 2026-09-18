<?php

namespace App\Services\Sms;

use App\Models\Backend\GeneralSettings;

/**
 * Le texte d'un SMS, composé en un seul endroit.
 *
 * ┌─ CE QUE CETTE CLASSE FERME ────────────────────────────────────────────────┐
 * │ Le socle écrivait chaque message en dur au point d'appel, précédé d'un      │
 * │ `if (session()->has('locale') && session()->get('locale') == 'bn')`.        │
 * │ Vingt-trois fois. Trois défauts, tous corrigés ici :                        │
 * │                                                                             │
 * │  1. **La langue était celle de l'agent.** Le destinataire d'un SMS n'est     │
 * │     presque jamais la personne qui a cliqué : c'est un client final, un      │
 * │     livreur, un marchand. Lire la session de l'opérateur pour décider de la  │
 * │     langue d'un tiers n'a pas de sens — et depuis le lot 5, `bn` ne peut     │
 * │     plus entrer en session, donc **tout partait en anglais**.                │
 * │                                                                             │
 * │  2. **La marque et la devise venaient de `settings()`.** Hors requête        │
 * │     locataire — le webhook FedaPay en est le cas type — `settings()` retombe │
 * │     sur la société 1 (constat F4) : le marchand recevait un SMS au nom d'un  │
 * │     AUTRE transporteur. `forCompany()` ferme cette porte comme               │
 * │     `SmsService::forCompany()` l'a fermée côté identifiants d'opérateur.     │
 * │                                                                             │
 * │  3. **Les montants étaient en taka.** `TK(1500)` était écrit dans le texte   │
 * │     anglais ; ailleurs le montant partait nu, sans devise du tout.           │
 * └─────────────────────────────────────────────────────────────────────────────┘
 *
 * La classe ne décide pas **si** un SMS part — `SmsSendSettingHelper()` et les
 * cases `send_sms_*` des formulaires gardent ce rôle — ni **comment** il part,
 * qui reste l'affaire de `SmsService` et du job `SendSms` (décision D13). Elle
 * ne fabrique que la phrase.
 */
final class SmsTemplate
{
    private ?string $marque = null;

    private ?string $devise = null;

    private function __construct(private readonly ?int $companyId)
    {
    }

    /**
     * Composer au nom d'une société donnée.
     *
     * À préférer partout où l'objet métier porte sa société (`$parcel->company_id`,
     * `$wallet->company_id`) : c'est la seule valeur juste hors requête locataire.
     */
    public static function forCompany(?int $companyId): self
    {
        return new self($companyId);
    }

    /** Composer au nom de la société courante — en requête authentifiée seulement. */
    public static function current(): self
    {
        return new self(null);
    }

    /**
     * Rend un gabarit de `lang/<locale>/sms.php`.
     *
     * `:brand` est fourni d'office : il termine presque tous les messages et le
     * point d'appel n'a pas à connaître la raison sociale du transporteur. Un
     * appelant peut le surcharger, ce que personne ne fait aujourd'hui.
     */
    public function render(string $cle, array $params = []): string
    {
        $params += ['brand' => $this->brand()];

        return trans('sms.' . $cle, $params, $this->locale());
    }

    /**
     * Montant destiné à un SMS : entier, devise suffixée, séparateur de milliers
     * en **espace ordinaire**.
     *
     * `formatAmount()` reste le point unique de mise en forme à l'écran et
     * sépare les milliers par une espace **insécable** (U+00A0), juste en
     * typographie française mais absente de l'alphabet GSM 03.38 : un seul de
     * ces caractères fait basculer le SMS en UCS-2, où il ne tient plus que
     * 70 caractères au lieu de 160 — donc coûte deux fois plus cher. On réutilise
     * les deux primitives du socle (`amountValue()` pour la valeur entière,
     * la devise de la société pour le symbole) et on ne change que l'espace.
     */
    public function amount($montant): string
    {
        return number_format(amountValue($montant), 0, ',', ' ') . ' ' . $this->currency();
    }

    /**
     * La langue d'un SMS.
     *
     * ⚠️ **Ni `app()->getLocale()`, ni la session, ni `config('app.locale')`.**
     * Les deux premiers valent la langue choisie par l'agent connecté au moment
     * où il clique ; le destinataire, lui, n'a pas d'avis dans cette requête.
     * Et la troisième non plus : `Application::setLocale()` **écrit** dans
     * `app.locale`, et `LanguageManager` l'appelle à chaque requête dont la
     * session porte une langue — `config('app.locale')` vaut donc lui aussi la
     * langue de l'agent. Le premier jet de ce service est tombé là-dedans, et
     * `SmsMessagesTest::test_the_agent_browsing_in_english_does_not_change_the_recipients_language`
     * l'a constaté.
     *
     * On prend `config('locales.default')`, que rien ne réécrit — `fr` par
     * défaut, et le français est la langue actée du produit. Une valeur que
     * `config/locales.php` ne sert pas retombe sur le français : mieux vaut un
     * SMS en français qu'un SMS en clés brutes.
     *
     * C'est ici, et nulle part ailleurs, qu'on branchera une langue enregistrée
     * par destinataire le jour où une colonne la portera : `render()` recevrait
     * le destinataire, cette méthode lirait sa préférence et retomberait sur la
     * langue de l'installation. Aucun des vingt-trois points d'appel ne bougera.
     */
    private function locale(): string
    {
        $langue = (string) config('locales.default', 'fr');

        return array_key_exists($langue, config('locales.supported', [])) ? $langue : 'fr';
    }

    /** Nom commercial du transporteur au nom duquel le message part. */
    private function brand(): string
    {
        if ($this->marque === null) {
            $this->marque = (string) ($this->reglage('name') ?? settings()?->name ?? '');
        }

        return $this->marque;
    }

    /** Devise de ce transporteur, avec le même repli que `currencySymbol()`. */
    private function currency(): string
    {
        if ($this->devise === null) {
            $valeur = $this->reglage('currency');
            $this->devise = blank($valeur) ? currencySymbol() : (string) $valeur;
        }

        return $this->devise;
    }

    /** Une colonne de `general_settings`, pour la société visée seulement. */
    private function reglage(string $colonne)
    {
        if ($this->companyId === null) {
            return null;
        }

        $valeur = GeneralSettings::where('id', $this->companyId)->value($colonne);

        return blank($valeur) ? null : $valeur;
    }
}
