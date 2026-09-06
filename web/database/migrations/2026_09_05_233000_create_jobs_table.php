<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * File d'attente des envois (décision D13).
 *
 * SMS, notifications poussées et courriels partaient jusqu'ici **dans la
 * requête HTTP** : l'agent qui changeait un statut de colis attendait
 * l'opérateur SMS (jusqu'à 80 s de délai d'attente, deux fois). Ils passent
 * désormais par cette table, vidée par un worker.
 *
 * Pilote `database` plutôt que Redis : un VPS mutualisé n'a que sa base, et le
 * volume d'un transporteur béninois tient largement dans une table.
 *
 * ⚠️ Sans worker en marche, les jobs s'accumulent ici sans partir. La commande
 * `php artisan beninlink:file-attente` le dit, et le guide de déploiement
 * fournit l'unité systemd.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
