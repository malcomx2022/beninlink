<?php

namespace App\Observers;

use App\Models\Backend\Parcel;
use App\Services\Customs\CustomsService;

/**
 * Emet l'alerte douaniere d'un colis d'export, a chaque enregistrement.
 *
 * Pourquoi un observer, alors que le socle n'en a aucun : il existe SIX chemins
 * qui ecrivent un colis (store, duplicateStore et update, dans le repository
 * marchand comme dans celui de l'administration). Poser l'appel dans chacun,
 * c'est signer d'avance l'oubli du septieme — la session precedente a passe une
 * bonne partie de son temps a rattraper exactement ce motif avec `chargeDetails`,
 * ou une garde avait ete posee a six endroits et cassee partout d'un coup.
 *
 * Ici, un seul point d'accroche couvre tous les chemins, presents et futurs.
 *
 * Le cout est nul pour un colis domestique : `recordFor()` sort sans requete des
 * que `destination_country` est vide ou vaut BJ. Et il est idempotent, donc un
 * changement de statut ne cree pas d'alerte supplementaire.
 *
 * Le niveau BLOQUANT n'arrive jamais jusqu'ici : la validation refuse la
 * creation avant qu'un colis existe.
 */
class ParcelCustomsObserver
{
    public function __construct(private readonly CustomsService $customs)
    {
    }

    public function saved(Parcel $parcel): void
    {
        $this->customs->recordFor($parcel);
    }
}
