<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

/**
 * `database:autobackup` — le socle livrait une sauvegarde qui ne se révélait
 * inutilisable qu'à la restauration, et elle tournait CHAQUE NUIT.
 *
 * Deux garanties, pas une : la commande refuse, ET elle n'est plus planifiée.
 * La première seule aurait fait échouer une tâche par nuit ; la seconde seule
 * aurait laissé le piège atteignable à la main.
 */
class AutoBackupNeutralizedTest extends TestCase
{
    public function test_the_socle_backup_command_refuses_to_run(): void
    {
        $this->artisan('database:autobackup')
            ->expectsOutputToContain('Cette commande est retirée')
            // Le refus renvoie vers ce qui la remplace : un message qui dit
            // seulement « non » envoie chercher ailleurs.
            ->expectsOutputToContain('docs/guides/infra/sauvegarde/')
            ->assertFailed();
    }

    public function test_it_is_no_longer_scheduled(): void
    {
        $planifiees = collect(app(Schedule::class)->events())
            ->map(fn ($event) => $event->command ?? '')
            ->filter(fn (string $c) => str_contains($c, 'database:autobackup'));

        $this->assertCount(
            0,
            $planifiees,
            "`database:autobackup` est de nouveau planifiée : elle échouerait chaque nuit, "
            . "et `schedule:run` écrit dans /dev/null."
        );
    }

    public function test_the_invoice_schedule_is_untouched(): void
    {
        // La déplanification ne devait retirer QU'ELLE. Ce test attrape un
        // retrait trop large dans le même geste.
        $planifiees = collect(app(Schedule::class)->events())
            ->map(fn ($event) => $event->command ?? '')
            ->filter(fn (string $c) => str_contains($c, 'invoice:generate'));

        $this->assertCount(1, $planifiees, "`invoice:generate` a disparu de la planification.");
    }
}
