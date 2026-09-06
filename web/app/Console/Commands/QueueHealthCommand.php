<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `php artisan beninlink:file-attente` — l'état de la file des envois.
 *
 * ## Pourquoi cette commande existe
 *
 * Depuis **D13**, les SMS, les notifications poussées et les courriels ne
 * partent plus dans la requête HTTP : ils passent par une file, vidée par un
 * worker (`php artisan queue:work`). C'est ce qu'on voulait — l'agent qui
 * change un statut de colis n'attend plus l'opérateur SMS.
 *
 * Mais cela crée une panne d'un genre nouveau, et **silencieuse** : si le
 * worker s'arrête, l'application continue de répondre normalement, les colis
 * avancent, les écrans sont justes… et plus aucun SMS ne part. Personne ne
 * s'en aperçoit avant qu'un client se plaigne.
 *
 * Cette commande est le témoin. Elle ne répare rien : elle dit ce qu'il y a
 * dans la file, depuis combien de temps, et ce qui a échoué.
 *
 * ## Lecture
 *
 *   - **en attente** : normal si le nombre baisse ; un plus ancien job de
 *     quelques minutes est le signe d'un worker arrêté ;
 *   - **échoués** : à relancer avec `php artisan queue:retry all` une fois la
 *     cause corrigée (identifiants d'opérateur, réseau).
 *
 * Sortie 1 si le doyen dépasse le seuil : de quoi la brancher sur une
 * surveillance (cron + alerte) sans écrire de code de plus.
 */
class QueueHealthCommand extends Command
{
    protected $signature = 'beninlink:file-attente {--seuil=5 : âge maximal toléré du plus ancien job, en minutes}';

    protected $description = "État de la file des envois (SMS, push, e-mails) et détection d'un worker arrêté";

    public function handle(): int
    {
        $connexion = (string) config('queue.default');
        $this->line("Pilote de file : <options=bold>{$connexion}</>");

        if ($connexion === 'sync') {
            $this->warn("En `sync`, rien n'est mis en file : les envois repartent dans la requête HTTP.");
            $this->line('Voir D13 — passer `QUEUE_CONNECTION=database` et lancer un worker.');

            return self::SUCCESS;
        }

        if ($connexion !== 'database' || !Schema::hasTable('jobs')) {
            $this->warn("File `{$connexion}` : cette commande ne sait lire que le pilote `database`.");

            return self::SUCCESS;
        }

        $enAttente = DB::table('jobs')->count();
        $doyen = DB::table('jobs')->min('available_at');
        $echoues = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

        $seuil = max(1, (int) $this->option('seuil'));
        $ageMinutes = $doyen ? Carbon::createFromTimestamp($doyen)->diffInMinutes(now()) : 0;

        $this->newLine();
        $this->table(['Indicateur', 'Valeur'], [
            ['En attente', $enAttente],
            ['Plus ancien', $doyen ? "{$ageMinutes} min" : '—'],
            ['Échoués', $echoues],
        ]);

        if ($echoues > 0) {
            $this->warn("{$echoues} envoi(s) en échec. Les relire : `php artisan queue:failed`, les relancer : `php artisan queue:retry all`.");
        }

        if ($enAttente > 0 && $ageMinutes >= $seuil) {
            $this->error("Le plus ancien envoi attend depuis {$ageMinutes} min (seuil : {$seuil}).");
            $this->line('Le worker est probablement arrêté : `systemctl status beninlink-queue`.');

            return self::FAILURE;
        }

        $this->info($enAttente === 0 ? 'File vide : tout est parti.' : 'File en cours de traitement.');

        return self::SUCCESS;
    }
}
