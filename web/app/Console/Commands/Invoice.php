<?php

namespace App\Console\Commands;

use App\Enums\Status;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\Merchant;
use App\Repositories\Invoice\InvoiceInterface;
use Illuminate\Console\Command;

/**
 * `php artisan invoice:generate` — les relevés de règlement dus, société par société.
 *
 * ## Pourquoi la boucle est explicite
 *
 * La commande bouclait sur `merchantIdlist()`, qui filtre les marchands par
 * `settings()->id`. Hors requête HTTP — et une tâche planifiée n'en a pas —
 * `settings()` n'a ni sous-domaine ni utilisateur connecté à lire : elle
 * retombe sur la **société 1**. La génération quotidienne ne servait donc que
 * la première société, et les autres transporteurs n'avaient **jamais** de
 * relevé, sans erreur ni alerte.
 *
 * D'où deux règles ici : la liste des sociétés est lue explicitement, et la
 * liste des marchands est filtrée par la société en cours de traitement — pas
 * par la session, qui n'existe pas.
 *
 * ## `--societe`
 *
 * Sans option, toutes les sociétés actives : c'est ce que fait le
 * planificateur. Avec, une seule — c'est ce que passe l'écran « génération des
 * relevés », pour qu'un administrateur ne déclenche jamais la génération des
 * autres transporteurs.
 *
 * La cadence, elle, reste celle du marchand (`merchants.payment_period`, en
 * jours) : cette commande propose, la fiche dispose.
 * Voir docs/guides/comptabilite/reprise-des-releves.md.
 */
class Invoice extends Command
{
    protected $signature = 'invoice:generate
        {--societe= : société à traiter ; par défaut toutes les sociétés actives}';

    protected $description = 'Génère les relevés de règlement dus, société par société';

    public function __construct(private InvoiceInterface $invoiceRepo)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $societes = $this->societes();

        if ($societes->isEmpty()) {
            $this->error('Aucune société à traiter.');

            return self::FAILURE;
        }

        $total = 0;

        foreach ($societes as $societe) {
            $releves = 0;

            $marchands = Merchant::where('company_id', $societe->id)
                ->orderByDesc('id')->pluck('id');

            foreach ($marchands as $marchandId) {
                if ($this->invoiceRepo->store($marchandId)) {
                    $releves++;
                }
            }

            $total += $releves;
            $this->line(sprintf(
                '%-40s %d marchand(s) examiné(s), %d relevé(s) émis',
                $societe->name,
                $marchands->count(),
                $releves
            ));
        }

        $this->info(sprintf('%d relevé(s) émis au total.', $total));

        return self::SUCCESS;
    }

    /** @return \Illuminate\Support\Collection<int, GeneralSettings> */
    private function societes()
    {
        $demandee = $this->option('societe');

        return $demandee
            ? GeneralSettings::where('id', (int) $demandee)->get()
            : GeneralSettings::where('status', Status::ACTIVE)->orderBy('id')->get();
    }
}
