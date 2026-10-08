<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * **S111** — retire les « partenaires » de démonstration du socle.
 *
 * Les semences de We Courier posaient, pour chaque société, des vignettes tirées
 * de `public/frontend/images/partner/` : des logos de vraies marques (Huawei, UPS,
 * Digg, Atom, 500px) affichés sous « Nos partenaires » sur la page d'accueil
 * publique, sans aucune relation avec ces entreprises. Les fichiers sont supprimés
 * du dépôt ; ici, les lignes qui les désignaient.
 *
 * Seules les lignes dont l'image est un fichier de ce dossier du socle sont
 * touchées (le chemin y figure tel quel ou dans le JSON `{"original": …}` des
 * anciennes semences MySQL). Un partenaire saisi par un transporteur passe par
 * l'envoi d'image du back-office (`uploads/partner/…`) et reste en place.
 */
return new class extends Migration
{
    private const DOSSIER = 'frontend/images/partner/';

    public function up(): void
    {
        // Large en SQL (`LIKE` traite la barre oblique inverse du JSON différemment selon
        // le moteur), exact en PHP : le chemin, une fois les `\/` du JSON ramenés à `/`.
        $uploads = DB::table('uploads')
            ->where('original', 'like', '%partner%')
            ->get(['id', 'original'])
            ->filter(fn ($upload) => str_contains(str_replace('\\/', '/', (string) $upload->original), self::DOSSIER))
            ->pluck('id');

        if ($uploads->isEmpty()) {
            return;
        }

        DB::table('partners')->whereIn('image_id', $uploads)->delete();
        DB::table('uploads')->whereIn('id', $uploads)->delete();
    }

    /** Rien à remettre : ces logos n'auraient jamais dû être publiés. */
    public function down(): void
    {
    }
};
