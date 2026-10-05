<?php

namespace Database\Seeders;

use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\MerchantDeliveryCharge;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Services\Install\SeedAccounts;
use App\Models\Backend\Merchant;
use App\Models\User;
use App\Enums\Status;
use App\Enums\UserType;

class MerchantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $merchantUser                  = new User();
        $merchantUser->company_id      = 2;
        $merchantUser->name            = "Merchant";
        $merchantUser->mobile          = "01912938003";
        $merchantUser->email           = "merchant@wemaxdevs.com";
        $merchantUser->address         = "Mirpur-2,Dhaka";
        $merchantUser->password        = Hash::make(SeedAccounts::motDePasse($merchantUser->email)); // S87
        $merchantUser->user_type       = UserType::MERCHANT;
        $merchantUser->hub_id          = 4;
        $merchantUser->image_id        = 2;
        $merchantUser->unique_id       = 2024;
        $merchantUser->save();
        SeedAccounts::annoncer($this->command, $merchantUser->email);

        $merchant                      = new Merchant();
        $merchant->user_id             = $merchantUser->id;
        $merchant->business_name       = "WemaxDevs";
        $merchant->company_id          = 2;
        $merchant->merchant_unique_id  = 2024;
        $merchant->current_balance     = 00;
        $merchant->opening_balance     = 00;
        // $merchant->vat                 = 10;
        $merchant->cod_charges         = array(
            'inside_city'    => "1",
            'sub_city'       => "2",
            'outside_city'   => "3",
            'cedeao'         => "3",
        );
        $merchant->nid_id              = 4;
        $merchant->trade_license       = 5;
        $merchant->address             = "Dhaka";
        $merchant->save();

        // Barème négocié : une ligne par tranche ET par zone depuis l'étape 6
        // (D4). Le marchand de démonstration reprend les montants de la
        // société — ce qui compte ici est que le chemin négocié existe, pas
        // qu'il soit avantageux.
        $deliveryCharges = DeliveryCharge::with('category')->orderBy('position')->get();

        foreach ($deliveryCharges as $delivery) {
            $deliveryCharge                      = new MerchantDeliveryCharge();
            $deliveryCharge->company_id          = $delivery->company_id;
            $deliveryCharge->merchant_id         = $merchant->id;
            $deliveryCharge->delivery_charge_id  = $delivery->id;
            $deliveryCharge->category_id         = $delivery->category_id;
            $deliveryCharge->zone_id             = $delivery->zone_id;
            $deliveryCharge->weight              = $delivery->weight;
            $deliveryCharge->amount              = $delivery->amount;
            $deliveryCharge->status              = Status::ACTIVE;
            $deliveryCharge->save();
        }
    }
}
