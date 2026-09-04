<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fil de notifications du marchand — source de l'écran `notifications` de mobile/.
 *
 * Table standard des notifications Laravel (canal `database`), telle que
 * `php artisan notifications:table` la génère. On adopte le mécanisme du
 * framework plutôt qu'une table maison : `User` porte déjà `Notifiable`, la
 * lecture (`unreadNotifications`, `markAsRead`) est fournie, et un canal
 * e-mail ou push pourra s'ajouter plus tard sans changer les émetteurs.
 *
 * Le socle We Courier n'a rien d'équivalent : `push_notifications` stocke les
 * messages rédigés par l'admin, pas ce qui arrive au marchand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
