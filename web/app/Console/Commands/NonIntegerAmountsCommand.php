<?php

namespace App\Console\Commands;

use App\Models\Backend\GeneralSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `php artisan beninlink:montants-non-entiers` — les montants stockés avec des
 * décimales, table par table.
 *
 * ## La décision qu'il tient (T2, S107)
 *
 * Le FCFA n'a pas de subdivision ; le socle stocke pourtant tous ses montants en
 * `decimal(…,2)`. Le porteur a tranché le 2026-10-06 : **pas de migration de
 * schéma**. La règle « entiers partout » vit au seul endroit où un taux devient
 * des francs (`ChargeCalculator::percentage()`, arrondi — `VatRoundingTest`) et
 * à l'affichage (`formatAmount()`, zéro décimale). Changer une quinzaine de
 * colonnes du socle serait une modification invasive de son cœur (règle d'or),
 * rendrait toute re-fusion de l'éditeur (T9) plus coûteuse, et une migration
 * MySQL interrompue ne se défait pas.
 *
 * Ce que la décision demande en échange, c'est de **pouvoir vérifier** qu'une
 * base vivante respecte la règle : c'est cette commande. Elle constate, ne
 * corrige rien, sort toujours en succès — comme les trois constats de
 * `docs/guides/infra/reprise/` § 8. La société est lue dans la liste des
 * sociétés, jamais `settings()` (F4).
 */
class NonIntegerAmountsCommand extends Command
{
    protected $signature = 'beninlink:montants-non-entiers
        {--societe= : ne traiter qu\'une société}';

    protected $description = 'Constate les montants stockés avec des décimales (T2 : le FCFA est entier, le schéma reste decimal) — ne corrige rien';

    /** Les colonnes monétaires qui portent une décision ou un relevé. */
    public const COLONNES = [
        'parcels'   => ['cash_collection', 'cod_amount', 'vat_amount', 'total_delivery_amount', 'current_payable'],
        'invoices'  => ['total_charge', 'cash_collection', 'current_payable'],
        'wallets'   => ['amount'],
        'merchants' => ['wallet_balance', 'current_balance'],
    ];

    public function handle(): int
    {
        $societes = $this->option('societe')
            ? GeneralSettings::where('id', (int) $this->option('societe'))->get()
            : GeneralSettings::orderBy('id')->get();

        if ($societes->isEmpty()) {
            $this->error('Société introuvable.');

            return self::FAILURE;
        }

        $total = 0;
        foreach ($societes as $societe) {
            $lignes = $this->constater((int) $societe->id);
            if ($lignes === []) {
                continue;
            }
            $this->line(sprintf('Société %d — %s', $societe->id, $societe->name));
            foreach ($lignes as [$table, $colonne, $nombre, $exemples]) {
                $this->line(sprintf('  - %s.%s : %d ligne(s) non entière(s) (id %s)', $table, $colonne, $nombre, $exemples));
            }
            $total += array_sum(array_column($lignes, 2));
        }

        if ($total === 0) {
            $this->info('Aucun montant stocké avec des décimales : la règle « FCFA entiers » tient dans la base.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn(sprintf('%d valeur(s) non entière(s). Constat seul : rien n’a été écrit.', $total));
        $this->comment('Un montant non entier vient d’une écriture qui a contourné ChargeCalculator (import, saisie directe en base, ancien code) :');
        $this->comment('remonter à la source avant de corriger la valeur (docs/DECISIONS_METIER.md, D15).');

        return self::SUCCESS;
    }

    /** @return list<array{0:string,1:string,2:int,3:string}> */
    private function constater(int $companyId): array
    {
        $lignes = [];
        foreach (self::COLONNES as $table => $colonnes) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            foreach ($colonnes as $colonne) {
                if (!Schema::hasColumn($table, $colonne)) {
                    continue;
                }
                $requete = DB::table($table)
                    ->whereNotNull($colonne)
                    ->whereRaw("ROUND({$colonne}) <> {$colonne}");
                if (Schema::hasColumn($table, 'company_id')) {
                    $requete->where('company_id', $companyId);
                }
                $nombre = (clone $requete)->count();
                if ($nombre === 0) {
                    continue;
                }
                $exemples = (clone $requete)->orderBy('id')->limit(5)->pluck('id')->implode(', ');
                $lignes[] = [$table, $colonne, $nombre, $exemples];
            }
        }

        return $lignes;
    }
}
