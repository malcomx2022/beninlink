<?php

namespace App\Console\Commands;

use App\Enums\Status;
use App\Models\Backend\Merchant;
use App\Models\Backend\Parcel;
use App\Models\Backend\Wallet;
use App\Services\Parcel\WalletDebit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * `php artisan beninlink:colis-non-debites` — les colis d'un marchand au
 * portefeuille qui n'ont jamais été facturés.
 *
 * ## Ce qui s'est passé
 *
 * Pour un marchand au portefeuille prépayé, **le débit à la création est la
 * facturation** : il n'y a pas de seconde passe. Trois défauts ont laissé des
 * colis passer sans débit, chacun corrigé depuis :
 *
 *   - **W5** — les colis créés depuis l'app mobile : le code lisait
 *     `$request->merchant_id`, que cette requête ne porte pas, donc la condition
 *     n'était jamais vraie et le portefeuille n'était jamais débité ;
 *   - **le `catch` vide** autour du débit : quand il échouait, le colis restait
 *     et personne ne le savait ;
 *   - **l'import Excel**, qui ne touchait pas le portefeuille du tout.
 *
 * Les correctifs ne rattrapent pas le passé. Cette commande le constate.
 *
 * ## Ce qu'elle sait, et ce qu'elle ne sait pas
 *
 * Le seul lien entre un colis et son débit est le libellé du mouvement
 * (`WalletDebit::SOURCE` + numéro de suivi) : un colis sans écriture à son nom
 * n'a jamais été facturé. Ça, c'est certain.
 *
 * Ce qu'elle **ignore** : si le marchand réglait déjà par portefeuille à
 * l'époque. `merchants.wallet_use_activation` n'a pas d'historique. Un colis
 * créé alors que le marchand payait au relevé n'avait pas à être débité, et
 * apparaîtra pourtant ici s'il est passé au portefeuille depuis. C'est pourquoi
 * `--regulariser` ne balaie pas tout seul : on liste, un humain regarde, et on
 * régularise marchand par marchand avec `--marchand=`.
 *
 * ## Pourquoi la régularisation ignore le plancher de solde
 *
 * Le colis existe. Il a été livré, ou il le sera. La dette est acquise : la
 * refuser parce que le solde ne suit pas laisserait la créance invisible —
 * exactement l'état qu'on corrige. Le solde peut donc passer en négatif ; c'est
 * le constat d'une dette, pas une autorisation de découvert.
 */
class UndebitedParcelsCommand extends Command
{
    protected $signature = 'beninlink:colis-non-debites
        {--marchand= : Ne traiter qu\'un marchand (son identifiant)}
        {--regulariser : Écrire les débits manquants}
        {--force : Autoriser --regulariser en production}';

    protected $description = 'Liste les colis de marchands au portefeuille qui n\'ont jamais été débités';

    public function handle(WalletDebit $debit): int
    {
        $marchands = Merchant::where('wallet_use_activation', Status::ACTIVE)
            ->when($this->option('marchand'), fn ($q, $id) => $q->whereKey($id))
            ->get();

        if ($marchands->isEmpty()) {
            $this->info('Aucun marchand ne règle par portefeuille : rien à vérifier.');

            return self::SUCCESS;
        }

        $parLigne = [];
        $total    = 0.0;
        $nombre   = 0;

        foreach ($marchands as $marchand) {
            $colis = $this->colisNonDebites($marchand);
            if ($colis->isEmpty()) {
                continue;
            }

            $montant  = (float) $colis->sum('total_delivery_amount');
            $total   += $montant;
            $nombre  += $colis->count();

            $parLigne[] = [
                $marchand->id,
                $marchand->business_name,
                $colis->count(),
                formatAmount($montant),
                optional($colis->min('created_at'))->format('Y-m-d') ?? '—',
                optional($colis->max('created_at'))->format('Y-m-d') ?? '—',
            ];
        }

        if ($parLigne === []) {
            $this->info('Aucun colis non débité : cette installation est à jour.');

            return self::SUCCESS;
        }

        $this->warn(sprintf('%d colis jamais facturés, pour %s.', $nombre, formatAmount($total)));
        $this->newLine();
        $this->table(['Marchand', 'Nom', 'Colis', 'Montant dû', 'Du', 'Au'], $parLigne);

        $this->newLine();
        $this->line('⚠️ `wallet_use_activation` n\'a pas d\'historique : un colis créé quand le');
        $this->line('   marchand réglait encore au relevé apparaît ici s\'il est passé au');
        $this->line('   portefeuille depuis. Vérifier avant de régulariser.');

        if (!$this->option('regulariser')) {
            $this->newLine();
            $this->comment('Ajouter --regulariser (et de préférence --marchand=<id>) pour écrire les débits manquants.');

            return self::SUCCESS;
        }

        if (app()->environment('production') && !$this->option('force')) {
            $this->newLine();
            $this->error('Refusé en production sans --force : la régularisation débite de l\'argent réel.');

            return self::FAILURE;
        }

        $ecrits = 0;
        foreach ($marchands as $marchand) {
            foreach ($this->colisNonDebites($marchand) as $colis) {
                // Une transaction par colis : un incident n'annule pas les
                // régularisations déjà écrites, et chacune reste vérifiable
                // ligne à ligne dans l'historique du portefeuille.
                DB::transaction(function () use ($debit, $colis, &$ecrits) {
                    if ($debit->regularise($colis)) {
                        $ecrits++;
                    }
                });
            }
        }

        $this->newLine();
        $this->info(sprintf('%d débit(s) écrit(s).', $ecrits));

        return self::SUCCESS;
    }

    /**
     * Les colis de ce marchand qui ne portent aucune écriture de portefeuille.
     *
     * Le rapprochement se fait sur le libellé du mouvement, seul lien existant
     * entre une écriture et son colis — il n'y a pas de `parcel_id` sur
     * `wallets`.
     */
    private function colisNonDebites(Merchant $marchand)
    {
        $debites = Wallet::where('merchant_id', $marchand->id)
            ->where('source', 'like', WalletDebit::SOURCE . '%')
            ->pluck('source')
            ->flip();

        // Le tri se fait en PHP, pas par un `whereNotIn` : un marchand actif
        // depuis un an a des milliers d'ecritures, et la clause IN
        // correspondante depasse la limite de variables du pilote.
        return Parcel::where('merchant_id', $marchand->id)
            ->get(['id', 'tracking_id', 'merchant_id', 'total_delivery_amount', 'created_at'])
            ->reject(fn (Parcel $colis) => $debites->has(WalletDebit::SOURCE . $colis->tracking_id));
    }
}
