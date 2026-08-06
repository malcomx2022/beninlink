<?php

namespace App\Providers;

use App\Repositories\Currency\CurrencyInterface;
use App\Repositories\Currency\CurrencyRepository;
use App\Repositories\FrontWeb\Blogs\BlogsInterface;
use App\Repositories\FrontWeb\Blogs\BlogsRepository;
use App\Repositories\FrontWeb\Pages\PagesInterface;
use App\Repositories\FrontWeb\Pages\PagesRepository;
use App\Repositories\FrontWeb\Faq\FaqInterface;
use App\Repositories\FrontWeb\Faq\FaqRepository;
use App\Repositories\FrontWeb\Partner\PartnerInterface;
use App\Repositories\FrontWeb\Partner\PartnerRepository;
use App\Repositories\FrontWeb\Section\SectionInterface;
use App\Repositories\FrontWeb\Section\SectionRepository;
use App\Repositories\FrontWeb\Service\ServiceInterface;
use App\Repositories\FrontWeb\Service\ServiceRepository;
use App\Repositories\FrontWeb\SocialLink\SocialLinkInterface;
use App\Repositories\FrontWeb\SocialLink\SocialLinkRepository;
use App\Repositories\FrontWeb\WhyCourier\WhyCourierInterface;
use App\Repositories\FrontWeb\WhyCourier\WhyCourierRepository;
use App\Repositories\MerchantDeliveryCharge\MerchantDeliveryChargeInterface;
use App\Repositories\Superadmin\Company\CompanyInterface;
use App\Repositories\Superadmin\Company\CompanyRepository;
use App\Repositories\Superadmin\Plan\PlanInterface;
use App\Repositories\Wallet\WalletInterface;
use App\Repositories\Wallet\WalletRepository;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use App\Repositories\Superadmin\Plan\PlanRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->bind('App\Repositories\Role\RoleInterface', 'App\Repositories\Role\RoleRepository');
        $this->app->bind('App\Repositories\Hub\HubInterface', 'App\Repositories\Hub\HubRepository');
        $this->app->bind('App\Repositories\User\UserInterface', 'App\Repositories\User\UserRepository');
        $this->app->bind('App\Repositories\Profile\ProfileInterface', 'App\Repositories\Profile\ProfileRepository');
        $this->app->bind('App\Repositories\Designation\DesignationInterface', 'App\Repositories\Designation\DesignationRepository');
        $this->app->bind('App\Repositories\Department\DepartmentInterface', 'App\Repositories\Department\DepartmentRepository');
        $this->app->bind('App\Repositories\Merchant\MerchantInterface', 'App\Repositories\Merchant\MerchantRepository');
        $this->app->bind('App\Repositories\MerchantShops\ShopsInterface', 'App\Repositories\MerchantShops\ShopsRepository');
        $this->app->bind('App\Repositories\MerchantDeliveryCharge\MerchantDeliveryChargeInterface', 'App\Repositories\MerchantDeliveryCharge\MerchantDeliveryChargeRepository');
        $this->app->bind('App\Repositories\DeliveryMan\DeliveryManInterface', 'App\Repositories\DeliveryMan\DeliveryManRepository');
        $this->app->bind('App\Repositories\HubInCharge\HubInChargeInterface', 'App\Repositories\HubInCharge\HubInChargeRepository');
        $this->app->bind('App\Repositories\Parcel\ParcelInterface', 'App\Repositories\Parcel\ParcelRepository');
        $this->app->bind('App\Repositories\DeliveryCategory\DeliveryCategoryInterface', 'App\Repositories\DeliveryCategory\DeliveryCategoryRepository');
        $this->app->bind('App\Repositories\DeliveryCharge\DeliveryChargeInterface', 'App\Repositories\DeliveryCharge\DeliveryChargeRepository');
        $this->app->bind('App\Repositories\Packaging\PackagingInterface', 'App\Repositories\Packaging\PackagingRepository');
        $this->app->bind('App\Repositories\MerchantPayment\PaymentInterface', 'App\Repositories\MerchantPayment\PaymentRepository');
        $this->app->bind('App\Repositories\Account\AccountInterface', 'App\Repositories\Account\AccountRepository');
        $this->app->bind('App\Repositories\MerchantManage\Payment\PaymentInterface', 'App\Repositories\MerchantManage\Payment\PaymentRepository');
        $this->app->bind('App\Repositories\FundTransfer\FundTransferInterface', 'App\Repositories\FundTransfer\FundTransferRepository');
        $this->app->bind('App\Repositories\MerchantProfile\MerchantProfileInterface', 'App\Repositories\MerchantProfile\MerchantProfileRepository');
        $this->app->bind('App\Repositories\DeliveryType\DeliveryTypeInterface', 'App\Repositories\DeliveryType\DeliveryTypeRepository');
        $this->app->bind('App\Repositories\Expense\ExpenseInterface', 'App\Repositories\Expense\ExpenseRepository');
        $this->app->bind('App\Repositories\Salary\SalaryInterface', 'App\Repositories\Salary\SalaryRepository');
        $this->app->bind('App\Repositories\BankTransaction\BankTransactionInterface', 'App\Repositories\BankTransaction\BankTransactionRepository');
        $this->app->bind('App\Repositories\NewsOffer\NewsOfferInterface', 'App\Repositories\NewsOffer\NewsOfferRepository');
        $this->app->bind('App\Repositories\Fraud\FraudInterface', 'App\Repositories\Fraud\FraudRepository');
        //merchant panel
        $this->app->bind('App\Repositories\MerchantPanel\PaymentAccount\PaymentAccountInterface', 'App\Repositories\MerchantPanel\PaymentAccount\PaymentAccountRepository');
        $this->app->bind('App\Repositories\MerchantPanel\Shops\ShopsInterface', 'App\Repositories\MerchantPanel\Shops\ShopsRepository');
        $this->app->bind('App\Repositories\MerchantPanel\PaymentRequest\PaymentRequestInterface', 'App\Repositories\MerchantPanel\PaymentRequest\PaymentRequestRepository');
        $this->app->bind('App\Repositories\MerchantPanel\MerchantParcel\MerchantParcelInterface', 'App\Repositories\MerchantPanel\MerchantParcel\MerchantParcelRepository');
        $this->app->bind('App\Repositories\MerchantPanel\Support\SupportInterface', 'App\Repositories\MerchantPanel\Support\SupportRepository');
        $this->app->bind('App\Repositories\MerchantPanel\Fraud\FraudInterface', 'App\Repositories\MerchantPanel\Fraud\FraudRepository');
        $this->app->bind('App\Repositories\MerchantPanel\PickupRequest\PickupRequestInterface', 'App\Repositories\MerchantPanel\PickupRequest\PickupRequestRepository');
        $this->app->bind('App\Repositories\Income\IncomeInterface', 'App\Repositories\Income\IncomeRepository');
        $this->app->bind('App\Repositories\Todo\TodoInterface', 'App\Repositories\Todo\TodoRepository');
        $this->app->bind('App\Repositories\Support\SupportInterface', 'App\Repositories\Support\SupportRepository');
        //account heads
        $this->app->bind('App\Repositories\AccountHeads\AccountHeadsInterface', 'App\Repositories\AccountHeads\AccountHeadsRepository');
        $this->app->bind('App\Repositories\SmsSetting\SmsSettingInterface', 'App\Repositories\SmsSetting\SmsSettingRepository');
        $this->app->bind('App\Repositories\SmsSendSetting\SmsSendSettingInterface', 'App\Repositories\SmsSendSetting\SmsSendSettingRepository');
        $this->app->bind('App\Repositories\GeneralSettings\GeneralSettingsInterface', 'App\Repositories\GeneralSettings\GeneralSettingsRepository');
        $this->app->bind('App\Repositories\NotificationSettings\NotificationSettingsInterface', 'App\Repositories\NotificationSettings\NotificationSettingsRepository');
        $this->app->bind('App\Repositories\GoogleMapSettings\GoogleMapSettingsInterface', 'App\Repositories\GoogleMapSettings\GoogleMapSettingsRepository');
        $this->app->bind('App\Repositories\PushNotification\PushNotificationInterface', 'App\Repositories\PushNotification\PushNotificationRepository');
        $this->app->bind('App\Repositories\AssetCategory\AssetCategoryInterface', 'App\Repositories\AssetCategory\AssetCategoryRepository');
        $this->app->bind('App\Repositories\Asset\AssetInterface', 'App\Repositories\Asset\AssetRepository');
        $this->app->bind('App\Repositories\CashReceivedFromDeliveryman\ReceivedInterface', 'App\Repositories\CashReceivedFromDeliveryman\ReceivedRepository');
        $this->app->bind('App\Repositories\HubPaymentRequest\HubPaymentRequestInterface', 'App\Repositories\HubPaymentRequest\HubPaymentRequestRepository');
        $this->app->bind('App\Repositories\HubManage\HubPayment\HubPaymentInterface', 'App\Repositories\HubManage\HubPayment\HubPaymentRepository');
        $this->app->bind('App\Repositories\Dashboard\DashboardInterface', 'App\Repositories\Dashboard\DashboardRepository');
        $this->app->bind('App\Repositories\Reports\ReportsInterface', 'App\Repositories\Reports\ReportsRepository');
        $this->app->bind('App\Repositories\Reports\TotalSummeryReport\TotalSummeryReportInterface', 'App\Repositories\Reports\TotalSummeryReport\TotalSummeryReportRepository');
        $this->app->bind('App\Repositories\Invoice\InvoiceInterface', 'App\Repositories\Invoice\InvoiceRepository');
        $this->app->bind('App\Repositories\SocialLoginSettings\SocialLoginSettingsInterface', 'App\Repositories\SocialLoginSettings\SocialLoginSettingsRepository');
        $this->app->bind('App\Repositories\PayoutSetup\PayoutSetupInterface', 'App\Repositories\PayoutSetup\PayoutSetupRepository');
        $this->app->bind('App\Repositories\MerchantOnlinePaymentSetup\PaymentSetupInterface', 'App\Repositories\MerchantOnlinePaymentSetup\PaymentSetupRepository');
        $this->app->bind(CurrencyInterface::class,   CurrencyRepository::class);
        //front web
        $this->app->bind(SocialLinkInterface::class, SocialLinkRepository::class);
        $this->app->bind(ServiceInterface::class,    ServiceRepository::class);
        $this->app->bind(WhyCourierInterface::class, WhyCourierRepository::class);
        $this->app->bind(FaqInterface::class,        FaqRepository::class);
        $this->app->bind(PartnerInterface::class,    PartnerRepository::class);
        $this->app->bind(BlogsInterface::class,      BlogsRepository::class);
        $this->app->bind(PagesInterface::class,      PagesRepository::class);
        $this->app->bind(SectionInterface::class,    SectionRepository::class);
        $this->app->bind(WalletInterface::class,     WalletRepository::class);
        $this->app->bind(PlanInterface::class,       PlanRepository::class);
        $this->app->bind(CompanyInterface::class,    CompanyRepository::class);
      
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapFive();
        Paginator::useBootstrapFour();
        Schema::defaultStringLength(191);
    }
}
