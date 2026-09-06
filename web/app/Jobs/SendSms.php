<?php

namespace App\Jobs;

use App\Http\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Un SMS, envoyé hors de la requête HTTP (décision D13).
 *
 * Ce qu'on sort de la requête : l'appel à l'opérateur. `reveSms()` pose un
 * `CURLOPT_TIMEOUT` de **80 secondes**, et un changement de statut de colis en
 * déclenche jusqu'à deux (le livreur et le marchand) — un opérateur lent
 * faisait donc attendre l'agent qui vient de cliquer, pour une information qui
 * ne le concerne pas.
 *
 * ⚠️ La société est portée par le job, pas relue à l'exécution : hors requête
 * locataire, `settings()` retombe sur la société 1 et le SMS partirait avec le
 * nom commercial ET les identifiants d'opérateur d'un autre transporteur —
 * facturés à lui (c'est le constat F4, que `forCompany()` avait fermé côté
 * requête ; le sortir de la requête le rouvrirait sans cette précaution).
 */
class SendSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Trois tentatives : un opérateur injoignable l'est rarement longtemps. */
    public $tries = 3;

    /** Le message n'a plus d'intérêt une fois la journée passée. */
    public $backoff = [60, 300];

    public function __construct(
        public readonly ?int $companyId,
        public readonly string $phone,
        public readonly string $message,
        public readonly bool $otp = false,
    ) {
    }

    public function handle(SmsService $sms): void
    {
        $service = $sms->forCompany($this->companyId);

        $this->otp
            ? $service->deliverOtp($this->phone, $this->message)
            : $service->deliverSms($this->phone, $this->message);
    }

    /**
     * Un SMS perdu ne défait rien : le colis a changé de statut, la facture
     * existe. On le journalise pour qu'il soit visible dans la file échouée.
     */
    public function failed(\Throwable $e): void
    {
        Log::warning('SMS non remis', [
            'company_id' => $this->companyId,
            'phone' => $this->phone,
            'message' => $e->getMessage(),
        ]);
    }
}
