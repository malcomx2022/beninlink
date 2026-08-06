<?php

namespace Database\Seeders\Backend\FrontWeb;

use App\Models\Backend\Upload;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement("INSERT INTO `sections` ( `company_id`, `type`, `key`, `value`, `created_at`, `updated_at`) VALUES
            (1, 1, 'title_1','SUB-DOMAIN BASED', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 1, 'title_2','COMPANY MANAGE', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 1, 'title_3','WITHOUT HASSLE', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 1, 'sub_title','We Committed to delivery - Make easy Efficient and quality delivery.', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 1, 'banner',null, '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 2, 'branch_icon','fa fa-warehouse', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 2, 'branch_count','7520', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 2, 'branch_title','Branches', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 2, 'parcel_icon','fa fa-gifts', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 2, 'parcel_count','50000000', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 2, 'parcel_title','Parcel Delivered', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 2, 'merchant_icon','fa fa-users', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 2, 'merchant_count','400000', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 2, 'merchant_title','Happy Merchant', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 2, 'reviews_icon','fa fa-thumbs-up', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 2, 'reviews_count','700', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 2, 'reviews_title','Positive Reviews', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 3, 'about_us', 'Fastest platform with all courier service features. Help you start, run and grow your courier service.', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 4, 'subscribe_title', 'Subscribe Us', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 4, 'subscribe_description','Get business news , tip and solutions to your problems our experts.', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 5, 'playstore_icon','fa-brands fa-google-play', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 5, 'playstore_link','https://drive.google.com/drive/folders/1jLe_s4F-HDSjI7dHPsen7vRUw2wv9SMi', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 5, 'ios_icon','fa-brands fa-app-store-ios', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 5, 'ios_link','https://drive.google.com/drive/folders/1jLe_s4F-HDSjI7dHPsen7vRUw2wv9SMi', '2023-01-27 17:30:40', '2023-01-27 17:30:40'),
            (1, 6, 'map_link','https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d542.6581052086841!2d90.3516149889463!3d23.798889773393963!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755c0e8a725cb8b%3A0x5a69b65edf9c7cf4!2sWemax%20IT!5e0!3m2!1sen!2sbd!4v1687082326781!5m2!1sen!2sbd', '2023-01-27 17:30:40', '2023-01-27 17:30:40')");
    }
}
