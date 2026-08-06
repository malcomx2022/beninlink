<?php

namespace Database\Seeders\Backend\SuperAdmin;

use App\Models\Backend\Superadmin\Plan;
use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Faker\Factory  as Faker;
class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Model::unguard();
        $faker = Faker::create(); 
        
        $freePlan               = new Plan(); 
        $freePlan->name         = 'Free trial';
        $freePlan->parcel_count = 3;
        $freePlan->deliveryman_count = 3;
        $freePlan->days_count   = 7; 
        $freePlan->price        = 0; 
        $freePlan->description  = $faker->unique()->realText(50);
        $freePlan->modules      = $this->FreePlan(); 
        $freePlan->position     = 1; 
        $freePlan->save();
 
        $plan               = new Plan(); 
        $plan->name         = 'Basic';
        $freePlan->deliveryman_count = 4;
        $plan->parcel_count = $faker->numberBetween(10,20);
        $plan->days_count   = $faker->numberBetween(30,60);
        $plan->price        = $faker->numberBetween(100,200); 
        $plan->description  = $faker->unique()->realText(100);
        $plan->modules      = $this->basicPlan();
        $plan->position     = 2;
        $plan->save();
 
        $plan               = new Plan(); 
        $plan->name         = 'Pro';
        $freePlan->deliveryman_count = 7;
        $plan->parcel_count = $faker->numberBetween(10,20);
        $plan->days_count   = $faker->numberBetween(30,60);
        $plan->price        = $faker->numberBetween(100,200); 
        $plan->description  = $faker->unique()->realText(100);
        $plan->modules      = $this->proPlan();
        $plan->position     = 3;

        $plan->save();
    }

    private function FreePlan(){
        return Permission::whereNotIn('attribute',[
            'users',
            // 'merchant',
            'payments',
            'hub_payments',
            'delivery_man',
            'delivery_category',
            'delivery_charge',
            'packaging',
            'account_heads',
            'salary',
        ])->pluck('attribute');
    }
    private function basicPlan(){
        return Permission::whereNotIn('attribute',[
            'users',
            // 'merchant',
            'payments',  
            'packaging',
            'account_heads',
            'salary',
        ])->pluck('attribute');
    }
    private function proPlan(){
        return Permission::pluck('attribute');
    }
}
