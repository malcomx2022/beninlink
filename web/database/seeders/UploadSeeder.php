<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Backend\Upload;

class UploadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user           = new Upload();
        $user->original = "uploads/users/user.png";
        $user->save();

        $user           = new Upload();
        $user->original = "uploads/users/user2.png";
        $user->save();

        $user           = new Upload();
        $user->original = "uploads/users/user3.png";
        $user->save();

        $user           = new Upload();
        $user->original = "uploads/users/user4.png";
        $user->save();

        $user           = new Upload();
        $user->original = "uploads/users/user5.png";
        $user->save();
       
        $user           = new Upload();
        $user->original = "uploads/users/user6.png";
        $user->save();
       
        $user           = new Upload();
        $user->original = "uploads/users/user7.png";
        $user->save();
    }
}
