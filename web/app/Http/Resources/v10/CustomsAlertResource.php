<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Alerte douaniere telle que l'affiche l'ecran « Alertes douanieres ».
 *
 * Les libelles (niveau, categorie, statut) sont rendus deja traduits : l'app ne
 * maintient pas un second jeu de libelles, comme pour les statuts de colis.
 */
class CustomsAlertResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'parcel_id'         => $this->parcel_id,
            'tracking_id'       => optional($this->parcel)->tracking_id,
            'country_code'      => $this->country_code,
            'country_name'      => $this->country_name,
            'goods_category'    => $this->goods_category,
            'category_name'     => trans('customs.category_' . $this->goods_category),
            /** 1 info, 2 avertissement, 3 bloquant (App\Enums\CustomsLevel). */
            'level'             => $this->level,
            'level_name'        => $this->level_name,
            'required_document' => $this->required_document,
            'message'           => $this->message,
            /** 1 en cours, 2 traitee (App\Enums\CustomsAlertStatus). */
            'status'            => $this->status,
            'status_name'       => $this->status_name,
            'created_at'        => dateFormat($this->created_at),
        ];
    }
}
