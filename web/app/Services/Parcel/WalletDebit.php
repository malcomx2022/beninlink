<?php

namespace App\Services\Parcel;

use App\Enums\Status;
use App\Exceptions\InsufficientWalletBalance;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Repositories\Wallet\WalletInterface;
use Illuminate\Http\Request;

/**
 * Débiter le portefeuille d'un marchand des frais d'un colis qu'on vient de créer.
 *
 * Le socle recopiait ce bloc **quatre fois** — création et duplication, côté
 * back-office et côté marchand/API — chaque copie enveloppée d'un
 * `try { … } catch (\Throwable $th) { }` vide. Quatre copies, c'est quatre
 * occasions de diverger, et elles avaient déjà divergé : deux d'entre elles
 * chargeaient `Merchant::find($request->merchant_id)`, un champ que l'app
 * mobile n'envoie pas, si bien que le débit n'avait tout simplement jamais lieu
 * pour les colis créés depuis le téléphone.
 *
 * Tout passe désormais par ici, et le contrôle de solde avec — voir
 * `InsufficientWalletBalance` pour ce qui manquait.
 *
 * Trois points de méthode :
 *
 * 1. Le marchand vient du **colis**, pas de la requête. Le colis sait à qui il
 *    est ; la requête, selon le chemin, ne le dit pas.
 * 2. La ligne marchand est verrouillée (`lockForUpdate`) : sans cela, deux
 *    créations simultanées lisent le même solde, le trouvent toutes deux
 *    suffisant, et débitent deux fois — le contrôle ne tiendrait que tant
 *    qu'un seul colis part à la fois.
 * 3. Rien n'est rattrapé ici. L'appelant crée le colis dans une transaction ;
 *    un échec du débit doit l'annuler, pas la laisser à moitié faite.
 */
class WalletDebit
{
    public function __construct(private WalletInterface $wallet)
    {
    }

    /**
     * @throws InsufficientWalletBalance si le solde ne couvre pas les frais.
     */
    public function apply(Parcel $parcel): void
    {
        $merchant = Merchant::whereKey($parcel->merchant_id)->lockForUpdate()->first();

        // Marchand introuvable, ou qui ne règle pas par portefeuille : rien à
        // débiter, et surtout rien à refuser. Il paiera au relevé.
        if (blank($merchant) || $merchant->wallet_use_activation != Status::ACTIVE) {
            return;
        }

        $required  = (float) $parcel->total_delivery_amount;
        $available = (float) $merchant->wallet_balance;

        // Le socle compare le sous-total HORS TVA au solde : c'est sa
        // convention, et `total_delivery_amount` est aussi ce qu'il débite.
        // On garde les deux alignés plutôt que de refuser sur un montant et
        // d'en prélever un autre.
        if ($required > $available) {
            throw new InsufficientWalletBalance($merchant, $required, $available);
        }

        $merchant->wallet_balance = $available - $required;
        $merchant->save();

        $expense                = new Request();
        $expense['user_id']     = $merchant->user_id;
        $expense['merchant_id'] = $merchant->id;
        $expense['tracking_id'] = $parcel->tracking_id;
        $expense['amount']      = $required;
        $this->wallet->expense($expense);
    }
}
