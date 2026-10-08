<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            "id"                => $this->id,
            "invoice_id"        => $this->invoice_id,
            "status"            => $this->InvoiceStatus,
            // ⚠️ W4 — cette ligne recomposait le net a partir de
            // `$this->PartialParcelsReturnMerchant` et
            // `$this->parcels_return_merchant_fees`, deux proprietes qui
            // n'existent nulle part : ni accesseur, ni relation, ni colonne.
            // Elles valaient donc toujours `null`, et `->sum()` sur `null` est
            // fatal : la liste des releves repondait 500 des qu'un seul releve
            // etait a rendre. L'ecran « Factures » de l'app marchand ne pouvait
            // fonctionner que tant qu'il etait vide.
            //
            // Le net vit deja sur le releve : `InvoiceRepository::store()` le
            // calcule une fois a l'emission (colis livres moins frais de
            // retour), et c'est lui que le PDF du chantier 4 publie sous
            // « net constate » (`SettlementStatement`). On le sert tel quel :
            // meme chiffre dans la liste, dans le PDF et dans le journal, et
            // une valeur qu'une mutation ulterieure d'un colis ne fait plus
            // bouger — ce qu'on attend d'un releve de reglement.
            "amount"            => amountValue($this->current_payable),
           "invoice_date"        =>dateFormat($this->invoice_date),
 
        ];
    }
}
