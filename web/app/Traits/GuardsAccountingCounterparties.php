<?php

namespace App\Traits;

use App\Models\Backend\Account;
use App\Models\Backend\DeliveryMan;
use App\Models\Backend\Hub;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\User;

/**
 * S48 — les CONTREPARTIES d'une ecriture comptable.
 *
 * Le motif se repete pour la quatrieme fois : S45 l'a trouve sur l'agent d'un
 * mouvement de colis, S46 sur les catalogues d'un colis, S47 sur le catalogue
 * d'un compte. A chaque fois la RESSOURCE etait gardee et le SECOND
 * identifiant ne l'etait pas.
 *
 * Le module comptable ne fait pas exception. Sa ressource est gardee depuis
 * S30 — `Income::companywise()->find($id)`, `Expense::companywise()->find($id)`
 * — mais une ecriture ne touche pas que sa propre ligne : elle DEPLACE DE
 * L'ARGENT sur une contrepartie nommee dans la requete.
 *
 * | Contrepartie | Ce que le socle en faisait |
 * |---|---|
 * | `Merchant::find()` | `current_balance ± amount`, puis `save()`, plus un releve |
 * | `DeliveryMan::find()` | idem |
 * | `Hub::find()` | idem |
 * | `User::find()` | le salaire verse, la ligne de paie |
 * | `Account::find()` | le compte de TRESORERIE mouvemente |
 * | `Parcel::find()` | la piece rattachee a l'ecriture |
 *
 * Douze lectures nues sur quatre depots — recette, depense, salaire,
 * encaissement livreur. Une ecriture saisie chez nous creditait ou debitait le
 * solde d'un tiers d'une AUTRE societe, en lui attachant un releve portant
 * NOTRE `company_id`.
 *
 * ⚠️ **La garde vit ici, et non recopiee quatre fois**, parce que les quatre
 * depots ecrivent la meme famille de contreparties. Le sabotage doit viser
 * chaque POINT D'APPEL — une aide partagee donne l'illusion d'une couverture
 * que ses appelants n'ont pas (lecon de S46, puis de S47).
 */
trait GuardsAccountingCounterparties
{
    /**
     * S51 — la boucle de garde vit desormais une couche plus bas, parce que les
     * portes de CREATION du socle nomment les memes ressources autrement. Le
     * comportement des huit points d'appel de S48 est inchange : meme carte,
     * meme refus, memes sabotages rouges.
     */
    use GuardsForeignIdentifiers;

    /** Les contreparties d'une ecriture, et le modele qui porte leur perimetre. */
    private const CONTREPARTIES = [
        'merchant_id'     => Merchant::class,
        'delivery_man_id' => DeliveryMan::class,
        'hub_id'          => Hub::class,
        'user_id'         => User::class,
        'account_id'      => Account::class,
        'parcel_id'       => Parcel::class,
    ];

    /**
     * Vrai si une contrepartie NOMMEE dans la requete n'est pas de la maison.
     *
     * Chacune reste FACULTATIVE : le poste comptable decide laquelle est
     * renseignee, et un champ absent laisse l'ecriture sans contrepartie, comme
     * avant. Seule une contrepartie fournie ET etrangere fait refuser — avant
     * la moindre ecriture, pour qu'un refus ne laisse pas de releve orphelin.
     */
    protected function contrepartieHorsPerimetre($request): bool
    {
        return $this->identifiantsHorsPerimetre($request, self::CONTREPARTIES);
    }
}
