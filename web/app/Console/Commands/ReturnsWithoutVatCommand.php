<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Backend\GeneralSettings;
use App\Models\Backend\InvoiceParcel;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Models\Config;
use App\Services\Parcel\ReturnVat;
use Illuminate\Console\Command;

/**
 * `php artisan beninlink:retours-sans-tva` — les relevés déjà émis qui ont
 * facturé un retour **hors champ**.
 *
 * ## Ce qui s'est passé
 *
 * Jusqu'au 2026-10-03, `InvoiceRepository` forçait `vat_amount = 0` sur un
 * colis retourné : le frais de retour était facturé au marchand sans TVA, sans
 * que ce choix ait jamais été posé. Le porteur a tranché (**D2, question 6**) :
 * le retour est une prestation **taxable au taux normal**. Les retours reçus
 * depuis portent leur TVA (`ReturnVat`) ; les relevés émis **avant** l'ont
 * facturé sans, et l'assiette déclarée est sous-évaluée d'autant.
 *
 * ## Ce que fait la commande — et seulement cela
 *
 * Elle **constate** : relevé par relevé, le frais de retour facturé, la TVA
 * qui manque au taux du colis, et si le relevé est déjà payé. Elle n'écrit
 * **rien**, et n'a pas d'option pour le faire : un relevé émis ne se modifie
 * jamais directement (**D8**, **D9**), et la régularisation — avoir, relevé
 * complémentaire, ou rien si l'expert-comptable juge les montants non
 * significatifs — se décide avec lui, pas dans une commande. Quand il aura
 * tranché, la régularisation sera un lot à part, sur le modèle de
 * `beninlink:retours-annules --corriger`.
 *
 * La société est lue dans la liste des sociétés — jamais `settings()` (**F4**).
 */
class ReturnsWithoutVatCommand extends Command
{
    protected $signature = 'beninlink:retours-sans-tva
        {--societe= : ne traiter qu\'une société}
        {--marchand= : ne traiter qu\'un marchand (son identifiant)}';

    protected $description = 'Constate les relevés émis qui ont facturé un frais de retour sans TVA (D2, question 6) — ne corrige rien';

    public function handle(): int
    {
        $societes = $this->option('societe')
            ? GeneralSettings::where('id', (int) $this->option('societe'))->get()
            : GeneralSettings::orderBy('id')->get();

        if ($societes->isEmpty()) {
            $this->error('Société introuvable.');

            return self::FAILURE;
        }

        $totalReleves = 0;
        $totalColis = 0;
        $totalFrais = 0;
        $totalTva = 0;

        foreach ($societes as $societe) {
            $constat = $this->constater((int) $societe->id);
            if ($constat === []) {
                continue;
            }

            $this->line(sprintf('Société %d — %s', $societe->id, $societe->name));
            $this->table(
                ['Relevé', 'Marchand', 'Émis le', 'Statut', 'Colis en retour', 'Frais de retour facturés', 'TVA manquante'],
                array_map(fn (array $r) => [
                    $r['releve'], $r['marchand'], $r['emis'], $r['statut'], $r['colis'],
                    formatAmount($r['frais']), formatAmount($r['tva']),
                ], $constat),
            );

            $totalReleves += count($constat);
            $totalColis += array_sum(array_column($constat, 'colis'));
            $totalFrais += array_sum(array_column($constat, 'frais'));
            $totalTva += array_sum(array_column($constat, 'tva'));
        }

        if ($totalReleves === 0) {
            $this->info('Aucun relevé émis n’a facturé de frais de retour sans TVA.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn(sprintf(
            '%d relevé(s), %d colis en retour facturés sans TVA : %s de frais, %s de TVA non facturée.',
            $totalReleves,
            $totalColis,
            formatAmount($totalFrais),
            formatAmount($totalTva),
        ));
        $this->comment('Constat seul : rien n’a été écrit. Un relevé émis ne se modifie pas (D8, D9) —');
        $this->comment('la régularisation se décide avec l’expert-comptable (docs/guides/comptabilite/plan-de-comptes.md, question 6).');

        return self::SUCCESS;
    }

    /**
     * Les relevés d'une société dont au moins une ligne de retour est sans TVA.
     *
     * Une ligne de retour se reconnaît par son `return_charge` > 0 ; elle est
     * « sans TVA » quand son `vat_amount` est nul. Le taux manquant est celui du
     * colis (`parcels.vat`), à défaut celui de la société — lu **explicitement**
     * dans `configs`, pas par `settingHelper()` qui résout par la société ambiante.
     *
     * @return list<array{releve:string, marchand:string, emis:string, statut:string, colis:int, frais:int, tva:int}>
     */
    private function constater(int $companyId): array
    {
        $tauxSociete = (float) (Config::where('company_id', $companyId)->where('key', 'vat_rate')->value('value') ?: 0);

        $requete = Invoice::with('merchant')
            ->where('company_id', $companyId)
            ->orderBy('issued_on')->orderBy('sequence')->orderBy('id');
        if ($marchand = $this->option('marchand')) {
            $requete->where('merchant_id', (int) $marchand);
        }

        $constat = [];
        foreach ($requete->get() as $releve) {
            $lignes = InvoiceParcel::with('parcel')
                ->where('invoice_id', $releve->id)
                ->where('return_charge', '>', 0)
                ->where(fn ($q) => $q->whereNull('vat_amount')->orWhere('vat_amount', '=', 0))
                ->get();
            if ($lignes->isEmpty()) {
                continue;
            }

            $frais = 0;
            $tva = 0;
            foreach ($lignes as $ligne) {
                $taux = (float) ($ligne->parcel?->vat ?? 0);
                $taux = $taux > 0 ? $taux : $tauxSociete;
                $frais += (int) round((float) $ligne->return_charge);
                $tva += ReturnVat::arrondi((float) $ligne->return_charge, $taux);
            }

            $constat[] = [
                'releve' => (string) $releve->invoice_id,
                'marchand' => (string) ($releve->merchant?->business_name ?? $releve->merchant_id),
                'emis' => (string) $releve->issued_on,
                'statut' => (int) $releve->status === InvoiceStatus::PAID ? 'payé' : 'non payé',
                'colis' => $lignes->count(),
                'frais' => $frais,
                'tva' => $tva,
            ];
        }

        return $constat;
    }
}
