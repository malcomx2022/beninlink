<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Backend\Role;
use App\Models\SuperAdminPermission;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Arr;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
 

        $roles = [
            'Super Admin',
            'Admin',
            'User'
        ];

        for($n = 0; $n < sizeof($roles); $n++)
        {
            $role              = new Role();
            $role->name        = $roles[$n]; 
            $role->slug        = str_replace(' ', '-',  strtolower($roles[$n]));
            if($n == 0){
                $role->permissions =  $this->superadminPermission(); 
                $role->company_id  = 1;
            }elseif($n==1){ 
                $role->permissions = $this->AdminPermissions();
                $role->company_id  = 2;
            }else{
                $role->company_id  = 2;
                $role->permissions=$this->userPermissions();
            } 
            $role->save();
        }

    }


  
    public function superadminPermission(){
        $array = [];
        foreach (SuperAdminPermission::pluck('keywords') as $key => $module) {
            foreach ($module as $key => $value) {
                 $array[]=$value;
            }
        } 
        return $array;
    }

    private function AdminPermissions(){
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
                    'parcel_status_update',

                    'delivery_man_read',
                    'delivery_man_create',
                    'delivery_man_update',
                    'delivery_man_delete',

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

                    'packaging_read',
                    'packaging_create',
                    'packaging_update',
                    'packaging_delete',

                    'category_read',
                    'category_create',
                    'category_update',
                    'category_delete',

                    'account_heads_read',

                    // 'database_backup_read',

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

                    'asset_category_read',
                    'asset_category_create',
                    'asset_category_update',
                    'asset_category_delete',

                    'news_offer_read',
                    'news_offer_create',
                    'news_offer_update',
                    'news_offer_delete',
                    //reports
                    'parcel_status_reports',
                    'parcel_wise_profit',
                    'parcel_total_summery',
                    'salary_reports',
                    'merchant_hub_deliveryman',
                    //end reports

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
                    

                    // front web

                    'social_link_read',
                    'social_link_create',
                    'social_link_update',
                    'social_link_delete',
                    //service
                    'service_read',
                    'service_create',
                    'service_update',
                    'service_delete',
                    //why courier
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
                    'subscription_read',
          
                    //wallet request
                    'wallet_request_read',
                    'wallet_request_create', 
                    'wallet_request_delete',
                    'wallet_request_approve',
                    'wallet_request_reject'


              ];
    }

    private function userPermissions(){
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

                    'hub_read',
                    'hub_incharge_read',

                    'account_read',

                    'income_read',

                    'expense_read',

                    'todo_read',

                    'sms_settings_read',

                    'sms_send_settings_read',

                    'general_settings_read',

                    'notification_settings_read',

                    'push_notification_read',
                    'push_notification_create',
                    'push_notification_update',
                    'push_notification_delete',

                    'account_heads_read',

                    'salary_read',
                    'support_read',

                    'fund_transfer_read',

                    'role_read',

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

                    'packaging_read',

                    'category_read',

                    'asset_category_read',
                    'news_offer_read',

                    'sms_settings_status_change',
                    'sms_send_settings_status_change',
                    'bank_transaction_read',
                    // 'database_backup_read',

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

                    'cash_received_from_delivery_man_read',
                    'cash_received_from_delivery_man_create',
                    'cash_received_from_delivery_man_update',
                    'cash_received_from_delivery_man_delete',
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
                    // 'currency_read', 


              ];
    }


}
