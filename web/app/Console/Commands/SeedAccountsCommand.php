<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Install\SeedAccounts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * `php artisan beninlink:comptes-amorcage` — les comptes d'amorçage du socle
 * qui portent encore le mot de passe de son code source (**S87**).
 *
 * `deploy.sh` l'exécute après `git pull` et `optimize:clear` — la commande vit
 * dans la version déployée, pas dans celle du serveur (**S88**) — et avant de
 * migrer : une base amorcée avant S87 qui garde `admin@wemaxdevs.com` à
 * `12345678` s'arrête là, coupée puis remontée par le filet, intacte, jusqu'à
 * ce que ces comptes soient changés ou supprimés. Un compte supprimé n'est pas un blocage ; un compte
 * renommé mais au mot de passe public non plus — c'est le mot de passe que la
 * commande vérifie, par courriel d'amorçage.
 *
 * Elle ne lit aucune société et n'écrit rien (**F4**, lecture seule).
 */
class SeedAccountsCommand extends Command
{
    protected $signature = 'beninlink:comptes-amorcage';

    protected $description = 'Constate les comptes d\'amorçage du socle dont le mot de passe est encore celui de son code source — sort en erreur s\'il en reste';

    public function handle(): int
    {
        $publics = User::whereIn('email', SeedAccounts::COMPTES)->orderBy('id')->get()
            ->filter(fn (User $compte) => Hash::check(SeedAccounts::MOT_DE_PASSE_PUBLIC, (string) $compte->password));

        if ($publics->isEmpty()) {
            $this->info('Aucun compte d\'amorçage ne porte le mot de passe public du socle.');

            return self::SUCCESS;
        }

        foreach ($publics as $compte) {
            $this->line(sprintf('  <fg=red>✗</> %s (n° %d, %s) — mot de passe public', $compte->email, $compte->id, $compte->name));
        }

        $this->error(sprintf(
            '%d compte(s) d\'amorçage portent encore le mot de passe du code source du socle. '
            . 'Les changer depuis le back-office, ou les supprimer, puis relancer ce constat.',
            $publics->count()
        ));

        return self::FAILURE;
    }
}
