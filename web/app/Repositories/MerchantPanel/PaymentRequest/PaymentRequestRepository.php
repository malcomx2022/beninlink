<?php
namespace App\Repositories\MerchantPanel\PaymentRequest;

use App\Enums\UserType;
use App\Models\Backend\Payment;
use App\Models\Backend\Merchant;
use App\Models\MerchantPayment;
use App\Repositories\MerchantPanel\PaymentRequest\PaymentRequestInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * S7 — une demande de retrait n'est lisible, modifiable et supprimable que
 * par le marchand qui l'a déposée. Le socle lisait `Payment::where('id')`
 * nu (montant, compte destinataire, statut d'un autre marchand).
 */
class PaymentRequestRepository implements PaymentRequestInterface {

    private function ownedPayments(){
        return Payment::companywise()->where('merchant_id', Auth::user()->merchant?->id);
    }

    /**
     * S81 (T5) — le compte de versement nomme par la demande doit etre AU MARCHAND.
     *
     * `merchant_account` designe une ligne de `merchant_payments` : la banque ou le
     * numero Mobile Money ou l'argent partira. Le socle l'ecrivait tel quel ; un
     * marchand pouvait donc deposer une demande vers le compte d'un AUTRE marchand
     * (meme societe ou non), et l'ecran de traitement du back-office rendait les
     * coordonnees de ce compte-la. L'API avait ferme ce cas des S7
     * (`PaymentRequestController::ownsAccount()`, `PaymentRequestScopeTest`) ; le
     * panneau web, autre controleur, ne l'avait jamais fait. Le champ s'appelle
     * `merchant_account`, pas `*_id` : c'est l'angle mort T5.
     */
    private function compteDeVersementEtranger($request): bool
    {
        $compte = $request->merchant_account ?? null;

        return filled($compte) && !MerchantPayment::where('merchant_id', Auth::user()->merchant?->id)
            ->where('id', $compte)
            ->exists();
    }

    public function all(){
        //
    }

    public function get($id){
        return $this->ownedPayments()->find($id);
    }

    public function store($request){
        try {
            if ($this->compteDeVersementEtranger($request)) {
                return false; // S81 — avant toute ecriture
            }
            DB::beginTransaction();
            $payment                   = new Payment();
            $payment->company_id       = settings()->id;
            $payment->merchant_id      = Auth::user()->merchant->id;
            $payment->amount           = $request->amount;
            $payment->merchant_account = $request->merchant_account;
            $payment->description      = $request->description;
            $payment->created_by       = UserType::MERCHANT;
            $payment->save();
            DB::commit();
            return true;

        } catch (\Throwable $th) {
            DB::rollBack();
            return false;
        }
    }
    public function edit($id){
        //
    }

    public function update($request){
        try {
            if ($this->compteDeVersementEtranger($request)) {
                return false; // S81
            }
            DB::beginTransaction();
            $payment                   = $this->ownedPayments()->find($request->id);
            if (blank($payment)) {
                DB::rollBack();
                return false;
            }
            $payment->merchant_id      = Auth::user()->merchant->id;
            $payment->amount           = $request->amount;
            $payment->merchant_account = $request->merchant_account;
            $payment->description      = $request->description;
            $payment->created_by       = UserType::MERCHANT;
            $payment->save();

            DB::commit();
            return true;

        } catch (\Throwable $th) {
            DB::rollBack();
            return false;
        }
    }

    public function delete($id){
        return $this->ownedPayments()->whereKey($id)->delete();
    }

}
