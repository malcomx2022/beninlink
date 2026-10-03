<?php

namespace App\Console\Commands;

use App\Models\Backend\GeneralSettings;
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
 * entier : les journaux, leurs contreparties, et une balance qui tombe juste.
 * Cette commande produit exactement cela, sans demander à personne de cocher
 * trente cases dans un écran.
 *
 * ## Ce qu'elle contient (S73)
 *
 * Chaque écriture dans la période de **sa** date : ventes et compensation à
 * l'émission du relevé, reversement à la date de l'ordre de virement
 * (`paid_on`, question 5), recharges de portefeuille à leur approbation et
 * remises d'espèces des livreurs à leur date (question 8). Les abonnements
 * SaaS n'y sont pas : ils sont dans les livres de la société éditrice.
 *
 * ## Ce qu'elle garantit
 *
 * Chaque pièce est équilibrée, et le total débit égale le total crédit — le
 * contrôle est fait **avant** l'écriture du fichier, parce qu'un import
 * comptable refuse un lot déséquilibré sans dire lequel. Un déséquilibre
 * arrête la commande et nomme la pièce.
 *
 * ## La société
 *
 * Lue dans la liste des sociétés, ou passée en option — jamais `settings()`,
 * qui hors requête retombe sur la société 1 (**F4**).
 */
class SyscohadaJournalCommand extends Command
{
    protected $signature = 'beninlink:journal-syscohada
        {--du= : début de la période (AAAA-MM-JJ) ; par défaut le 1er du mois dernier}
        {--au= : fin de la période (AAAA-MM-JJ) ; par défaut la fin de ce mois-là}
        {--societe= : société ; par défaut toutes}
        {--payes : ne retenir que les relevés payés (les seuls à porter une écriture de banque) — sans recharges ni remises}
        {--fichier= : chemin du CSV à écrire ; sans lui, rien n\'est écrit}';

    protected $description = "Extrait des écritures SYSCOHADA d'une période, équilibre vérifié (D2)";

    public function handle(): int
    {
        $du = $this->option('du') ? Carbon::parse($this->option('du'))->startOfDay() : now()->subMonthNoOverflow()->startOfMonth();
        $au = $this->option('au') ? Carbon::parse($this->option('au'))->endOfDay() : (clone $du)->endOfMonth();

        $societes = $this->option('societe')
            ? GeneralSettings::where('id', (int) $this->option('societe'))->get()
            : GeneralSettings::orderBy('id')->get();

        if ($societes->isEmpty()) {
            $this->error('Société introuvable.');

            return self::FAILURE;
        }

        $lignes = [];
        foreach ($societes as $societe) {
            $lignes = array_merge($lignes, SyscohadaJournal::periode((int) $societe->id, $du, $au, (bool) $this->option('payes')));
        }

        if ($lignes === []) {
            $this->warn("Aucune écriture entre le {$du->format('d/m/Y')} et le {$au->format('d/m/Y')}.");

            return self::SUCCESS;
        }

        $balance = SyscohadaJournal::balance($lignes);

        $this->line("Période : du {$du->format('d/m/Y')} au {$au->format('d/m/Y')}");
        $this->table(
            ['Sociétés', 'Pièces', 'Lignes', 'Total débit', 'Total crédit'],
            [[
                $societes->count(),
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
            file_put_contents($fichier, SyscohadaJournal::csvLignes($lignes));
            $this->info("Écrit dans {$fichier}.");
        } else {
            $this->comment('Ajouter --fichier=<chemin> pour produire le CSV.');
        }

        $this->newLine();
        $this->comment('Comptes et journaux : config/syscohada.php — trois numéros restent « à valider »');
        $this->comment("par l'expert-comptable (docs/guides/comptabilite/plan-de-comptes.md).");

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
