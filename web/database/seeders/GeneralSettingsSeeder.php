<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Backend\GeneralSettings; 
use App\Models\Backend\Upload;

class GeneralSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //main
        $user           = new Upload();
        $user->original = "uploads/users/user8.png";
        $user->save(); 
        $user           = new Upload();
        $user->original = "uploads/users/user9.png";
        $user->save(); 

        $row               = new GeneralSettings();
        $row->name         = "BeninLink"; // S92 — la plateforme porte son nom
        $row->phone        = "0197010000";
        $row->email        = "contact@beninlink.app";
        $row->address      = "Cadjèhoun, Cotonou, Bénin";
        $row->currency     = "FCFA";
        $row->copyright    = "© Tous droits réservés — BeninLink.";
        $row->logo         = 8;
        $row->favicon      = 9;
        $row->par_track_prefix     = 'we';
        $row->invoice_prefix       = 'we';
        $row->current_version      = '1';
        $row->primary_color        = '#12503A'; // vert profond — charte BeninLink
        $row->accent_color         = '#E0A63C'; // ocre — actions clés, réglable (lot 3)
        $row->text_color           = '#ffffff';
        $row->save();


        //company
 
        $row               = new GeneralSettings();
        $row->name         = "Transporteur de démonstration"; // S92 — société 2 : le jeu de démonstration
        $row->phone        = "0197020000";
        $row->email        = "contact@transporteur-demo.bj";
        $row->address      = "Akpakpa, Cotonou, Bénin";
        $row->currency     = "FCFA";
        $row->copyright    = "© Tous droits réservés — Transporteur de démonstration.";
        $row->logo         = 8;
        $row->favicon      = 9;
        $row->par_track_prefix     = 'co';
        $row->invoice_prefix       = 'co';
        $row->current_version      = '1';
        $row->primary_color        = '#12503A'; // vert profond — charte BeninLink
        $row->accent_color         = '#E0A63C'; // ocre — actions clés, réglable (lot 3)
        $row->text_color           = '#ffffff'; 
        $row->subscription_id      = 1;
        $row->plan_id              = 1;
        $row->save();
 
    }
}
