<?php
namespace App\Repositories\PayoutSetup;

use App\Enums\PayoutSetup;
use App\Enums\Status;
use App\Models\Backend\Setting;
use App\Repositories\PayoutSetup\PayoutSetupInterface;
class PayoutSetupRepository implements PayoutSetupInterface{

    public function update($payment_method,$request){
        // S21 — une passerelle désactivée (config/payments.php) ne s'active pas
        // par les réglages : ni clés enregistrées, ni statut actif.
        if (!gatewayEnabled($payment_method)) {
            return false;
        }

        // Champs à ne pas recopier dans `settings`. Le cas FedaPay y ajoute les
        // secrets laissés vides, qui veulent dire « ne pas changer ».
        $skip = ['_method','_token'];

        try {


            switch ($payment_method) {
                case PayoutSetup::STRIPE:
                    $request['stripe_status'] = $request->stripe_status == 'on'? Status::ACTIVE:Status::INACTIVE;
                    break;
                case PayoutSetup::SSL_COMMERZ:
                    $request['sslcommerz_testmode'] = $request->sslcommerz_testmode == 'on'? Status::ACTIVE:Status::INACTIVE;
                    $request['sslcommerz_status']   = $request->sslcommerz_status == 'on'? Status::ACTIVE:Status::INACTIVE;
                    break;
                case PayoutSetup::PAYPAL:
                    $request['paypal_status']       = $request->paypal_status == 'on'? Status::ACTIVE:Status::INACTIVE;
                    break;
                case PayoutSetup::SKRILL:
                    $request['skrill_status']      = $request->skrill_status == 'on'? Status::ACTIVE:Status::INACTIVE;
                    break;
                case PayoutSetup::BKASH:
                    $request['bkash_test_mode']   = $request->bkash_test_mode == 'on'? Status::ACTIVE:Status::INACTIVE;
                    $request['bkash_status']      = $request->bkash_status == 'on'? Status::ACTIVE:Status::INACTIVE;
                    break;
                case PayoutSetup::AAMARPAY:
                    $request['aamarpay_sendbox_mode']= $request->aamarpay_sendbox_mode == 'on'? Status::ACTIVE:Status::INACTIVE;
                    $request['aamarpay_status']      = $request->aamarpay_status == 'on'? Status::ACTIVE:Status::INACTIVE;
                    break;
                case PayoutSetup::RAZORPAY:
                    $request['razorpay_status']      = $request->razorpay_status == 'on'? Status::ACTIVE:Status::INACTIVE;
                    break;
                case PayoutSetup::FEDAPAY:
                    // F2/F3 — FedaPay n'avait aucun ecran : sa seule surface de
                    // reglage etait le `.env`. Trois valeurs vivent desormais
                    // dans `settings`, par societe, la ou `FedaPayGateway` les lit.
                    $request['fedapay_status'] = $request->fedapay_status == 'on'? Status::ACTIVE:Status::INACTIVE;

                    // Un champ de secret laisse vide veut dire « ne pas changer » :
                    // l'ecran ne reaffiche jamais un secret enregistre, donc un
                    // enregistrement de routine ne doit pas l'effacer.
                    foreach (['fedapay_secret_key', 'fedapay_webhook_secret'] as $secret) {
                        if (blank($request->input($secret))) {
                            $skip[] = $secret;
                        }
                    }

                    // Un locataire qui encaisse sur SON compte recoit des webhooks
                    // signes par le secret de CE compte. Enregistrer la cle sans le
                    // secret de webhook ferait rejeter tous ses webhooks : le
                    // paiement partirait, le portefeuille ne serait jamais credite.
                    // On refuse plutot que de livrer ce piege.
                    $cleEnregistree = Setting::where('company_id', settings()->id)
                        ->where('key', 'fedapay_secret_key')->value('value');
                    $secretEnregistre = Setting::where('company_id', settings()->id)
                        ->where('key', 'fedapay_webhook_secret')->value('value');

                    $cleApres    = blank($request->input('fedapay_secret_key')) ? $cleEnregistree : $request->input('fedapay_secret_key');
                    $secretApres = blank($request->input('fedapay_webhook_secret')) ? $secretEnregistre : $request->input('fedapay_webhook_secret');

                    if (!blank($cleApres) && blank($secretApres)) {
                        session()->flash('payout_setup_error', __('fedapay.webhook_secret_required'));

                        return false;
                    }
                    break;
                default:

                    break;
            }

            $requestData = $request->except($skip);
            foreach ($requestData as $key => $value) {
                // S6 — la recherche n'était PAS scopée par société : une société
                // qui enregistrait ses clés de passerelle retrouvait la ligne
                // d'une autre (la première portant cette clé) et l'écrasait.
                // Fuite et écrasement inter-locataires sur des clés de paiement.
                $setting          = Setting::where('company_id', settings()->id)
                                            ->where('key',$key)->first();
                if($setting){
                    $setting->value   = $value;
                    $setting->save();
                }else {
                    Setting::create(['company_id'=>settings()->id,'key' => $key,'value' => $value]);
                }

            }
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
