<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Appareils abonnés aux notifications poussées.
 *
 * Le socle portait `users.device_token` — une seule colonne, un seul appareil,
 * et **jamais écrite** par aucun code (le push passait par des topics FCM). Un
 * marchand a couramment un téléphone et une tablette, et le même compte peut
 * ouvrir l'app marchand puis l'app livreur : il faut une ligne par appareil.
 *
 * `company_id` est redondant avec l'utilisateur, comme partout dans le socle :
 * il permet le scope `companywise()` sans jointure, et surtout de purger les
 * appareils d'une société sans passer par ses comptes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('general_settings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Jeton du service de push (ExponentPushToken[...] côté Expo).
            // Unique : un appareil qui change de main suit son dernier compte.
            $table->string('token')->unique();

            $table->string('platform', 16)->nullable();   // ios | android
            $table->string('app', 32)->nullable();        // merchant | deliveryman
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
