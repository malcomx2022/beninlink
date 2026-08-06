<?php

namespace Database\Seeders\Backend\FrontWeb;

use App\Models\Backend\FrontWeb\Blog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        for ($i = 0; $i < 10; $i++) {
            $blog              = new Blog();
            $blog->company_id     = 1;
            $blog->title       = $faker->unique()->sentence(10);
            $blog->description = $faker->unique()->sentence(100);
            $blog->position    = $i;
            $blog->created_by  = 1;
            $blog->save();
        }
    }
}
