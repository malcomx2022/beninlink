<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Backend\Merchantpanel\Invoice;
use App\Services\Invoicing\SyscohadaJournal;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * `php artisan beninlink:journal-syscohada` — l'extrait comptable d'une période.
 *
 * ## Pourquoi une commande
 *
 * L'export existait déjà, mais **relevé par relevé**, depuis le back-office.
 * Or ce que l'expert-comptable doit valider (décision **D2**), c'est un mois
 * entier : les trois journaux, leurs contreparties, et une balance qui tombe
 * juste. Cette commande produit exactement cela, sans demander à personne de
 * cocher trente cases dans un écran.
 *
 * ## Ce qu'elle garantit
 *
 * Chaque pièce est équilibrée, et le total débit égale le total crédit — le
 * contrôle est fait **avant** l'écriture du fichier, parce qu'un import
 * comptable refuse un lot déséquilibré sans dire lequel. Un déséquilibre
 * arrête la commande et nomme la pièce.
 *
 * ## Ce qu'elle ne décide pas
 *
 * Les numéros de comptes et les codes de journaux viennent de
 * `config/syscohada.php`. Ils restent une **proposition** tant que
 * l'expert-comptable ne les a pas arrêtés : voir
 * `docs/guides/comptabilite/plan-de-comptes.md`, qui liste ce qu'il doit
 * confirmer et se signe.
 */
class SyscohadaJournalCommand extends Command
{
    protected $signature = 'beninlink:journal-syscohada
        {--du= : début de la période (AAAA-MM-JJ) ; par défaut le 1er du mois dernier}
        {--au= : fin de la période (AAAA-MM-JJ) ; par défaut la fin de ce mois-là}
        {--societe= : société ; par défaut celle du premier relevé trouvé}
        {--payes : ne retenir que les relevés payés (les seuls à porter une écriture de banque)}
        {--fichier= : chemin du CSV à écrire ; sans lui, rien n\'est écrit}';

    protected $description = "Extrait des écritures SYSCOHADA d'une période, équilibre vérifié (D2)";

    public function handle(): int
    {
        $du = $this->option('du') ? Carbon::parse($this->option('du')) : now()->subMonthNoOverflow()->startOfMonth();
        $au = $this->option('au') ? Carbon::parse($this->option('au')) : (clone $du)->endOfMonth();

        $requete = Invoice::query()
            ->whereBetween('issued_on', [$du->toDateString(), $au->toDateString()])
            ->orderBy('issued_on')->orderBy('sequence');

        if ($societe = $this->option('societe')) {
            $requete->where('company_id', (int) $societe);
        }
        if ($this->option('payes')) {
            $requete->where('status', InvoiceStatus::PAID);
        }

        $releves = $requete->get();
        if ($releves->isEmpty()) {
            $this->warn("Aucun relevé émis entre le {$du->format('d/m/Y')} et le {$au->format('d/m/Y')}.");

            return self::SUCCESS;
        }

        $lignes = [];
        foreach ($releves as $releve) {
            $lignes = array_merge($lignes, SyscohadaJournal::linesFor($releve));
        }

        $balance = SyscohadaJournal::balance($lignes);

        $this->line("Période : du {$du->format('d/m/Y')} au {$au->format('d/m/Y')}");
        $this->table(
            ['Relevés', 'Pièces', 'Lignes', 'Total débit', 'Total crédit'],
            [[
                $releves->count(),
                $balance['pieces'],
                count($lignes),
                formatAmount($balance['debit']),
                formatAmount($balance['credit']),
            ]],
        );

        $this->table(['Journal', 'Lignes', 'Débit', 'Crédit'], $this->parJournal($lignes));

        if ($balance['desequilibrees'] !== [] || $balance['debit'] !== $balance['credit']) {
            $this->error('Écritures déséquilibrées : ' . implode(', ', $balance['desequilibrees']));
            $this->line("Rien n'a été écrit : un lot déséquilibré serait refusé à l'import.");

            return self::FAILURE;
        }

        $this->info('Équilibré : chaque pièce, et le total.');

        if ($fichier = $this->option('fichier')) {
            file_put_contents($fichier, SyscohadaJournal::csv($releves));
            $this->info("Écrit dans {$fichier}.");
        } else {
            $this->comment('Ajouter --fichier=<chemin> pour produire le CSV.');
        }

        $this->newLine();
        $this->comment('Comptes et journaux : config/syscohada.php — proposition tant que');
        $this->comment("l'expert-comptable ne l'a pas arrêtée (docs/guides/comptabilite/plan-de-comptes.md).");

        return self::SUCCESS;
    }

    /** @param array<int, array<string, string|int>> $lignes */
    private function parJournal(array $lignes): array
    {
        $par = [];
        foreach ($lignes as $ligne) {
            $code = (string) $ligne['journal'];
            $par[$code] ??= ['lignes' => 0, 'debit' => 0, 'credit' => 0];
            $par[$code]['lignes']++;
            $par[$code]['debit'] += (int) $ligne['debit'];
            $par[$code]['credit'] += (int) $ligne['credit'];
        }
        ksort($par);

        return array_map(
            fn (string $code) => [$code, $par[$code]['lignes'], formatAmount($par[$code]['debit']), formatAmount($par[$code]['credit'])],
            array_keys($par),
        );
    }
}
