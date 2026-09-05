<?php

namespace App\Services\Parcel;

use App\Enums\Status;
use App\Exceptions\InsufficientWalletBalance;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\Wallet;
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
 * pour les colis créés depuis le téléphone. L'import Excel, lui, ne débitait
 * rien du tout (§15 de la revue) : il passe désormais par ici aussi.
 *
 * Tout passe donc par ce service, et le contrôle de solde avec — voir
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
    /** Libellé du mouvement, seul lien entre une écriture et son colis. */
    public const SOURCE = 'Parcel delivery charge - #';

    public function __construct(private WalletInterface $wallet)
    {
    }

    /**
     * Débit à la création d'un colis, sous plancher de solde.
     *
     * @throws InsufficientWalletBalance si le solde ne couvre pas les frais.
     */
    public function apply(Parcel $parcel): void
    {
        $merchant = $this->merchantForUpdate($parcel);
        if (blank($merchant)) {
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

        $this->write($merchant, $parcel, $required);
    }

    /**
     * Régularisation d'un colis **déjà créé** qui n'a jamais été débité.
     *
     * Sans plancher, et c'est délibéré : le colis existe, il a été livré ou il
     * le sera, la dette est acquise. Refuser ici laisserait la créance
     * invisible — exactement l'état qu'on vient corriger. Le solde peut donc
     * passer en négatif ; c'est le constat d'une dette, pas une autorisation
     * de découvert.
     *
     * Réservé à `beninlink:colis-non-debites`, qui n'agit que sur demande
     * explicite d'un opérateur.
     *
     * @return bool `false` si le marchand ne règle pas par portefeuille, ou si
     *              le colis porte déjà son écriture.
     */
    public function regularise(Parcel $parcel): bool
    {
        $merchant = $this->merchantForUpdate($parcel);
        if (blank($merchant) || $this->alreadyDebited($parcel)) {
            return false;
        }

        $this->write($merchant, $parcel, (float) $parcel->total_delivery_amount);

        return true;
    }

    /** Le colis porte-t-il déjà son écriture de portefeuille ? */
    public function alreadyDebited(Parcel $parcel): bool
    {
        return Wallet::where('merchant_id', $parcel->merchant_id)
            ->where('source', self::SOURCE . $parcel->tracking_id)
            ->exists();
    }

    /**
     * Le marchand du colis, ligne verrouillée — ou `null` s'il n'y a rien à
     * débiter : marchand introuvable, ou qui ne règle pas par portefeuille.
     * Celui-là paiera au relevé ; il ne doit être ni débité, ni refusé.
     */
    private function merchantForUpdate(Parcel $parcel): ?Merchant
    {
        $merchant = Merchant::whereKey($parcel->merchant_id)->lockForUpdate()->first();

        if (blank($merchant) || $merchant->wallet_use_activation != Status::ACTIVE) {
            return null;
        }

        return $merchant;
    }

    private function write(Merchant $merchant, Parcel $parcel, float $amount): void
    {
        $merchant->wallet_balance = (float) $merchant->wallet_balance - $amount;
        $merchant->save();

        $expense                = new Request();
        $expense['user_id']     = $merchant->user_id;
        $expense['merchant_id'] = $merchant->id;
        // La société du MARCHAND, jamais `settings()` : en console il n'y a pas
        // de locataire et `settings()` retombe sur la société 1, ce qui
        // rattacherait l'écriture au mauvais transporteur.
        $expense['company_id']  = $merchant->company_id;
        $expense['tracking_id'] = $parcel->tracking_id;
        $expense['amount']      = $amount;
        $this->wallet->expense($expense);
    }
}
