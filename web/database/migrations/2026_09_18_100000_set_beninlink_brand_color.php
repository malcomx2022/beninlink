<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 1 de la charte web (2026-09-18) — la couleur primaire passe au vert profond.
 *
 * Le site public tire sa couleur de `general_settings.primary_color`, réinjectée
 * dans `:root` par `frontend/layouts/master.blade.php`. Le CSS de la charte ne
 * peut donc pas suffire côté vitrine : sans cette migration, une installation
 * existante resterait au violet du socle We Courier.
 *
 * ⚠️ Ne réécrit QUE les lignes encore à la valeur d'usine `#7e0095`. Un
 * transporteur qui a choisi sa propre couleur la garde : le multi-tenant du socle
 * la lui promet, et la charte n'est que le défaut. C'est aussi ce qui rend cette
 * migration rejouable sans dégât.
 */
return new class extends Migration
{
    /** Violet du socle We Courier, valeur d'usine. */
    private const USINE = '#7e0095';

    /** Vert profond BeninLink — mobile/src/theme/colors.ts (`primary`). */
    private const CHARTE = '#12503A';

    public function up(): void
    {
        if (! Schema::hasTable('general_settings')) {
            return;
        }

        DB::table('general_settings')
            ->whereRaw('LOWER(primary_color) = ?', [self::USINE])
            ->update(['primary_color' => self::CHARTE]);

        $this->defautDeColonne(self::CHARTE);
    }

    public function down(): void
    {
        if (! Schema::hasTable('general_settings')) {
            return;
        }

        DB::table('general_settings')
            ->whereRaw('LOWER(primary_color) = ?', [strtolower(self::CHARTE)])
            ->update(['primary_color' => self::USINE]);

        $this->defautDeColonne(self::USINE);
    }

    /**
     * Le défaut de colonne est porteur, pas cosmétique :
     * `CompanyRepository::company_create()` ne renseigne jamais `primary_color`,
     * donc CHAQUE nouveau transporteur hérite de ce défaut. Le corriger dans la
     * migration de création ne sert que les bases neuves — sur une base déjà
     * migrée, il faut l'altérer ici.
     *
     * Volontairement en SQL direct et réservé à MySQL/MariaDB : `->change()` de
     * Laravel 10 exige `doctrine/dbal`, absent du socle, et la syntaxe
     * `ALTER ... SET DEFAULT` n'existe pas en SQLite — or la suite de tests
     * tourne sur SQLite en mémoire, où le défaut vient déjà de la migration de
     * création. Ne rien faire y est donc correct, pas un renoncement.
     */
    private function defautDeColonne(string $couleur): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        // MySQL n'accepte AUCUN paramètre lié dans du DDL : la valeur doit être
        // écrite dans l'instruction. Elle vient d'une constante de cette classe,
        // jamais d'une requête — la validation ci-dessous le garantit et ferme
        // la porte à une interpolation douteuse si quelqu'un change l'appelant.
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $couleur) !== 1) {
            throw new InvalidArgumentException("Couleur invalide : {$couleur}");
        }

        DB::statement(
            "ALTER TABLE `general_settings` ALTER COLUMN `primary_color` SET DEFAULT '{$couleur}'"
        );
    }
};
