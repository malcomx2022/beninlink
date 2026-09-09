<?php

namespace App\Console;
 
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
 
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        // `database:autobackup` tournait ici chaque nuit. Retirée le 2026-09-09 :
        // elle assemblait le SQL sans échapper les valeurs, lisait en `utf8` une
        // base `utf8mb4`, et expédiait toute la base en clair par courriel. Ses
        // sauvegardes ne se révélaient inutilisables qu'à la restauration.
        // La laisser planifiée aurait fait échouer une tâche par nuit, en
        // silence — `schedule:run` écrit dans /dev/null.
        // La sauvegarde du projet : docs/guides/infra/sauvegarde/

        $schedule->command('invoice:generate')->daily('13:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
