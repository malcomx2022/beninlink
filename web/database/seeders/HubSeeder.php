<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Backend\Hub;

class HubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // S92 — les semences parlent du Bénin : six agences réelles du réseau
        // visé (Cotonou, Abomey-Calavi, Porto-Novo, Parakou, Bohicon). Les ids
        // 1 à 6 sont lus par les autres semences (livreur → 1, agence → 2,
        // marchand → 4) : l'ordre ne change pas. Numéros à dix chiffres (01…).
        $hubs = [
            [
                'name'            =>'Cotonou — Cadjèhoun',
                'phone'           =>'0197020001',
                'address'         =>'Cadjèhoun, Cotonou, Bénin',
                'current_balance' => '00'
            ],
            [
                'name'            =>'Cotonou — Akpakpa',
                'phone'           =>'0197020002',
                'address'         =>'Akpakpa, Cotonou, Bénin',
                'current_balance' => '00'
            ],
            [
                'name'            =>'Abomey-Calavi — Godomey',
                'phone'           =>'0197020003',
                'address'         =>'Godomey, Abomey-Calavi, Bénin',
                'current_balance' => '00'
            ],
            [
                'name'            =>'Porto-Novo — Ouando',
                'phone'           =>'0197020004',
                'address'         =>'Ouando, Porto-Novo, Bénin',
                'current_balance' => '00'
            ],
            [
                'name'            =>'Parakou — Centre',
                'phone'           =>'0197020005',
                'address'         =>'Centre-ville, Parakou, Bénin',
                'current_balance' => '00'
            ],
            [
                'name'            =>'Bohicon — Gare',
                'phone'           =>'0197020006',
                'address'         =>'Quartier de la gare, Bohicon, Bénin',
                'current_balance' => '00'
            ],
        ];

        for($n = 0; $n < sizeof($hubs); $n++)
        {
            $hub                  = new Hub();
            $hub->company_id      = 2;
            $hub->name            = $hubs[$n]['name'];
            $hub->phone           = $hubs[$n]['phone'];
            $hub->address         = $hubs[$n]['address'];
            $hub->current_balance = $hubs[$n]['current_balance'];
            $hub->save();
        }
    }
}
