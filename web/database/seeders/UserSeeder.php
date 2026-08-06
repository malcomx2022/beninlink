<?php

namespace Database\Seeders;

use App\Enums\BooleanStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Enums\UserType;
use App\Models\Backend\Subscription;
use App\Models\RoleUser;
use App\Models\SuperAdminPermission;
use App\Models\Tenant;
use App\Repositories\GeneralSettings\GeneralSettingsInterface;
use App\Repositories\Superadmin\Company\CompanyRepository;
use App\Repositories\User\UserInterface;
use Carbon\Carbon;
use Illuminate\Support\Arr;
 
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {


        $user                        = new User();
        $user->name                  = "Admin";
        $user->email                 = "admin@wemaxdevs.com";
        $user->password              = Hash::make('12345678');
        $user->mobile                = "01912938002";
        $user->nid_number            = "12345678912";
        $user->user_type             = UserType::SUPER_ADMIN;
        $user->image_id              = 1;
        $user->joining_date          = "2022-01-01";
        $user->salary                =  7000;
        $user->address               = "Mirpur-10, Dhaka-1216";
        $user->role_id               = 1;
        $user->permissions           = (new RoleSeeder)->superadminPermission();
        $user->save();

        $user                        = new User();
        $user->name                  = "Company";
        $user->company_owner         = BooleanStatus::YES;
        $user->company_id            = 2;
        $user->email                 = "company@wemaxdevs.com";
        $user->password              = Hash::make('12345678');
        $user->mobile                = "01912938002";
        $user->nid_number            = "12345678912";
        $user->user_type             = UserType::ADMIN;
        $user->image_id              = 1;
        $user->joining_date          = "2022-01-01";
        $user->salary                =  7000;
        $user->address               = "Mirpur-10, Dhaka-1216";
        $user->role_id               = 2;
        $user->permissions           = $this->AdminPermissions();
        $user->save();

        $tenant1 =  Tenant::create(['id' => 'company','company_id'=>2]);
        $tenant1->domains()->create(['domain' => 'company.'.get_host(),'domain_name'=>'company']);
     
        (new CompanyFrontendDataSeeder())->companySiteData(2);

        $subscription               = new Subscription();
        $subscription->company_id   = 2;
        $subscription->user_id      = $user->id;
        $subscription->plan_id      = 3;
        $subscription->parcel_count = 100;
        $subscription->deliveryman_count = 0;
        $subscription->days_count   = 365;
        $subscription->price        = 450;
        $subscription->start_date   = Carbon::now()->subDays(10)->toDateTimeString();
        $subscription->expired_date = Carbon::now()->addDays(365)->toDateTimeString();
        $subscription->save();


        // Account user
        $user                        = new User();
        $user->name                  = "Branch";
        $user->company_id            = 2;
        $user->email                 = "branch@wemaxdevs.com";
        $user->password              = Hash::make('12345678');
        $user->mobile                = "01478523690";
        $user->nid_number            = "12345678910";
        $user->designation_id        = 2;
        $user->department_id         = 2;
        $user->hub_id                = 2;
        $user->user_type             = UserType::ADMIN;
        $user->image_id              = 6;
        $user->salary                =  9000;
        $user->joining_date          = "2022-04-20";
        $user->address               = "Mirpur-10, Dhaka-1216";
        $user->role_id               = 2;
        $user->permissions           = $this->userPermissions();
        $user->save();
 
    }



    private function  AdminPermissions()
    {
        return [
            'dashboard_read',
            'calendar_read',
            'total_parcel',
            'total_user',
            'total_merchant',
            'total_delivery_man',
            'total_hubs',
            'total_accounts',
            'total_parcels_pending',
            'total_pickup_assigned',
            'total_received_warehouse',
            'total_deliveryman_assigned',
            'total_partial_deliverd',
            'total_parcels_deliverd',

            'recent_accounts',
            'recent_salary',
            'recent_hub',
            'all_statements',
            'income_expense_charts',
            'merchant_revenue_charts',
            'deliveryman_revenue_charts',
            'courier_revenue_charts',
            'recent_parcels',
            'bank_transaction',

            'log_read',
            // 'database_backup_read',

            'hub_read',
            'hub_create',
            'hub_update',
            'hub_delete',
            'hub_incharge_read',
            'hub_incharge_create',
            'hub_incharge_update',
            'hub_incharge_delete',
            'hub_incharge_assigned',

            'account_read',
            'account_create',
            'account_update',
            'account_delete',

            'income_read',
            'income_create',
            'income_update',
            'income_delete',

            'expense_read',
            'expense_create',
            'expense_update',
            'expense_delete',

            'todo_read',
            'todo_create',
            'todo_update',
            'todo_delete',

            'account_heads_read',

            'salary_read',
            'salary_create',
            'salary_update',
            'salary_delete',



            'support_read',
            'support_create',
            'support_update',
            'support_delete',
            'support_reply',
            'support_status_update',

            'fund_transfer_read',
            'fund_transfer_create',
            'fund_transfer_update',
            'fund_transfer_delete',

            'role_read',
            'role_create',
            'role_update',
            'role_delete',

            'designation_read',
            'designation_create',
            'designation_update',
            'designation_delete',

            'department_read',
            'department_create',
            'department_update',
            'department_delete',

            'user_read',
            'user_create',
            'user_update',
            'user_delete',
            'permission_update',

            'merchant_read',
            'merchant_create',
            'merchant_update',
            'merchant_delete',
            'merchant_view',

            'merchant_delivery_charge_read',
            'merchant_delivery_charge_create',
            'merchant_delivery_charge_update',
            'merchant_delivery_charge_delete',

            'merchant_shop_read',
            'merchant_shop_create',
            'merchant_shop_update',
            'merchant_shop_delete',

            'merchant_payment_read',
            'merchant_payment_create',
            'merchant_payment_update',
            'merchant_payment_delete',

            'payment_read',
            'payment_create',
            'payment_update',
            'payment_delete',
            'payment_reject',
            'payment_process',

            'hub_payment_read',
            'hub_payment_create',
            'hub_payment_update',
            'hub_payment_delete',
            'hub_payment_reject',
            'hub_payment_process',

            'hub_payment_request_read',
            'hub_payment_request_create',
            'hub_payment_request_update',
            'hub_payment_request_delete',

            'parcel_read',
            'parcel_create',
            'parcel_update',
            'parcel_delete',


            'delivery_man_read',
            'delivery_man_create',
            'delivery_man_update',
            'delivery_man_delete',
            'subscription_read',

            'delivery_category_read',
            'delivery_category_create',
            'delivery_category_update',
            'delivery_category_delete',

            'delivery_charge_read',
            'delivery_charge_create',
            'delivery_charge_update',
            'delivery_charge_delete',

            'delivery_type_read',
            'delivery_type_status_change',

            'liquid_fragile_read',
            'liquid_fragile_update',
            'liquid_status_change',

            'sms_settings_read',
            'sms_settings_create',
            'sms_settings_update',
            'sms_settings_delete',

            'sms_send_settings_read',
            'sms_send_settings_create',
            'sms_send_settings_update',
            'sms_send_settings_delete',

            'general_settings_read',
            'general_settings_update',

            'notification_settings_read',
            'notification_settings_update',

            'push_notification_read',
            'push_notification_create',
            'push_notification_update',
            'push_notification_delete',

            'packaging_read',
            'packaging_create',
            'packaging_update',
            'packaging_delete',
            'parcel_status_update',

            'category_read',
            'category_create',
            'category_update',
            'category_delete',

            'asset_category_read',
            'asset_category_create',
            'asset_category_update',
            'asset_category_delete',

            'news_offer_read',
            'news_offer_create',
            'news_offer_update',
            'news_offer_delete',

            'sms_settings_status_change',
            'sms_send_settings_status_change',
            'bank_transaction_read',

            'parcel_status_reports',
            'parcel_wise_profit',
            'parcel_total_summery',
            'salary_reports',
            'merchant_hub_deliveryman',

            'salary_generate_read',
            'salary_generate_create',
            'salary_generate_update',
            'salary_generate_delete',

            'assets_read',
            'assets_create',
            'assets_update',
            'assets_delete',

            'fraud_read',
            'fraud_create',
            'fraud_update',
            'fraud_delete',
            'subscribe_read',
            'pickup_request_regular',
            'pickup_request_express',
            'invoice_read',
            'invoice_status_update',
            'social_login_settings_read',
            'social_login_settings_update',
            'payout_setup_settings_read',
            'payout_setup_settings_update',
            'online_payment_read',
            'payout_read',
            'payout_create',
            'hub_view',
            'paid_invoice_read',
            'invoice_generate_menually',
            // 'currency_read',
            // 'currency_create',
            // 'currency_update',
            // 'currency_delete',

            //front web 
            'social_link_read',
            'social_link_create',
            'social_link_update',
            'social_link_delete',
            //service
            'service_read',
            'service_create',
            'service_update',
            'service_delete',
            //Why Courier
            'why_courier_read',
            'why_courier_create',
            'why_courier_update',
            'why_courier_delete',
            //faq
            'faq_read',
            'faq_create',
            'faq_update',
            'faq_delete',
            //partner
            'partner_read',
            'partner_create',
            'partner_update',
            'partner_delete',
            //blogs
            'blogs_read',
            'blogs_create',
            'blogs_update',
            'blogs_delete',
            //pages
            'pages_read',
            'pages_update',
            //section
            'section_read',
            'section_update',

            //wallet request
            'wallet_request_read',
            'wallet_request_create',
            'wallet_request_delete',
            'wallet_request_approve',
            'wallet_request_reject'


        ];
    }


    private function userPermissions()
    {
        return [
            'dashboard_read',
            'calendar_read',
            'total_parcel',
            'total_user',
            'total_merchant',
            'total_delivery_man',
            'total_hubs',
            'total_accounts',
            'total_parcels_pending',
            'total_pickup_assigned',
            'total_received_warehouse',
            'total_deliveryman_assigned',
            'total_partial_deliverd',
            'total_parcels_deliverd',

            'recent_accounts',
            'recent_salary',
            'recent_hub',
            'all_statements',
            'income_expense_charts',
            'merchant_revenue_charts',
            'deliveryman_revenue_charts',
            'courier_revenue_charts',
            'recent_parcels',
            'bank_transaction',

            'recent_accounts',
            'recent_salary',
            'recent_hub',
            'all_statements',
            'income_expense_charts',
            'merchant_revenue_charts',
            'deliveryman_revenue_charts',
            'courier_revenue_charts',
            'recent_parcels',
            'bank_transaction',

            'log_read',
            // 'database_backup_read',

            'hub_read',
            'hub_incharge_read',

            'account_read',

            'expense_read',

            'todo_read',

            'account_heads_read',

            'fund_transfer_read',

            'role_read',
            'salary_read',
            'support_read',
            'general_settings_read',

            'designation_read',

            'department_read',

            'user_read',

            'merchant_read',

            'merchant_delivery_charge_read',

            'merchant_shop_read',

            'merchant_payment_read',

            'payment_read',
            'hub_payment_request_read',

            'hub_payment_read',

            'parcel_read',

            'delivery_man_read',

            'delivery_category_read',

            'delivery_charge_read',

            'delivery_type_read',

            'liquid_fragile_read',

            'sms_settings_read',
            'sms_send_settings_read',

            'notification_settings_read',

            'push_notification_read',
            'push_notification_create',
            'push_notification_update',
            'push_notification_delete',

            'packaging_read',

            'category_read',

            'asset_category_read',

            'news_offer_read',

            'cash_received_from_delivery_man_read',
            'cash_received_from_delivery_man_create',
            'cash_received_from_delivery_man_update',
            'cash_received_from_delivery_man_delete',

            'parcel_status_reports',
            'parcel_wise_profit',
            'parcel_total_summery',
            'salary_reports',
            'merchant_hub_deliveryman',

            'salary_generate_read',


            'assets_read',

            'fraud_read',
            'subscribe_read',
            'pickup_request_regular',
            'pickup_request_express',
            'invoice_read',
            'invoice_status_update',
            'social_login_settings_read',
            'social_login_settings_update',
            'payout_setup_settings_read',
            'online_payment_read',
            'payout_read',
            'hub_view',
            'paid_invoice_read',
            'invoice_generate_menually',


        ];
    }

}
