<?php


namespace App\Http\Services;

use App\Enums\Status;
use App\Jobs\SendSms;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\SmsSetting;
use App\Services\Sms\SmsTemplate;
use App\Support\BeninPhone;
use http\Client;
use Twilio\Rest\Client as TwilioClient;
class SmsService
{
    /**
     * Societe pour le compte de laquelle le SMS part, quand l'appelant la
     * connait. Voir `forCompany()`.
     */
    private ?int $companyId = null;

    /**
     * F4 — envoyer un SMS **au nom d'une societe donnee**.
     *
     * Les deux aides du socle, celle des reglages SMS et celle des reglages
     * generaux, retombent sur la societe 1 en l'absence d'utilisateur connecte.
     * Un SMS declenche hors session — le webhook FedaPay en est le cas type —
     * partait donc avec le nom commercial et la devise d'un AUTRE locataire, et
     * surtout avec les identifiants d'operateur SMS de la societe 1, factures
     * a elle.
     *
     * Renvoie une **copie** : le service est resolu depuis le conteneur et
     * partage, on ne doit pas lui coller un locataire de facon durable.
     */
    public function forCompany(?int $companyId): static
    {
        $copie = clone $this;
        $copie->companyId = $companyId;

        return $copie;
    }

    /**
     * Reglage SMS de la societe visee, avec repli sur le comportement du socle
     * quand aucune n'est precisee : en requete authentifiee, l'aide du socle est
     * deja scopee par la societe connectee.
     */
    private function setting(string $key)
    {
        if ($this->companyId === null) {
            return smsSettings($key);
        }

        return SmsSetting::where('company_id', $this->companyId)->where('key', $key)->value('value');
    }

    /** Nom commercial a afficher comme expediteur. */
    private function brand(): string
    {
        if ($this->companyId !== null) {
            $nom = GeneralSettings::where('id', $this->companyId)->value('name');
            if (!blank($nom)) {
                return (string) $nom;
            }
        }

        return (string) settings()?->name;
    }

    /**
     * D13 — l'envoi quitte la requête HTTP.
     *
     * Les deux points d'entrée du socle (`sendOtp`, `sendSms`) gardent leur
     * signature et leurs vingt-huit appels : ils ne parlent plus à l'opérateur,
     * ils **mettent en file**. La livraison elle-même vit dans `deliverOtp()` /
     * `deliverSms()`, appelées par `App\Jobs\SendSms`.
     *
     * La société est résolue **ici**, dans la requête, et voyage avec le job :
     * à l'exécution, `settings()` retomberait sur la société 1 (constat F4).
     *
     * Avec `QUEUE_CONNECTION=sync` — une installation sans worker — le job
     * s'exécute immédiatement : le comportement est exactement celui d'avant.
     */
    public function sendOtp($userPhone,$otpCode)
    {
        SendSms::dispatch($this->companyId ?? settings()?->id, (string) BeninPhone::normalize((string) $userPhone), (string) $otpCode, true);
    }

    public function sendSms($userPhone,$msg)
    {
        // S133 — un numéro rangé avant S133 (« 97000000 », « 0197000000 ») part avec son indicatif.
        SendSms::dispatch($this->companyId ?? settings()?->id, (string) BeninPhone::normalize((string) $userPhone), (string) $msg);
    }

    /** Livraison réelle d'un code de vérification. Appelée par le job. */
    public function deliverOtp($userPhone,$otpCode)
    {
        $smsSetting = $this->setting('reve_status');
        $smsTwilioSetting = $this->setting('twilio_status');
        if($smsSetting == Status::ACTIVE){
            $this->reveSms ('otp',$userPhone,$otpCode);
        }
        if($smsTwilioSetting == Status::ACTIVE){
            $this->twilioSms('otp',$userPhone,$otpCode);
        }

    }

    /** Livraison réelle d'un SMS. Appelée par le job. */
    public function deliverSms($userPhone,$msg)
    {

        $smsSetting       = $this->setting('reve_status');
        $smsTwilioSetting = $this->setting('twilio_status');
        $smsNexmoSetting  = $this->setting('nexmo_status');
        if($smsSetting == Status::ACTIVE){
            $this->reveSms ('sms',$userPhone,$msg);
        }
        if($smsTwilioSetting == Status::ACTIVE){
            $this->twilioSms('sms',$userPhone,$msg);
        }
        if($smsNexmoSetting == Status::ACTIVE){
            $this->nexmoSms('sms',$userPhone,$msg);
        }

    }

    private function reveSms ($type,$userPhone,$userMsg){
      
            try {
                    $api_key    = $this->setting('reve_api_key');
                    $api_secret = $this->setting('reve_secret_key');
                    $api_url    = $this->setting('reve_api_url');
                    $callerID   = $this->brand();
                    if($type == 'otp') {
                        // Le socle collait ici une phrase anglaise en dur — la
                        // seule que le socle ajoutait lui-meme au message, et
                        // la premiere que lit une PME qui s'inscrit.
                        // ⚠️ Twilio et Nexmo, eux, envoient le code NU : c'est
                        // un ecart du socle, pas une decision. Il est releve
                        // dans l'audit, pas corrige ici.
                        $message = SmsTemplate::forCompany($this->companyId)
                            ->render('otp', ['code' => $userMsg]);
                    }else {
                        $message = $userMsg;
                    }

                    $params = [
                        "apikey" => $api_key,
                        "secretkey" => $api_secret,
                        "callerID" => $callerID,
                        "toUser" => $userPhone,
                        "messageContent" => $message
                    ];

                    $url = $api_url . '?' . http_build_query($params);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // S12 : certificat verifie
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 80); 
                    $response = curl_exec($ch);
                    curl_close($ch);  
                    return $response;
            } catch (\Exception $exception) {
                return $exception;
            }

    }

    private function twilioSms($type,$receiverNumber,$message){

        try {

            $account_sid = $this->setting('twilio_sid');
            $auth_token = $this->setting('twilio_token');
            $twilio_number = $this->setting('twilio_from');

            $client = new TwilioClient($account_sid, $auth_token); 
            $client->messages->create($receiverNumber, [
                'from' => $twilio_number,
                'body' => $message]);  
        return true;
        } catch (\Exception $exception) { 
            return $exception;
        }
    }

    private function nexmoSms($type,$receiverNumber,$message) {

        try {
            $nexmoKey = $this->setting('nexmo_key');
            $nexmoSecretKey = $this->setting('nexmo_secret_key');
            $basic  = new \Vonage\Client\Credentials\Basic($nexmoKey, $nexmoSecretKey);
            $client = new \Vonage\Client($basic);
            $response = $client->sms()->send(
                new \Vonage\SMS\Message\SMS($receiverNumber, $this->brand(), $message)
            );
            $message = $response->current();

            if ($message->getStatus() == 0) {
                return true;
            } else {
                return false;
            }

        } catch (\Exception $e) {
            return $e;
        }
    }


}
