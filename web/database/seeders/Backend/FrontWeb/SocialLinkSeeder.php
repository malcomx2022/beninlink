<?php

namespace Database\Seeders\Backend\FrontWeb;

use App\Models\Backend\FrontWeb\SocialLink;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SocialLinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $socials = [
            [
                'name'  => 'facebook',
                'icon'  => 'fab fa-facebook-square',
                'link'  => 'https://www.facebook.com',
                'status' => 1
            ],
            [
                'name'  => 'Instagram',
                'icon'  => 'fab fa-instagram',
                'link'  => 'https://www.instagram.com',
                'status' => 1
            ],
            [
                'name'  => 'Twitter',
                'icon'  => 'fab fa-twitter',
                'link'  => 'https://www.twitter.com',
                'status' => 1
            ],
            [
                'name'  => 'Youtube',
                'icon'  => 'fab fa-youtube',
                'link'  => 'https://www.youtube.com',
                'status' => 0
            ],
            [
                'name'  => 'Whatsapp',
                'icon'  => 'fab fa-whatsapp',
                'link'  => 'https://www.whatsapp.com',
                'status' => 0
            ],
            [
                'name'  => 'Skype',
                'icon'  => 'fab fa-skype',
                'link'  => 'https://www.skype.com',
                'status' => 1
            ]
        ];

        foreach ($socials as  $key => $social) {
            $socialLink           = new SocialLink();
            $socialLink->company_id     = 1;
            $socialLink->name     = $social['name'];
            $socialLink->icon     = $social['icon'];
            $socialLink->link     = $social['link'];
            $socialLink->status   = $social['status'];
            $socialLink->position = ($key + 1);
            $socialLink->save();
        }
    }
}
