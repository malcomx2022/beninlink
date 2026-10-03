<?php

use App\Enums\UserType;
use App\Models\SuperAdminPermission;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

/**
 * **R6 (S75)** — le catalogue des catégories (`categorys`, sans `company_id`)
 * est réservé au super-administrateur, comme les devises depuis S55.
 *
 * Les routes ont changé de panneau (`super-admin/category`, `panel:super-admin`) ;
 * cette migration suit côté **données** : les super-administrateurs déjà créés
 * reçoivent les quatre droits `category_*` (le seeder ne rejoue pas en
 * production), et la table `super_admin_permissions` gagne la ligne qui les
 * offre aux suivants. Idempotente : relancée, elle n'ajoute rien deux fois.
 *
 * Les administrateurs de société gardent leur JSON tel quel : le droit qu'ils
 * portent encore ne mène plus nulle part, puisque le panneau les refuse avant
 * de lire les droits.
 */
return new class extends Migration
{
    private const DROITS = ['category_read', 'category_create', 'category_update', 'category_delete'];

    public function up(): void
    {
        if (!SuperAdminPermission::where('attribute', 'category')->exists()) {
            $ligne = new SuperAdminPermission();
            $ligne->attribute = 'category';
            $ligne->keywords = [
                'read' => 'category_read', 'create' => 'category_create',
                'update' => 'category_update', 'delete' => 'category_delete',
            ];
            $ligne->save();
        }

        foreach (User::where('user_type', UserType::SUPER_ADMIN)->get() as $superAdmin) {
            $droits = array_values(array_unique(array_merge((array) $superAdmin->permissions, self::DROITS)));
            if ($droits !== (array) $superAdmin->permissions) {
                $superAdmin->permissions = $droits;
                $superAdmin->save();
            }
        }
    }

    public function down(): void
    {
        SuperAdminPermission::where('attribute', 'category')->delete();
    }
};
