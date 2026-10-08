<?php

namespace App\Http\Resources\v10;

use App\Enums\Wallet\WalletType;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un mouvement du porte-monnaie prépayé (`wallets`), pour `GET wallet/history`.
 *
 * Le montant sort en entier (XOF, sans décimales) et les libellés arrivent
 * déjà traduits : l'app les affiche, elle n'en dérive aucun.
 */
class WalletResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'transaction_id'    => (string) $this->transaction_id,
            /** Origine du mouvement, dans la langue négociée (S123) : « Recharge du portefeuille », « FedaPay »… */
            'source'            => $this->source_label,
            'amount'            => (int) round((float) $this->amount),
            'type'              => (int) $this->type,
            'typeName'          => $this->type == WalletType::EXPENSE
                                    ? __('wallet.expense')
                                    : __('wallet.income'),
            'payment_method'    => (int) $this->payment_method,
            'paymentMethodName' => trans('WalletPaymentMethod.' . $this->payment_method),
            'status'            => (int) $this->status,
            'statusName'        => trans('WalletStatus.' . $this->status),
            'created_at'        => dateTimeFormat($this->created_at),
        ];
    }
}
