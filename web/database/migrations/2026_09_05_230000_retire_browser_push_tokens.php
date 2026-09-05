<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * S22 / D12 — retrait du push navigateur du back-office.
 *
 * Les valeurs de `users.web_token` sont des jetons émis par le projet Firebase
 * **de l'éditeur** (`we-courier-81101`), pour un canal qui ne livre plus rien.
 * Les garder n'ouvre aucune possibilité : ils ne servent qu'à désigner des
 * navigateurs auprès d'un tiers dont le transporteur n'a pas les clés.
 *
 * La colonne reste : c'est celle qu'un futur push navigateur réutilisera, avec
 * un projet maîtrisé (FCM v1) ou un abonnement Web Push (VAPID). Seules les
 * valeurs partent.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNotNull('web_token')->update(['web_token' => null]);
    }

    public function down(): void
    {
        // Rien à remettre : les jetons purgés ne valaient que pour un canal retiré.
    }
};
