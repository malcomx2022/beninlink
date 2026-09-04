<?php
/*
|--------------------------------------------------------------------------
| Laravel WeCourier App
|--------------------------------------------------------------------------
|
| Autor Name : WemaxDevs
| Website : www.wemaxdevs.com
| Email : info@wemaxdevs.com
|
*/

use App\Http\Controllers\AamarpayController;
use App\Http\Controllers\Backend\AddonController;
use App\Http\Controllers\Backend\DatabaseBackupController;
use App\Http\Controllers\Backend\GoogleMapSettingsController;
use App\Http\Controllers\Backend\NotificationSettingsController;
use App\Http\Controllers\Backend\PushNotificationController;
use App\Http\Controllers\Backend\TotalSummeryReportController;
use App\Http\Controllers\MapParcelController;
use App\Http\Middleware\XSS;

use App\Http\Controllers\Backend\HubPanel\HubPaymentRequestController;
use App\Http\Controllers\Backend\HubPaymentController;
use App\Http\Controllers\Backend\MerchantPanel\MerchantParcelController;
use App\Http\Controllers\Backend\MerchantPanel\StatementsController;
use App\Http\Controllers\Backend\SmsSendSettingsController;
use App\Http\Controllers\Backend\SmsSettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashbordController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Backend\HubInChargeController;
use App\Http\Controllers\Backend\MerchantDeliveryChargeController;
use App\Http\Controllers\Backend\ProfileController;
use App\Http\Controllers\Backend\MerchantProfileController;
use App\Http\Controllers\Backend\MerchantController;
use App\Http\Controllers\Backend\ParcelController;
use App\Http\Controllers\Backend\ParcelQuoteController;
use App\Http\Controllers\Backend\CustomsController;
use App\Http\Controllers\Backend\DeliverycategoryController;
use App\Http\Controllers\Backend\DeliveryChargeController;
use App\Http\Controllers\Backend\MerchantShopsController;
use App\Http\Controllers\Backend\PackagingController;
use App\Http\Controllers\Backend\RoleController;
use App\Http\Controllers\Backend\ActiveLogController;
use App\Http\Controllers\Backend\HubController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\Backend\DeliveryManController;
use App\Http\Controllers\Backend\DesignationController;
use App\Http\Controllers\Backend\DepartmentController;
use App\Http\Controllers\Backend\FraudController;
use App\Http\Controllers\Backend\AccountController;
use App\Http\Controllers\Backend\AccountHeadsController;
use App\Http\Controllers\Backend\AdminAamarpayController;
use App\Http\Controllers\Backend\AdminBkashController;
use App\Http\Controllers\Backend\AdminSkrillController;
use App\Http\Controllers\Backend\AdminSslCommerzController;
use App\Http\Controllers\Backend\DeliveryTypeController;
use App\Http\Controllers\MerchantmanagePaymentController;
use App\Http\Controllers\Backend\FundTransferController;
use App\Http\Controllers\Backend\IncomeController;
use App\Http\Controllers\Backend\BankTransactionController;
use App\Http\Controllers\Backend\LiquidFragileController;
use App\Http\Controllers\Backend\ExpenseController;
use App\Http\Controllers\MerchantPaymentAccountController;
use App\Http\Controllers\Backend\TodoController;
use App\Http\Controllers\Backend\SupportController;
use App\Http\Controllers\Backend\GeneralSettingsController;
use App\Http\Controllers\Backend\AssetcategoryController;
use App\Http\Controllers\Backend\NewsOfferController;
use App\Http\Controllers\Backend\SalaryController;
use App\Http\Controllers\Backend\AssetController;
use App\Http\Controllers\Backend\BkashController;
use App\Http\Controllers\Backend\CurrencyController;
use App\Http\Controllers\Backend\FrontWeb\BlogController;
use App\Http\Controllers\Backend\FrontWeb\FaqController;
use App\Http\Controllers\Backend\FrontWeb\PageController;
use App\Http\Controllers\Backend\FrontWeb\PartnerController;
use App\Http\Controllers\Backend\FrontWeb\SectionController;
use App\Http\Controllers\Backend\FrontWeb\ServiceController;
use App\Http\Controllers\Backend\FrontWeb\SocialLinkController;
use App\Http\Controllers\Backend\FrontWeb\WhyCourierController;
use App\Http\Controllers\Backend\HubPanel\ReceivedFromDeliverymanController;
use App\Http\Controllers\Backend\MerchantInvoiceController;
//merchant panel controller
use App\Http\Controllers\Backend\MerchantPanel\SettingsController;
use App\Http\Controllers\Backend\MerchantPanel\PaymentAccountController;
use App\Http\Controllers\Backend\MerchantPanel\AccountTransactionController;
use App\Http\Controllers\Backend\MerchantPanel\PaymentRequestController;
use App\Http\Controllers\Backend\MerchantPanel\ShopsController;
use App\Http\Controllers\Backend\MerchantPanel\NewsOfferController as MerchantNewsOfferController;
use App\Http\Controllers\Backend\MerchantPanel\SupportController as MerchantPanelSupportController;
use App\Http\Controllers\Backend\MerchantPanel\FraudController as MerchantPanelFraudController;
use App\Http\Controllers\Backend\MerchantPanel\InvoiceController;
use App\Http\Controllers\Backend\MerchantPanel\MerchantOnlinePaymentSetupController;
use App\Http\Controllers\Backend\MerchantPanel\ReportsController as MerchantPanelReportsController;
use App\Http\Controllers\Backend\MerchantPanel\MerchantReportsController;
use App\Http\Controllers\Backend\MerchantPanel\OnlinePaymentController;
use App\Http\Controllers\Backend\MerchantPanel\PickupRequestController as MerchantPanelPickupRequestController;
use App\Http\Controllers\Backend\MerchantPanel\WalletController;
use App\Http\Controllers\Backend\PayoutController;
use App\Http\Controllers\Backend\PayoutSetupController;
use App\Http\Controllers\Backend\PickupRequestController;
use App\Http\Controllers\Backend\ReportsController;
use App\Http\Controllers\Backend\SalaryGenerateController;
use App\Http\Controllers\Backend\SkrillController;
use App\Http\Controllers\Backend\SocialLoginController;
use App\Http\Controllers\Backend\SslCommerzPaymentController;
use App\Http\Controllers\Backend\Superadmin\PlanController;
use App\Http\Controllers\LocalizationController;
use App\Models\Backend\Payroll\SalaryGenerate;
use App\Http\Controllers\Backend\WebNotificationController;
use App\Http\Controllers\Frontend\FrontendController;
use App\Http\Controllers\InstallerController;
use App\Http\Middleware\CompanyActivationMiddleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;


//installer
Route::middleware(['XSS', 'IsNotInstalled'])->group(function () {
    Route::get('install',                          [InstallerController::class, 'index']);
});

Route::middleware(['XSS'])->group(function () {
    Route::post('installing',                      [InstallerController::class, 'installing'])->name('installing');
    Route::get('finish',                           [InstallerController::class, 'finish'])->name('final');
});

//end installer
Route::middleware(['XSS', 'IsInstalled'])->group(function () {

    Route::get('localization/{language}', [LocalizationController::class, 'setLocalization'])->name('setlocalization');

    $domain = false;
    if (Config::get('app.app_installed') == 'yes'  && Schema::hasTable('domains')) :
        $domain = in_array(request()->getHost(), Domain::pluck('domain')->toArray());
    endif;

    if ($domain) :
        Route::middleware([
            PreventAccessFromCentralDomains::class,
            InitializeTenancyByDomain::class,
            CompanyActivationMiddleware::class
        ])->group(function () {

            Auth::routes();
            //frontend
            Route::controller(FrontendController::class)->group(function () {
                Route::get('/',                      'index')->name('home');
                Route::get('/tracking',              'tracking')->name('tracking.index');
                Route::get('/about-us',              'aboutUs')->name('aboutus.index');
                Route::get('/privacy-and-policy',    'privacyPolicy')->name('privacy.policy.index');
                Route::get('/terms-of-condition',    'termsOfCondition')->name('termsof.condition.index');
                Route::get('/faq-list',              'faq')->name('get.faq.index');
                Route::post('subscribe-store',       'subscribe')->name('subscribe.store');
                Route::get('contact-send',           'contactSendPage')->name('contact.send.page');
                Route::post('contact-message-send',  'contactMessageSend')->name('contact.message.send');
                Route::get('blog-details/{id}',      'blogDetails')->name('blog.details');
                Route::get('get-blogs',              'blogs')->name('get.blogs');
                Route::get('service-details/{id}',    'serviceDetails')->name('service.details');
            });
            //end frontend

            Route::get('merchant/sign-up',                [MerchantController::class, 'signUp'])->name('merchant.sign-up');
            Route::post('merchant/sign-up-store',         [MerchantController::class, 'signUpStore'])->name('merchant.sign-up-store');
            Route::post('merchant/otp-verification',      [MerchantController::class, 'otpVerification'])->name('merchant.otp-verification');
            Route::get('merchant/otp-verification-form',  [MerchantController::class, 'otpVerificationForm'])->name('merchant.otp-verification-form');
            Route::post('merchant/resend-otp',            [MerchantController::class, 'resendOTP'])->name('merchant.resend-otp');
            //social authentication
            Route::get('/login/{social}',                 [SocialLoginController::class, 'socialRedirect'])->name('social.login');
            Route::get('/google/login',                   [SocialLoginController::class, 'authGoogleLogin']); //google login , need url add in  your google app
            Route::get('/facebook/login',                 [SocialLoginController::class, 'authFacebookLogin']); // facebook login, need url add in your facebook app
            //end social authentication

            Route::group(['middleware' => 'auth'], function () {
                // XSS Protection
                Route::get('/dashboard',             [DashbordController::class, 'index'])->name('dashboard.index');
                Route::get('/subscription',          [PlanController::class, 'subscription'])->name('subscription.index');

                Route::get('/subscription/payment',  [PlanController::class, 'subscriptionPayment'])->name('subscription.payment');
                // Chantier 3 — abonnement par Mobile Money. Hors du groupe
                // subscriptionCheck : une société dont le plan a expiré doit
                // pouvoir payer. L'activation vient du webhook, jamais d'ici.
                Route::post('/subscription/fedapay', [\App\Http\Controllers\Payment\FedaPayController::class, 'subscribe'])->name('subscription.fedapay');

                Route::any('/subscription/success',  [PlanController::class, 'StripePaymentSuccess'])->name('subscription.success');
                Route::any('/subscription/cancel',   [PlanController::class, 'StripePaymentCancel'])->name('subscription.cancel');

                Route::get('/admin/subscription/history',  [PlanController::class, 'subscriptionHistory'])->name('admin.subscription.history');
                Route::group(['middleware' => ['subscriptionCheck', 'XSS']], function () {
                    // Route::get('/home',[HomeController::class, 'index'])->name('home');
                    //Admin Dashbord Controller
                    Route::post('search-charts',         [DashbordController::class, 'searchCharts'])->name('search-charts');
                    //Admin Category Controller
                    Route::get('category/index',         [CategoryController::class, 'index'])->name('category.index')->middleware('hasPermission:category_read');
                    Route::get('category/create',        [CategoryController::class, 'create'])->name('category.create')->middleware('hasPermission:category_create');
                    Route::post('category/store',        [CategoryController::class, 'store'])->name('category.store')->middleware('hasPermission:category_create');
                    Route::get('category/edit/{id}',     [CategoryController::class, 'edit'])->name('category.edit')->middleware('hasPermission:category_update');
                    Route::put('category/update',        [CategoryController::class, 'update'])->name('category.update')->middleware('hasPermission:category_update');
                    Route::delete('category/delete/{id}', [CategoryController::class, 'destroy'])->name('category.delete')->middleware('hasPermission:category_delete');
                    // Admin Routes
                    Route::group(['prefix' => 'admin'], function () {

                        Route::resource('addons', AddonController::class);
                        Route::post('/addons/activation', [AddonController::class, 'activation'])->name('addons.activation');

                        Route::get('logs',                   [ActiveLogController::class, 'index'])->name('logs.index')->middleware('hasPermission:log_read');
                        Route::get('log-activity-view/{id}', [ActiveLogController::class, 'view'])->name('log-activity-view');
                        Route::get('roles',                                             [RoleController::class, 'index'])->name('roles.index')->middleware('hasPermission:role_read');
                        Route::get('roles/create',                                      [RoleController::class, 'create'])->name('roles.create')->middleware('hasPermission:role_create');
                        Route::post('roles/store',                                      [RoleController::class, 'store'])->name('roles.store')->middleware('hasPermission:role_create');
                        Route::get('roles/edit/{id}',                                   [RoleController::class, 'edit'])->name('roles.edit')->middleware('hasPermission:role_update');
                        Route::put('roles/update',                                      [RoleController::class, 'update'])->name('roles.update')->middleware('hasPermission:role_update');
                        Route::delete('role/delete/{id}',                               [RoleController::class, 'destroy'])->name('role.delete')->middleware('hasPermission:role_delete');
                        // Hubs
                        Route::get('hubs',                                              [HubController::class, 'index'])->name('hubs.index')->middleware('hasPermission:hub_read');
                        Route::get('hubs/filter',                                       [HubController::class, 'filter'])->name('hubs.filter')->middleware('hasPermission:hub_read');
                        Route::get('hubs/create',                                       [HubController::class, 'create'])->name('hubs.create')->middleware('hasPermission:hub_create');
                        Route::post('hubs/store',                                       [HubController::class, 'store'])->name('hubs.store')->middleware('hasPermission:hub_create');
                        Route::get('hubs/edit/{id}',                                    [HubController::class, 'edit'])->name('hubs.edit')->middleware('hasPermission:hub_update');
                        Route::put('hubs/update',                                       [HubController::class, 'update'])->name('hubs.update')->middleware('hasPermission:hub_update');
                        Route::delete('hub/delete/{id}',                                [HubController::class, 'destroy'])->name('hub.delete')->middleware('hasPermission:hub_delete');
                        Route::get('hub/view/{id}',                                     [HubController::class, 'view'])->name('hub.view')->middleware('hasPermission:hub_view');
                        //hub payment
                        Route::get('request/hub/payment/index',                          [HubPaymentController::class, 'index'])->name('hub.hub-payment.index')->middleware('hasPermission:hub_payment_read');
                        Route::get('request/hub/payment/create',                         [HubPaymentController::class, 'create'])->name('hub.hub-payment.create')->middleware('hasPermission:hub_payment_create');
                        Route::post('request/hub/payment/store',                         [HubPaymentController::class, 'paymentStore'])->name('hub.hub-payment.store')->middleware('hasPermission:hub_payment_create');
                        Route::get('request/hub/payment/edit/{id}',                      [HubPaymentController::class, 'edit'])->name('hub.hub-payment.edit')->middleware('hasPermission:hub_payment_update');
                        Route::put('request/hub/payment/update/{id}',                    [HubPaymentController::class, 'update'])->name('hub.hub-payment.update')->middleware('hasPermission:hub_payment_update');
                        Route::delete('request/hub/payment/delete/{id}',                 [HubPaymentController::class, 'destroy'])->name('hub.hub-payment.delete')->middleware('hasPermission:hub_payment_delete');
                        //hub payment process
                        Route::get('hub-payment/reject/{id}',                             [HubPaymentController::class, 'reject'])->name('hub-payment.reject')->middleware('hasPermission:hub_payment_reject');
                        Route::get('hub-payment/cancel-reject/{id}',                      [HubPaymentController::class, 'cancelReject'])->name('hub-payment.cancel-reject')->middleware('hasPermission:hub_payment_reject');
                        Route::get('hub-payment/process/{id}',                            [HubPaymentController::class, 'process'])->name('hub-payment.process')->middleware('hasPermission:hub_payment_process');
                        Route::get('hub-payment/cancel-process/{id}',                     [HubPaymentController::class, 'cancelProcess'])->name('hub-payment.cancel-process')->middleware('hasPermission:hub_payment_process');
                        Route::put('hub-payment/processed',                               [HubPaymentController::class, 'processed'])->name('hub-payment.processed')->middleware('hasPermission:hub_payment_process');
                        //hub panel payment-request
                        Route::get('hub/payment-request/index',                           [HubPaymentRequestController::class, 'index'])->name('hub-panel.payment-request.index')->middleware('hasPermission:hub_payment_request_read');
                        Route::get('hub/payment-request/create',                          [HubPaymentRequestController::class, 'create'])->name('hub-panel.payment-request.create')->middleware('hasPermission:hub_payment_request_create');
                        Route::post('hub/payment-request/store',                          [HubPaymentRequestController::class, 'store'])->name('hub-panel.payment-request.store')->middleware('hasPermission:hub_payment_request_create');
                        Route::get('hub/payment-request/edit/{id}',                       [HubPaymentRequestController::class, 'edit'])->name('hub-panel.payment-request.edit')->middleware('hasPermission:hub_payment_request_update');
                        Route::put('hub/payment-request/update/{id}',                     [HubPaymentRequestController::class, 'update'])->name('hub-panel.payment-request.update')->middleware('hasPermission:hub_payment_request_update');
                        Route::delete('hub/payment-request/delete/{id}',                  [HubPaymentRequestController::class, 'delete'])->name('hub-panel.payment-request.delete')->middleware('hasPermission:hub_payment_request_delete');
                        // Hub in charges
                        Route::get('hub/incharge/{hubID}/index',                    [HubInChargeController::class, 'index'])->name('hub-incharge.index')->middleware('hasPermission:hub_incharge_read');
                        Route::get('hub/incharge/{hubID}/create',                   [HubInChargeController::class, 'create'])->name('hub-incharge.create')->middleware('hasPermission:hub_incharge_create');
                        Route::post('hub/incharge/{hubID}/store',                   [HubInChargeController::class, 'store'])->name('hub-incharge.store')->middleware('hasPermission:hub_incharge_create');
                        Route::get('hub/incharge/{hubID}/edit/{id}',                [HubInChargeController::class, 'edit'])->name('hub-incharge.edit')->middleware('hasPermission:hub_incharge_update');
                        Route::put('hub/incharge/{hubID}/update/{id}',              [HubInChargeController::class, 'update'])->name('hub-incharge.update')->middleware('hasPermission:hub_incharge_update');
                        Route::delete('hub/incharge/{hubID}/delete/{id}',           [HubInChargeController::class, 'destroy'])->name('hub-incharge.destroy')->middleware('hasPermission:hub_incharge_delete');
                        Route::get('hub/incharge/{hubID}/assigned/{id}',            [HubInChargeController::class, 'assigned'])->name('hub-incharge.assigned')->middleware('hasPermission:hub_incharge_assigned');
                        Route::get('users',          [UserController::class, 'index'])->name('users.index')->middleware('hasPermission:user_read');
                        Route::get('users/filter',   [UserController::class, 'filter'])->name('users.filter')->middleware('hasPermission:user_read');
                        Route::get('users/create',   [UserController::class, 'create'])->name('users.create')->middleware('hasPermission:user_create');
                        Route::post('users/store',   [UserController::class, 'store'])->name('users.store')->middleware('hasPermission:user_create');
                        Route::get('users/edit/{id}', [UserController::class, 'edit'])->name('users.edit')->middleware('hasPermission:user_update');
                        Route::put('users/update',   [UserController::class, 'update'])->name('users.update')->middleware('hasPermission:user_update');
                        Route::get('users/permissions/{id}',  [UserController::class, 'permission'])->name('users.permission')->middleware('hasPermission:permission_update');
                        Route::put('users/permissions/update', [UserController::class, 'permissionsUpdate'])->name('users.permissions.update')->middleware('hasPermission:permission_update');
                        Route::delete('user/delete/{id}',     [UserController::class, 'destroy'])->name('user.delete')->middleware('hasPermission:user_delete');
                        // Account income
                        Route::get('income',                      [IncomeController::class, 'index'])->name('income.index')->middleware('hasPermission:income_read');
                        Route::get('income/filter',               [IncomeController::class, 'filter'])->name('income.filter')->middleware('hasPermission:income_read');
                        Route::get('income/create',               [IncomeController::class, 'create'])->name('income.create')->middleware('hasPermission:income_create');
                        Route::post('income/search-account/{id}', [IncomeController::class, 'searchAccount'])->name('income.search-account');
                        Route::post('income/store',               [IncomeController::class, 'store'])->name('income.store')->middleware('hasPermission:income_create');
                        Route::get('income/edit/{id}',            [IncomeController::class, 'edit'])->name('income.edit')->middleware('hasPermission:income_update');
                        Route::put('income/update/{id}',          [IncomeController::class, 'update'])->name('income.update')->middleware('hasPermission:income_update');
                        Route::delete('income/delete/{id}',       [IncomeController::class, 'destroy'])->name('income.delete')->middleware('hasPermission:income_delete');
                        Route::post('income/balance-check',       [IncomeController::class, 'balanceCheck'])->name('income.balance.check');
                        Route::post('income/hub-user-accounts',   [IncomeController::class, 'hubUserAccounts'])->name('income.hub-user-accounts');
                        Route::post('income/users',               [IncomeController::class, 'IncomeUsers'])->name('income.users');
                        // Account expense
                        Route::get('expense',                      [ExpenseController::class, 'index'])->name('expense.index')->middleware('hasPermission:expense_read');
                        Route::get('expense/filter',               [ExpenseController::class, 'filter'])->name('expense.filter')->middleware('hasPermission:expense_read');
                        Route::get('expense/create',               [ExpenseController::class, 'create'])->name('expense.create')->middleware('hasPermission:expense_create');
                        Route::post('expense/search-account/{id}', [ExpenseController::class, 'searchAccount'])->name('expense.search-account');
                        Route::post('expense/store',               [ExpenseController::class, 'store'])->name('expense.store')->middleware('hasPermission:expense_create');
                        Route::get('expense/edit/{id}',            [ExpenseController::class, 'edit'])->name('expense.edit')->middleware('hasPermission:expense_update');
                        Route::put('expense/update/{id}',          [ExpenseController::class, 'update'])->name('expense.update')->middleware('hasPermission:expense_update');
                        Route::delete('expense/delete/{id}',       [ExpenseController::class, 'destroy'])->name('expense.delete')->middleware('hasPermission:expense_delete');
                        Route::post('expense/users',               [ExpenseController::class, 'ExpenseUsers'])->name('expense.users');
                        //salary
                        Route::get('salarys',                      [SalaryController::class, 'index'])->name('salary.index')->middleware('hasPermission:salary_read');
                        Route::get('salarys/filter',                [SalaryController::class, 'salaryFilter'])->name('salary.filter')->middleware('hasPermission:salary_read');
                        Route::get('salarys/create',                [SalaryController::class, 'create'])->name('salary.create')->middleware('hasPermission:salary_create');
                        Route::post('salary/users',               [SalaryController::class, 'Users'])->name('salary.users');
                        Route::post('salary/store',               [SalaryController::class, 'store'])->name('salary.store')->middleware('hasPermission:salary_create');
                        Route::get('salarys/edit/{id}',             [SalaryController::class, 'edit'])->name('salary.edit')->middleware('hasPermission:salary_update');
                        Route::put('salary/update',              [SalaryController::class, 'update'])->name('salary.update')->middleware('hasPermission:salary_update');
                        Route::delete('salary/delete/{id}',        [SalaryController::class, 'delete'])->name('salary.delete')->middleware('hasPermission:salary_delete');
                        Route::post('salary/search-account',       [SalaryController::class, 'salaryGet'])->name('salary.account.search');
                        Route::get('salary/pay-slip/{id}',         [SalaryController::class, 'paySlip'])->name('salary.pay.slip')->middleware('hasPermission:salary_read');
                        Route::get('bank-transaction',                 [BankTransactionController::class, 'index'])->name('bank-transaction.index')->middleware('hasPermission:bank_transaction_read');
                        Route::Post('bank-transaction/filter',         [BankTransactionController::class, 'filter'])->name('bank-transaction.filter')->middleware('hasPermission:bank_transaction_read');
                        Route::get('bank-transaction/specific/search', [BankTransactionController::class, 'bankTransactionSpecificSearch'])->name('bank.transaction.specific.search');
                        Route::get('bank-transaction/filter/print',    [BankTransactionController::class, 'bankTransactionPrint'])->name('bank.transaction.filter.print');
                        //hub panel cash received from delivery man
                        Route::get('hub/cash-received-deliveryman',               [ReceivedFromDeliverymanController::class, 'index'])->name('cash.received.deliveryman.index')->middleware('hasPermission:cash_received_from_delivery_man_read');
                        Route::get('hub/cash-received-deliveryman/create',        [ReceivedFromDeliverymanController::class, 'create'])->name('cash.received.deliveryman.create')->middleware('hasPermission:cash_received_from_delivery_man_create');
                        Route::post('hub/cash-received-deliveryman/store',        [ReceivedFromDeliverymanController::class, 'store'])->name('cash.received.deliveryman.store')->middleware('hasPermission:cash_received_from_delivery_man_create');
                        Route::get('hub/cash-received-deliveryman/edit/{id}',     [ReceivedFromDeliverymanController::class, 'edit'])->name('cash.received.deliveryman.edit')->middleware('hasPermission:cash_received_from_delivery_man_update');
                        Route::put('hub/cash-received-deliveryman/update',        [ReceivedFromDeliverymanController::class, 'update'])->name('cash.received.deliveryman.update')->middleware('hasPermission:cash_received_from_delivery_man_update');
                        Route::delete('hub/cash-received-deliveryman/delete/{id}', [ReceivedFromDeliverymanController::class, 'delete'])->name('cash.received.deliveryman.delete')->middleware('hasPermission:cash_received_from_delivery_man_delete');
                        // User profile
                        Route::get('profile/{id}',                  [ProfileController::class, 'view'])->name('profile.index')->withoutMiddleware('subscriptionCheck');
                        Route::get('profile/update/{id}',           [ProfileController::class, 'create'])->name('profile.edit')->withoutMiddleware('subscriptionCheck');
                        Route::get('profile/change-password/{id}',  [ProfileController::class, 'changePassword'])->name('password.change')->withoutMiddleware('subscriptionCheck');
                        Route::put('profile/update/{id}',           [ProfileController::class, 'update'])->name('profile.update')->withoutMiddleware('subscriptionCheck');
                        Route::put('profile/update-password/{id}',  [ProfileController::class, 'updatePassword'])->name('profile.password.update')->withoutMiddleware('subscriptionCheck');
                        // Merchant Routes
                        Route::get('merchant/index',          [MerchantController::class, 'index'])->name('merchant.index')->middleware('hasPermission:merchant_read');
                        Route::get('merchant/create',         [MerchantController::class, 'create'])->name('merchant.create')->middleware('hasPermission:merchant_create');
                        Route::post('merchant/store',         [MerchantController::class, 'store'])->name('merchant.store')->middleware('hasPermission:merchant_create');
                        Route::get('merchant/edit/{id}',      [MerchantController::class, 'edit'])->name('merchant.edit')->middleware('hasPermission:merchant_update');
                        Route::put('merchant/update/{id}',    [MerchantController::class, 'update'])->name('merchant.update')->middleware('hasPermission:merchant_update');
                        Route::delete('merchant/delete/{id}', [MerchantController::class, 'destroy'])->name('merchant.delete')->middleware('hasPermission:merchant_delete');
                        Route::get('merchant/view/{id}',      [MerchantController::class, 'view'])->name('merchant.view')->middleware('hasPermission:merchant_view');
                        Route::get('merchant/invoice-generate/{id}',      [MerchantController::class, 'invoiceGenerate'])->name('merchant.invoice.generate')->middleware('hasPermission:merchant_view');
                        //Merchent delivery charge routes
                        Route::post('merchant/delivery-charge/info',                    [MerchantDeliveryChargeController::class, 'deliveryChargeInfo'])->name('merchant.deliveryCharge.deliveryChargeInfo');
                        Route::get('merchant/{merchant}/delivery-charge/index',         [MerchantDeliveryChargeController::class, 'index'])->name('merchant.deliveryCharge.index')->middleware('hasPermission:merchant_delivery_charge_read');
                        Route::get('merchant/{merchant}/delivery-charge/create',        [MerchantDeliveryChargeController::class, 'create'])->name('merchant.deliveryCharge.create')->middleware('hasPermission:merchant_delivery_charge_create');
                        Route::post('merchant/{merchant}/delivery-charge/store',        [MerchantDeliveryChargeController::class, 'store'])->name('merchant.deliveryCharge.store')->middleware('hasPermission:merchant_delivery_charge_create');
                        Route::get('merchant/{merchant}/delivery-charge/edit/{id}',     [MerchantDeliveryChargeController::class, 'edit'])->name('merchant.deliveryCharge.edit')->middleware('hasPermission:merchant_delivery_charge_update');
                        Route::put('merchant/{merchant}/delivery-charge/update/{id}',   [MerchantDeliveryChargeController::class, 'update'])->name('merchant.deliveryCharge.update')->middleware('hasPermission:merchant_delivery_charge_update');
                        Route::delete('merchant/{merchant}/delivery-charge/delete/{id}', [MerchantDeliveryChargeController::class, 'delete'])->name('merchant.deliveryCharge.delete')->middleware('hasPermission:merchant_delivery_charge_delete');
                        //Merchent shops routes
                        Route::get('merchant/{id}/shops/index',     [MerchantShopsController::class, 'index'])->name('merchant.shops.index')->middleware('hasPermission:merchant_shop_read');
                        Route::get('merchant/shops/create/{id}',    [MerchantShopsController::class, 'create'])->name('merchant.shops.create')->middleware('hasPermission:merchant_shop_create');
                        Route::post('merchant/shops/store',         [MerchantShopsController::class, 'store'])->name('merchant.shops.store')->middleware('hasPermission:merchant_shop_create');
                        Route::get('merchant/shops/edit/{id}',      [MerchantShopsController::class, 'edit'])->name('merchant.shops.edit')->middleware('hasPermission:merchant_shop_update');
                        Route::put('merchant/shops/update',         [MerchantShopsController::class, 'update'])->name('merchant.shops.update')->middleware('hasPermission:merchant_shop_update');
                        Route::delete('merchant/shops/delete/{id}', [MerchantShopsController::class, 'delete'])->name('merchant.shops.delete')->middleware('hasPermission:merchant_shop_delete');
                        Route::get('merchant/shops/default/{merchant_id}/{id}', [MerchantShopsController::class, 'defaultShop'])->name('merchant.shops.default');
                        //merchant payment account
                        Route::get('merchant/{id}/payment/index',       [MerchantPaymentAccountController::class, 'index'])->name('merchant.paymentaccount.index')->middleware('hasPermission:merchant_payment_read');
                        Route::get('merchant/{id}/payment/add',         [MerchantPaymentAccountController::class, 'paymentAdd'])->name('merchant.payment.add')->middleware('hasPermission:merchant_payment_create');
                        Route::post('merchant/paymentmethod/change',    [MerchantPaymentAccountController::class, 'paymentChange'])->name('merchant.paymentmethod.change');
                        Route::post('merchant/paymentinfo/bank/store',  [MerchantPaymentAccountController::class, 'bankStore'])->name('merchant.paymentinfo.bank.store')->middleware('hasPermission:merchant_payment_create');
                        Route::post('merchant/paymentinfo/mobile/store', [MerchantPaymentAccountController::class, 'mobileStore'])->name('merchant.paymentinfo.mobile.store')->middleware('hasPermission:merchant_payment_create');
                        Route::get('merchant/{mid}/payment/edit/{id}',  [MerchantPaymentAccountController::class, 'paymentEdit'])->name('merchant.payment.edit')->middleware('hasPermission:merchant_payment_update');
                        Route::put('merchant/paymentinfo/bank/update',   [MerchantPaymentAccountController::class, 'bankUpdate'])->name('merchant.payment.bank.update')->middleware('hasPermission:merchant_payment_update');
                        Route::put('merchant/paymentinfo/mobile/update', [MerchantPaymentAccountController::class, 'mobileUpdate'])->name('merchant.payment.mobile.update')->middleware('hasPermission:merchant_payment_update');
                        Route::delete('merchant/paymentinfo/delete/{id}', [MerchantPaymentAccountController::class, 'destroy'])->name('merchant.payment.delete')->middleware('hasPermission:merchant_payment_delete');
                        //merchant manage payment
                        Route::get('payment/index',         [MerchantmanagePaymentController::class, 'index'])->name('merchant.manage.payment.index')->middleware('hasPermission:payment_read');
                        Route::get('payment/create',        [MerchantmanagePaymentController::class, 'create'])->name('merchant-manage.payment.create')->middleware('hasPermission:payment_create');
                        Route::post('merchant/account',     [MerchantmanagePaymentController::class, 'merchantAccount'])->name('merchant-manage.merchant.account');
                        Route::post('merchant/search',      [MerchantmanagePaymentController::class, 'merchantSearch'])->name('merchant-manage.merchant-search');
                        Route::post('payment/store',        [MerchantmanagePaymentController::class, 'paymentStore'])->name('merchantmanage.payment.store')->middleware('hasPermission:payment_create');
                        Route::get('payment/edit/{id}',     [MerchantmanagePaymentController::class, 'edit'])->name('merchatmanage.payment.edit')->middleware('hasPermission:payment_update');
                        Route::put('payment/update',        [MerchantmanagePaymentController::class, 'update'])->name('merchantmanage.payment.update')->middleware('hasPermission:payment_update');
                        Route::delete('payment/delete/{id}', [MerchantmanagePaymentController::class, 'destroy'])->name('merchantmanage.payment.delete')->middleware('hasPermission:payment_delete');
                        //merchant manage payment process
                        Route::get('payment/reject/{id}',        [MerchantmanagePaymentController::class, 'reject'])->name('merchantmanage.payment.reject')->middleware('hasPermission:payment_reject');
                        Route::get('payment/cancel-reject/{id}', [MerchantmanagePaymentController::class, 'cancelReject'])->name('merchantmanage.payment.cancel-reject')->middleware('hasPermission:payment_reject');
                        Route::get('payment/process/{id}',       [MerchantmanagePaymentController::class, 'process'])->name('merchantmanage.payment.process')->middleware('hasPermission:payment_process');
                        Route::get('payment/cancel-process/{id}', [MerchantmanagePaymentController::class, 'cancelProcess'])->name('merchantmanage.payment.cancel-process')->middleware('hasPermission:payment_process');
                        Route::put('payment/processed',          [MerchantmanagePaymentController::class, 'processed'])->name('merchantmanage.payment.processed')->middleware('hasPermission:payment_process');
                        Route::get('payment/merchant/filter',    [MerchantmanagePaymentController::class, 'merchantpaymentFilter'])->name('merchantmanage.payment.filter');
                        //merchant invoice
                        Route::prefix('merchant/{merchant_id}/invoice')->name('merchant.invoice.')->group(function () {
                            Route::get('/',                      [MerchantInvoiceController::class, 'index'])->name('index')->middleware('hasPermission:invoice_read');
                            Route::get('/{invoice_id}',          [MerchantInvoiceController::class, 'InvoiceDetails'])->name('details')->middleware('hasPermission:invoice_read');
                            Route::get('/status/update',         [MerchantInvoiceController::class, 'StatusUpdate'])->name('status.update')->middleware('hasPermission:invoice_status_update');
                            Route::get('/pdf/{invoice_id}',     [MerchantInvoiceController::class, 'InvoicePdf'])->name('pdf')->middleware('hasPermission:invoice_read');
                            Route::get('/journal/{invoice_id}', [MerchantInvoiceController::class, 'InvoiceJournal'])->name('journal')->middleware('hasPermission:invoice_read');
                            Route::get('/csv/{invoice_id}',     [MerchantInvoiceController::class, 'InvoiceCSV'])->name('csv')->middleware('hasPermission:invoice_read');
                        });
                        Route::get('paid/invoice',               [MerchantInvoiceController::class, 'PaidInvoice'])->name('paid.invoice.index');
                        // Chantier 4 — export journal SYSCOHADA des relevés d'une période.
                        Route::get('paid/invoice/syscohada-journal', [MerchantInvoiceController::class, 'JournalPeriod'])->name('paid.invoice.journal')->middleware('hasPermission:invoice_read');
                        //liquid fragile
                        Route::get('liquid-fragile/index',  [LiquidFragileController::class, 'index'])->name('liquid-fragile.index')->middleware('hasPermission:liquid_fragile_read');
                        Route::get('liquid-fragile/edit',   [LiquidFragileController::class, 'edit'])->name('liquid.fragile.edit')->middleware('hasPermission:liquid_fragile_update');
                        Route::put('liquid-fragile/update', [LiquidFragileController::class, 'update'])->name('liquid.fragile.update')->middleware('hasPermission:liquid_fragile_update');
                        Route::post('liquid-fragile/status', [LiquidFragileController::class, 'status'])->name('liquid-fragile.status')->middleware('hasPermission:liquid_status_change');
                        // Parcel Routes
                        Route::get('parcel/index',                          [ParcelController::class, 'index'])->name('parcel.index')->middleware('hasPermission:parcel_read');
                        Route::get('parcel/details/{id}',                   [ParcelController::class, 'details'])->name('parcel.details')->middleware('hasPermission:parcel_read');
                        Route::get('parcel/logs/{id}',                      [ParcelController::class, 'logs'])->name('parcel.logs')->middleware('hasPermission:parcel_read');
                        Route::get('parcel/clone/{id}',                     [ParcelController::class, 'duplicate'])->name('parcel.clone');
                        Route::get('parcel/create',                         [ParcelController::class, 'create'])->name('parcel.create')->middleware('hasPermission:parcel_create');
                        Route::post('parcel/store',                         [ParcelController::class, 'store'])->name('parcel.store')->middleware('hasPermission:parcel_create');
                        Route::post('parcel/clone-store',                   [ParcelController::class, 'duplicateStore'])->name('parcel.clone-store');
                        Route::get('parcel/edit/{id}',                      [ParcelController::class, 'edit'])->name('parcel.edit')->middleware('hasPermission:parcel_update');
                        Route::put('parcel/update/{id}',                    [ParcelController::class, 'update'])->name('parcel.update')->middleware('hasPermission:parcel_update');
                        Route::get('parcel/status-update/{id}/{status_id}', [ParcelController::class, 'statusUpdate'])->name('parcel.status-update')->middleware('hasPermission:parcel_status_update');
                        Route::delete('parcel/delete/{id}',                 [ParcelController::class, 'destroy'])->name('parcel.delete')->middleware('hasPermission:parcel_delete');
                        Route::get('parcel/print/{id}',                     [ParcelController::class, 'parcelPrint'])->name('parcel.print')->middleware('hasPermission:parcel_read');
                        Route::get('parcel/print/{id}/label',               [ParcelController::class, 'parcelPrintLabel'])->name('parcel.print-label')->middleware('hasPermission:parcel_read');
                        Route::get('parcel/multiple/print/label',           [ParcelController::class, 'parcelMultiplePrintLabel'])->name('parcel.multiple.print-label');

                        //parcel status
                        Route::post('parcel/deliveryman/search',            [ParcelController::class, 'deliverymanSearch'])->name('parcel.deliveryman.search');
                        Route::post('parcel/pickup-man/assigned',           [ParcelController::class, 'PickupManAssigned'])->name('parcel.pickup.man-assigned')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/pickup-man/assigned/cancel',    [ParcelController::class, 'PickupManAssignedCancel'])->name('parcel.pickup.man-assigned-cancel')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/pickup/re-schedule',            [ParcelController::class, 'PickupReSchedule'])->name('parcel.pickup.re.schedule')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/pickup-reschedule/cancel',      [ParcelController::class, 'PickupReScheduleCancel'])->name('parcel.pickup.re-schedule-cancel')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/pickup/received',               [ParcelController::class, 'receivedBypickupman'])->name('parcel.received.by.pickup')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/pickup/received/cancel',        [ParcelController::class, 'receivedBypickupmanCancel'])->name('parcel.pickup.man-received-cancel')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/received-warehouse',            [ParcelController::class, 'receivedWarehouse'])->name('parcel.received.warehouse')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/received-warehouse/cancel',     [ParcelController::class, 'receivedWarehouseCancel'])->name('parcel.received-warehouse-cancel')->middleware('hasPermission:parcel_status_update');
                        Route::get('parcel/filter',                         [ParcelController::class, 'filter'])->name('parcel.filter');
                        Route::post('parcel/search',                        [ParcelController::class, 'search'])->name('parcel.search');
                        Route::post('parcel/search-delivery-man-assing-multiple-parcel', [ParcelController::class, 'searchDeliveryManAssingMultipleParcel'])->name('parcel.search-delivery-man-assing-multiple-parcel');
                        Route::post('parcel/search-expense',                [ParcelController::class, 'searchExpense'])->name('parcel.search-expense');
                        Route::post('parcel/search-income',                 [ParcelController::class, 'searchIncome'])->name('parcel.search-income');
                        Route::post('parcel/transfer-to-hub-multiple-parcel', [ParcelController::class, 'transferToHubMultipleParcel'])->name('parcel.transfer-to-hub-multiple-parcel')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/delivery-man-assign-multiple-parcel', [ParcelController::class, 'deliveryManAssignMultipleParcel'])->name('parcel.delivery-man-assign-multiple-parcel')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/transfer-to-hub',                [ParcelController::class, 'transfertohub'])->name('parcel.transfer-to-hub')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/transfer-hub',                   [ParcelController::class, 'transferHub'])->name('parcel.transferHub')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/transfer-to-hub/cancel',         [ParcelController::class, 'transfertoHubCancel'])->name('parcel.transfer-to-hub-cancel')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/received-by-hub',                [ParcelController::class, 'receivedByHub'])->name('parcel.received-by.hub')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/received-by-hub/cancel',         [ParcelController::class, 'receivedByHubCancel'])->name('parcel.received-by-hub-cancel')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/received-warehouse-hub-selected', [ParcelController::class, 'warehouseHubSelected'])->name('parcel.received.warehouse.hub.select');
                        Route::post('parcel/delivery-man-assign',            [ParcelController::class, 'deliverymanAssign'])->name('parcel.delivery-man-assign')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/delivery-man/assign/cancel',     [ParcelController::class, 'deliverymanAssignCancel'])->name('parcel.delivery-man-assign-cancel')->middleware('hasPermission:parcel_status_update');
                        Route::get('parcel/bulkassign/print',                 [ParcelController::class, 'ParcelBulkAssignPrint'])->name('parcel.parcel-bulkassign-print');
                        Route::post('parcel/delivery-reschedule',            [ParcelController::class, 'deliveryReschedule'])->name('parcel.delivery.reschedule')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/delivery-re-scheule/cancel',     [ParcelController::class, 'deliveryReScheduleCancel'])->name('parcel.delivery-re-schedule-cancel')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/return-to-qourier',              [ParcelController::class, 'returntoQourier'])->name('parcel.return-to-qourier')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/return-to-qourier-cancel',       [ParcelController::class, 'returntoQourierCancel'])->name('parcel.return-to-courier-cancel')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/return-assign-to-merchant',      [ParcelController::class, 'returnAssignToMerchant'])->name('parcel.return-assign-to-merchant')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/return-assign-to-merchant/cancel', [ParcelController::class, 'returnAssignToMerchantCancel'])->name('parcel.return-assign-to-merchant-cancel')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/return-assign-to-merchant-reschedule', [ParcelController::class, 'returnAssignToMerchantReschedule'])->name('parcel.return-assign-to-merchant.reschedule')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/return-assign-re-schedule-to-merchant/cancel', [ParcelController::class, 'returnAssignToMerchantRescheduleCancel'])->name('parcel.return-assign-re-schedule-to-merchant-cancel')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/return-received-by-merchant',       [ParcelController::class, 'returnReceivedByMerchant'])->name('parcel.return-received-by-merchant')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/return-received-by-merchant/cancel', [ParcelController::class, 'returnReceivedByMerchantCancel'])->name('parcel.return-received-by-merchant-cancel')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/delivered',                         [ParcelController::class, 'parcelDelivered'])->name('parcel.delivered')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/delivered/cancel',                  [ParcelController::class, 'parcelDeliveredCancel'])->name('parcel.delivered-cancel')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/partial-delivered',                 [ParcelController::class, 'parcelPartialDelivered'])->name('parcel.partial-delivered')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/partial-delivered/cancel',          [ParcelController::class, 'parcelPartialDeliveredCancel'])->name('parcel.partial-delivered-cancel')->middleware('hasPermission:parcel_status_update');
                        Route::post('/transertohub-selected-hub',               [ParcelController::class, 'transfertohubSelectedHub'])->name('transertohub.selected.hub');
                        Route::post('/parcel/received-by-multiple-hub',         [ParcelController::class, 'parcelReceivedByMultipleHub'])->name('parcel.received-by-mulbiple-hub')->middleware('hasPermission:parcel_status_update');
                        Route::post('parcel/recived-by-hub/search',             [ParcelController::class, 'parcelRecivedByHubSearch'])->name('parcel.received-by-hub-search'); //ajax
                        Route::post('assign-pickup/parcel/search',              [ParcelController::class, 'AssignPickupParcelSearch'])->name('assign-pickup.parcel.search'); //ajax
                        Route::post('assign-pickup/bulk',                       [ParcelController::class, 'AssignPickupBulk'])->name('parcel.assign-pickup-bulk')->middleware('hasPermission:parcel_status_update');
                        Route::post('assign-return-to-merchant/parcel/search',  [ParcelController::class, 'AssignReturnToMerchantParcelSearch'])->name('assign-return-to-merchant.parcel.search'); //ajax
                        Route::post('parcel/assign-return-to-merchant-bulk',    [ParcelController::class, 'AssignReturnToMerchantBulk'])->name('parcel.assign-return-to-merchant-bulk')->middleware('hasPermission:parcel_status_update');
                        // new route add
                        Route::post('parcel/priority/update',                   [ParcelController::class, 'priorityUpdate'])->name('parcel.priority.status');
                        Route::get('parcel/deliveryMan/show',                   [ParcelController::class, 'parcelDeliveryMan'])->name('parcel.parcelDeliveryMan');
                        Route::get('parcel/delivered/logs/info/{id}',           [ParcelController::class, 'deliveredInfo'])->name('parcel.deliveredInfo');

                        //end parcel status
                        Route::post('parcel/merchant',                          [ParcelController::class, 'getMerchant'])->name('parcel.merchant.get');
                        Route::post('parcel/hub',                               [ParcelController::class, 'getHub'])->name('parcel.hub.get');
                        Route::post('parcel/merchant/shops',                    [ParcelController::class, 'merchantShops'])->name('parcel.merchant.shops');
                        Route::post('parcel/delivery-category',                 [ParcelController::class, 'deliveryWeight'])->name('parcel.deliveryCategory.deliveryWeight');
                        Route::post('parcel/delivery-charge',                   [ParcelController::class, 'deliveryCharge'])->name('parcel.deliveryCharge.get');
                        Route::post('parcel/quote',                             ParcelQuoteController::class)->name('parcel.quote');
                        // Chantier 5 — douane. Les ecrans empruntent les permissions
                        // parcel_* : PermissionSeeder n'est pas rejouable, voir le controleur.
                        Route::get('customs/alerts',              [CustomsController::class, 'alerts'])->name('customs.alerts')->middleware('hasPermission:parcel_read');
                        Route::put('customs/alerts/{id}/resolve', [CustomsController::class, 'resolve'])->name('customs.alerts.resolve')->middleware('hasPermission:parcel_update');
                        Route::get('customs/rules',               [CustomsController::class, 'rules'])->name('customs.rules')->middleware('hasPermission:parcel_read');
                        Route::get('customs/rules/edit/{id}',     [CustomsController::class, 'edit'])->name('customs.rules.edit')->middleware('hasPermission:parcel_update');
                        Route::put('customs/rules/update/{id}',   [CustomsController::class, 'update'])->name('customs.rules.update')->middleware('hasPermission:parcel_update');
                        //import
                        Route::get('parcel/import-parcel',                      [ParcelController::class, 'parcelImportExport'])->name('parcel.parcel-import')->middleware('hasPermission:parcel_create');
                        Route::post('parcel/file-import',                       [ParcelController::class, 'parcelImport'])->name('parcel.file-import')->middleware('hasPermission:parcel_create');
                        Route::get('parcel/file-export',                        [ParcelController::class, 'parcelExport'])->name('parcel.file-export');
                        Route::post('parcel/import/merchant',                   [ParcelController::class, 'getImportMerchant'])->name('parcel.import.merchant.get');
                        //merchant fetch using ajax
                        Route::post('get-merchant-cod',                         [parcelController::class, 'getMerchantCod'])->name('get.merchant.cod');
                        // Deliveryman
                        Route::get('deliveryman',                [DeliveryManController::class, 'index'])->name('deliveryman.index')->middleware('hasPermission:delivery_man_read');
                        Route::get('deliveryman/filter',         [DeliveryManController::class, 'filter'])->name('deliveryman.filter')->middleware('hasPermission:delivery_man_read');
                        Route::get('deliveryman/create',         [DeliveryManController::class, 'create'])->name('deliveryman.create')->middleware('hasPermission:delivery_man_create');
                        Route::post('deliveryman/store',         [DeliveryManController::class, 'store'])->name('deliveryman.store')->middleware('hasPermission:delivery_man_create');
                        Route::get('deliveryman/edit/{id}',      [DeliveryManController::class, 'edit'])->name('deliveryman.edit')->middleware('hasPermission:delivery_man_update');
                        Route::put('deliveryman/update',         [DeliveryManController::class, 'update'])->name('deliveryman.update')->middleware('hasPermission:delivery_man_update');
                        Route::delete('deliveryman/delete/{id}', [DeliveryManController::class, 'destroy'])->name('deliveryman.delete')->middleware('hasPermission:delivery_man_delete');
                        // Delivery Categorys Routes
                        Route::get('delivery-category/index',          [DeliverycategoryController::class, 'index'])->name('delivery-category.index')->middleware('hasPermission:delivery_category_read');
                        Route::get('delivery-category/create',         [DeliverycategoryController::class, 'create'])->name('delivery-category.create')->middleware('hasPermission:delivery_category_create');
                        Route::post('delivery-category/store',         [DeliverycategoryController::class, 'store'])->name('delivery-category.store')->middleware('hasPermission:delivery_category_create');
                        Route::get('delivery-category/edit/{id}',      [DeliverycategoryController::class, 'edit'])->name('delivery-category.edit')->middleware('hasPermission:delivery_category_update');
                        Route::get('delivery-category/view/{id}',      [DeliverycategoryController::class, 'view'])->name('delivery-category.view');
                        Route::put('delivery-category/update',         [DeliverycategoryController::class, 'update'])->name('delivery-category.update')->middleware('hasPermission:delivery_category_update');
                        Route::delete('delivery-category/delete/{id}', [DeliverycategoryController::class, 'destroy'])->name('delivery-category.delete')->middleware('hasPermission:delivery_category_delete');
                        // Delivery Charges Routes
                        Route::get('delivery-charge/index',         [DeliveryChargeController::class, 'index'])->name('delivery-charge.index')->middleware('hasPermission:delivery_charge_read');
                        Route::get('delivery-charge/filter',         [DeliveryChargeController::class, 'filter'])->name('delivery-charge.filter')->middleware('hasPermission:delivery_charge_read');
                        Route::get('delivery-charge/create',        [DeliveryChargeController::class, 'create'])->name('delivery-charge.create')->middleware('hasPermission:delivery_charge_create');
                        Route::post('delivery-charge/store',        [DeliveryChargeController::class, 'store'])->name('delivery-charge.store')->middleware('hasPermission:delivery_charge_create');
                        Route::get('delivery-charge/edit/{id}',     [DeliveryChargeController::class, 'edit'])->name('delivery-charge.edit')->middleware('hasPermission:delivery_charge_update');
                        Route::get('delivery-charge/view/{id}',     [DeliveryChargeController::class, 'view'])->name('delivery-charge.view');
                        Route::put('delivery-charge/update',        [DeliveryChargeController::class, 'update'])->name('delivery-charge.update')->middleware('hasPermission:delivery_charge_update');
                        Route::delete('delivery-charge/delete/{id}', [DeliveryChargeController::class, 'destroy'])->name('delivery-charge.delete')->middleware('hasPermission:delivery_charge_delete');
                        //delivery type
                        Route::get('delivery-type/index', [DeliveryTypeController::class, 'index'])->name('delivery-type.index')->middleware('hasPermission:delivery_type_read');
                        Route::post('delivery-type/status', [DeliveryTypeController::class, 'status'])->name('delivery-type.status')->middleware('hasPermission:delivery_type_status_change');
                        // Packaging Routes
                        Route::get('packaging/index',       [PackagingController::class, 'index'])->name('packaging.index')->middleware('hasPermission:packaging_read');
                        Route::get('packaging/create',      [PackagingController::class, 'create'])->name('packaging.create')->middleware('hasPermission:packaging_create');
                        Route::post('packaging/store',      [PackagingController::class, 'store'])->name('packaging.store')->middleware('hasPermission:packaging_create');
                        Route::get('packaging/edit/{id}',   [PackagingController::class, 'edit'])->name('packaging.edit')->middleware('hasPermission:packaging_update');
                        Route::get('packaging/view/{id}',   [PackagingController::class, 'view']);
                        Route::put('packaging/update',     [PackagingController::class, 'update'])->name('packaging.update')->middleware('hasPermission:packaging_update');
                        Route::delete('packaging/delete/{id}', [PackagingController::class, 'destroy'])->name('packaging.delete')->middleware('hasPermission:packaging_delete');
                        // Accounts Routes
                        Route::get('accounts/index',          [AccountController::class, 'index'])->name('accounts.index')->middleware('hasPermission:account_read');
                        Route::get('accounts/filter',         [AccountController::class, 'filter'])->name('accounts.filter')->middleware('hasPermission:account_read');
                        Route::get('accounts/create',         [AccountController::class, 'create'])->name('accounts.create')->middleware('hasPermission:account_create');
                        Route::post('accounts/store',         [AccountController::class, 'store'])->name('accounts.store')->middleware('hasPermission:account_create');
                        Route::get('accounts/edit/{id}',      [AccountController::class, 'edit'])->name('accounts.edit')->middleware('hasPermission:account_update');
                        Route::get('accounts/view/{id}',      [AccountController::class, 'view'])->name('accounts.view');
                        Route::put('accounts/update/{id}',    [AccountController::class, 'update'])->name('accounts.update')->middleware('hasPermission:account_update');
                        Route::delete('accounts/delete/{id}', [AccountController::class, 'destroy'])->name('accounts.delete')->middleware('hasPermission:account_delete');
                        Route::post('accounts/current-balance', [AccountController::class, 'currentBalance'])->name('accounts.current-balance');
                        // Fund Transfer Routes
                        Route::get('fund-transfer/index',          [FundTransferController::class, 'index'])->name('fund-transfer.index')->middleware('hasPermission:fund_transfer_read');
                        Route::get('fund-transfer/create',         [FundTransferController::class, 'create'])->name('fund-transfer.create')->middleware('hasPermission:fund_transfer_create');
                        Route::post('fund-transfer/store',         [FundTransferController::class, 'store'])->name('fund-transfer.store')->middleware('hasPermission:fund_transfer_create');
                        Route::get('fund-transfer/edit/{id}',      [FundTransferController::class, 'edit'])->name('fund-transfer.edit')->middleware('hasPermission:fund_transfer_update');
                        Route::get('fund-transfer/view/{id}',      [FundTransferController::class, 'view'])->name('fund-transfer.view');
                        Route::put('fund-transfer/update/{id}',    [FundTransferController::class, 'update'])->name('fund-transfer.update')->middleware('hasPermission:fund_transfer_update');
                        Route::delete('fund-transfer/delete/{id}', [FundTransferController::class, 'destroy'])->name('fund-transfer.delete')->middleware('hasPermission:fund_transfer_delete');
                        Route::get('fund-transfer/specific/search', [FundTransferController::class, 'fundTransferSpecificSearch'])->name('fund.transfer.specific.search')->middleware('hasPermission:fund_transfer_read');
                        Route::get('fund-transfer/search/flter/print', [FundTransferController::class, 'fundTransferSearchFilterPrint'])->name('fund.transfer.search.filter.print')->middleware('hasPermission:fund_transfer_read');
                        Route::get('fund-transfer/filter',         [FundTransferController::class, 'fundTransferFilter'])->name('fund.transfer.filter')->middleware('hasPermission:fund_transfer_read');
                        // Designation
                        Route::get('designations',              [DesignationController::class, 'index'])->name('designations.index')->middleware('hasPermission:designation_read');
                        Route::get('designations/create',       [DesignationController::class, 'create'])->name('designations.create')->middleware('hasPermission:designation_create');
                        Route::post('designations/store',       [DesignationController::class, 'store'])->name('designations.store')->middleware('hasPermission:designation_create');
                        Route::get('designations/edit/{id}',    [DesignationController::class, 'edit'])->name('designations.edit')->middleware('hasPermission:designation_update');
                        Route::put('designations/update',       [DesignationController::class, 'update'])->name('designations.update')->middleware('hasPermission:designation_update');
                        Route::delete('designation/delete/{id}', [DesignationController::class, 'destroy'])->name('designation.delete')->middleware('hasPermission:designation_delete');
                        // Department
                        Route::get('departments',               [DepartmentController::class, 'index'])->name('departments.index')->middleware('hasPermission:department_read');
                        Route::get('departments/create',        [DepartmentController::class, 'create'])->name('departments.create')->middleware('hasPermission:department_create');
                        Route::post('departments/store',        [DepartmentController::class, 'store'])->name('departments.store')->middleware('hasPermission:department_create');
                        Route::get('departments/edit/{id}',     [DepartmentController::class, 'edit'])->name('departments.edit')->middleware('hasPermission:department_update');
                        Route::put('departments/update',        [DepartmentController::class, 'update'])->name('departments.update')->middleware('hasPermission:department_update');
                        Route::delete('department/delete/{id}', [DepartmentController::class, 'destroy'])->name('department.delete')->middleware('hasPermission:department_delete');
                        // Fraud
                        Route::get('fraud',                [FraudController::class, 'index'])->name('fraud.index')->middleware('hasPermission:fraud_read');
                        Route::get('fraud/create',         [FraudController::class, 'create'])->name('fraud.create')->middleware('hasPermission:fraud_create');
                        Route::post('fraud/store',         [FraudController::class, 'store'])->name('fraud.store')->middleware('hasPermission:fraud_create');
                        Route::get('fraud/edit/{id}',      [FraudController::class, 'edit'])->name('fraud.edit')->middleware('hasPermission:fraud_update');
                        Route::put('fraud/update',         [FraudController::class, 'update'])->name('fraud.update')->middleware('hasPermission:fraud_update');
                        Route::delete('fraud/delete/{id}', [FraudController::class, 'destroy'])->name('fraud.delete')->middleware('hasPermission:fraud_delete');
                        // To_do List route
                        Route::get('todo/todo_list',        [TodoController::class, 'index'])->name('todo.index')->middleware('hasPermission:todo_read');
                        Route::post('todo/todo_add',        [TodoController::class, 'store'])->name('todo.store')->middleware('hasPermission:todo_create');
                        Route::post('todo/momal',           [TodoController::class, 'todoModal'])->name('todo.modal');
                        Route::post('todo/processing',      [TodoController::class, 'todoProcessing'])->name('todo.processing')->middleware('hasPermission:todo_update');
                        Route::post('todo/completed',       [TodoController::class, 'todoComplete'])->name('todo.completed')->middleware('hasPermission:todo_update');
                        Route::put('todo/update',           [TodoController::class, 'update'])->name('todo.update')->middleware('hasPermission:todo_update');
                        Route::delete('todo/delete/{id}',   [TodoController::class, 'destroy'])->name('todo.delete')->middleware('hasPermission:todo_delete');
                        // Support route
                        Route::get('support/index',         [SupportController::class, 'index'])->name('support.index')->middleware('hasPermission:support_read');
                        Route::get('support/create',        [SupportController::class, 'create'])->name('support.add')->middleware('hasPermission:support_create');
                        Route::post('support/store',        [SupportController::class, 'store'])->name('support.store')->middleware('hasPermission:support_create');
                        Route::get('support/edit/{id}',     [SupportController::class, 'edit'])->name('support.edit')->middleware('hasPermission:support_update');
                        Route::put('support/update',        [SupportController::class, 'update'])->name('support.update')->middleware('hasPermission:support_update');
                        Route::delete('support/delete/{id}', [SupportController::class, 'destroy'])->name('support.delete')->middleware('hasPermission:support_delete');
                        Route::get('support/view/{id}',     [SupportController::class, 'view'])->name('support.view');
                        Route::post('support/reply',        [SupportController::class, 'supportReply'])->name('support.reply')->middleware('hasPermission:support_reply');
                        Route::get('support/status-update/{id}',  [SupportController::class, 'statusUpdate'])->name('support.status.update')->middleware('hasPermission:support_status_update');

                        //account heads
                        Route::get('/account-heads', [AccountHeadsController::class, 'index'])->name('account.heads.index')->middleware('hasPermission:account_heads_read');
                        Route::get('sms-settings/index',            [SmsSettingsController::class, 'index'])->name('sms-settings.index')->middleware('hasPermission:sms_settings_read');
                        Route::get('sms-settings/create',           [SmsSettingsController::class, 'create'])->name('sms-settings.create')->middleware('hasPermission:sms_settings_create');
                        Route::post('sms-settings/store',           [SmsSettingsController::class, 'store'])->name('sms-settings.store')->middleware('hasPermission:sms_settings_create');
                        Route::get('sms-settings/edit/{id}',        [SmsSettingsController::class, 'edit'])->name('sms-settings.edit')->middleware('hasPermission:sms_settings_update');
                        Route::put('sms-settings/update/{id}',      [SmsSettingsController::class, 'update'])->name('sms-settings.update')->middleware('hasPermission:sms_settings_update');
                        Route::delete('sms-settings/delete/{id}',   [SmsSettingsController::class, 'delete'])->name('sms-settings.delete')->middleware('hasPermission:sms_settings_delete');
                        Route::post('sms-settings/status',          [SmsSettingsController::class, 'status'])->name('sms-settings.status')->middleware('hasPermission:sms_settings_status_change');
                        Route::get('sms-send-settings/index',       [SmsSendSettingsController::class, 'index'])->name('sms-send-settings.index')->middleware('hasPermission:sms_send_settings_read');
                        Route::post('sms-send-settings/status',     [SmsSendSettingsController::class, 'status'])->name('sms-send-settings.status')->middleware('hasPermission:sms_send_settings_status_change');
                        // General settings
                        Route::get('general-settings/index',        [GeneralSettingsController::class, 'index'])->name('general-settings.index')->middleware('hasPermission:general_settings_read');
                        Route::put('general-settings/update',       [GeneralSettingsController::class, 'update'])->name('general-settings.update')->middleware('hasPermission:general_settings_update');
                        //currency settings
                        Route::get('currency',                      [CurrencyController::class, 'index'])->name('currency.index')->middleware('hasPermission:currency_read');
                        Route::get('currency/create',               [CurrencyController::class, 'create'])->name('currency.create')->middleware('hasPermission:currency_create');
                        Route::post('currency/store',               [CurrencyController::class, 'store'])->name('currency.store')->middleware('hasPermission:currency_create');
                        Route::get('currency/edit/{id}',            [CurrencyController::class, 'edit'])->name('currency.edit')->middleware('hasPermission:currency_update');
                        Route::put('currency/update',               [CurrencyController::class, 'update'])->name('currency.update')->middleware('hasPermission:currency_update');
                        Route::delete('currency/delete/{id}',       [CurrencyController::class, 'delete'])->name('currency.delete')->middleware('hasPermission:currency_delete');
                        // Asset Categorys Routes
                        Route::get('asset-category/index',          [AssetcategoryController::class, 'index'])->name('asset-category.index')->middleware('hasPermission:asset_category_read');
                        Route::get('asset-category/create',         [AssetcategoryController::class, 'create'])->name('asset-category.create')->middleware('hasPermission:asset_category_create');
                        Route::post('asset-category/store',         [AssetcategoryController::class, 'store'])->name('asset-category.store')->middleware('hasPermission:asset_category_create');
                        Route::get('asset-category/edit/{id}',      [AssetcategoryController::class, 'edit'])->name('asset-category.edit')->middleware('hasPermission:asset_category_update');
                        Route::get('asset-category/view/{id}',      [AssetcategoryController::class, 'view'])->name('asset-category.view')->middleware('hasPermission:asset_category_read');
                        Route::put('asset-category/update',         [AssetcategoryController::class, 'update'])->name('asset-category.update')->middleware('hasPermission:asset_category_update');
                        Route::delete('asset-category/delete/{id}', [AssetcategoryController::class, 'destroy'])->name('asset-category.delete')->middleware('hasPermission:asset_category_delete');
                        // News & Offer
                        Route::get('news-offer',                [NewsOfferController::class, 'index'])->name('news-offer.index')->middleware('hasPermission:news_offer_read');
                        Route::get('news-offer/create',         [NewsOfferController::class, 'create'])->name('news-offer.create')->middleware('hasPermission:news_offer_create');
                        Route::post('news-offer/store',         [NewsOfferController::class, 'store'])->name('news-offer.store')->middleware('hasPermission:news_offer_create');
                        Route::get('news-offer/edit/{id}',      [NewsOfferController::class, 'edit'])->name('news-offer.edit')->middleware('hasPermission:news_offer_update');
                        Route::put('news-offer/update/{id}',    [NewsOfferController::class, 'update'])->name('news-offer.update')->middleware('hasPermission:news_offer_update');
                        Route::delete('news-offer/delete/{id}', [NewsOfferController::class, 'destroy'])->name('news-offer.delete')->middleware('hasPermission:news_offer_delete');
                        // Asset Routes
                        Route::get('assets/index',          [AssetController::class, 'index'])->name('asset.index')->middleware('hasPermission:assets_read');
                        Route::get('assets/create',         [AssetController::class, 'create'])->name('asset.create')->middleware('hasPermission:assets_create');
                        Route::post('assets/store',         [AssetController::class, 'store'])->name('asset.store')->middleware('hasPermission:assets_create');
                        Route::get('assets/edit/{id}',      [AssetController::class, 'edit'])->name('asset.edit')->middleware('hasPermission:assets_update');
                        Route::get('assets/view/{id}',      [AssetController::class, 'view'])->name('asset.view')->middleware('hasPermission:assets_read');
                        Route::put('assets/update',         [AssetController::class, 'update'])->name('asset.update')->middleware('hasPermission:assets_update');
                        Route::delete('assets/delete/{id}', [AssetController::class, 'destroy'])->name('asset.delete')->middleware('hasPermission:assets_delete');
                        //reports
                        Route::get('reports/parcel-total-summery',          [TotalSummeryReportController::class, 'parcelTotalSummery'])->name('parcel.total.summery.index')->middleware('hasPermission:parcel_total_summery');
                        Route::get('reports/parcel-filter-total-summery',   [TotalSummeryReportController::class, 'parcelTotalSummeryFilter'])->name('parcel.filter.total.summery')->middleware('hasPermission:parcel_total_summery');
                        Route::get('reports/parcel-reports',                [ReportsController::class, 'parcelReports'])->name('parcel.reports')->middleware('hasPermission:parcel_status_reports');
                        Route::get('reports/parcel-filter-reports',         [ReportsController::class, 'parcelSReports'])->name('parcel.filter.reports')->middleware('hasPermission:parcel_status_reports');
                        Route::get('parcel-reports-print-page/{array}',     [ReportsController::class, 'parcelReportsPrint'])->name('parcel.reports.print.page')->middleware('hasPermission:parcel_status_reports');
                        Route::get('reports/parcel-wise-reports',            [ReportsController::class, 'parcelWiseReports'])->name('parcel.wise.profit.index')->middleware('hasPermission:parcel_wise_profit');
                        Route::post('reports-tracking-parcels',              [ReportsController::class, 'reportsTrackingParcels'])->name('reports-tracking-parcels')->middleware('hasPermission:parcel_wise_profit');
                        Route::get('reports/parcel-wise-profit-reports',     [ReportsController::class, 'ParcelWiseProfitReports'])->name('parcel.wise.profit.reports')->middleware('hasPermission:parcel_wise_profit');
                        Route::get('parcel-wise-profit-print-page/{array}', [ReportsController::class, 'parcelWiseProfitPrint'])->name('parcel.wise.profit.print.page')->middleware('hasPermission:parcel_wise_profit');
                        Route::get('reports/salary-reports',                 [ReportsController::class, 'salaryReports'])->name('salary.reports')->middleware('hasPermission:salary_reports');
                        Route::get('reports/reports-salary-reports',         [ReportsController::class, 'ReportssalaryReports'])->name('reports.salary.reports')->middleware('hasPermission:salary_reports');
                        Route::get('reports/salary-report-print',           [ReportsController::class, 'SalaryReportPrint'])->name('salary.reports.print.page')->middleware('hasPermission:salary_reports');
                        Route::get('reports/merchant-hub-deliveryman',       [ReportsController::class, 'MerchantHubDeliverymanReports'])->name('merchant.hub.deliveryman.reports')->middleware('hasPermission:merchant_hub_deliveryman');
                        Route::get('reports/mhd-reports',                    [ReportsController::class, 'MHDreports'])->name('reports.mhd.reports')->middleware('hasPermission:merchant_hub_deliveryman');
                        Route::get('reports/merchnat-hub-delivery-reports-print-page',   [ReportsController::class, 'MerchantHubDeliveryReportsPrintPage'])->name('merchant.hub.deliveryman.reports.print-page')->middleware('hasPermission:merchant_hub_deliveryman');
                        //export
                        Route::get('reports/mhd-pdf',                        [ReportsController::class, 'mhdPDF'])->name('merchant.hub.deliveryman.pdf');
                        // database backup
                        Route::get('/database-backup',             [DatabaseBackupController::class, 'index'])->name('database.backup.index')->middleware('hasPermission:database_backup_read');
                        Route::get('database-backup/download',     [DatabaseBackupController::class, 'databaseBackup'])->name('database.backup.download')->middleware('hasPermission:database_backup_read');
                        //invoice generate
                        Route::get('settings/invoice-generate-menually/index', [MerchantInvoiceController::class, 'InvoiceGenerateMenuallyIndex'])->name('invoice.generate.menually.index')->middleware('hasPermission:invoice_generate_menually');
                        Route::get('settings/invoice-generate-menually',      [MerchantInvoiceController::class, 'InvoiceGenerateMenually'])->name('invoice.generate.menually')->middleware('hasPermission:invoice_generate_menually');
                        //salary generate
                        Route::get('salary/salary-generate',               [SalaryGenerateController::class, 'index'])->name('salary.generate.index')->middleware('hasPermission:salary_generate_read');
                        Route::post('salary/salary-auto-generate',         [SalaryGenerateController::class, 'salaryAutoGenerate'])->name('salary.auto.generate')->middleware('hasPermission:salary_generate_create');
                        Route::get('salary/salary-generate/create',        [SalaryGenerateController::class, 'create'])->name('salary.generate.create')->middleware('hasPermission:salary_generate_create');
                        Route::post('salary/salary-generate/store',        [SalaryGenerateController::class, 'store'])->name('salary.generate.store')->middleware('hasPermission:salary_generate_create');
                        Route::get('salary/salary-generate/edit/{id}',     [SalaryGenerateController::class, 'edit'])->name('salary.generate.edit')->middleware('hasPermission:salary_generate_update');
                        Route::put('salary/salary-generate/update',        [SalaryGenerateController::class, 'update'])->name('salary.generate.update')->middleware('hasPermission:salary_generate_update');
                        Route::delete('salary/salary-generate/delete/{id}', [SalaryGenerateController::class, 'salaryGenerateDelete'])->name('salary-generate.delete')->middleware('hasPermission:salary_generate_delete');
                        Route::get('subscribe',                            [SalaryGenerateController::class, 'subscribe'])->name('subscribe.index');
                        //pickup request
                        Route::prefix('pickup-request')->name('pickup.request.')->group(function () {
                            Route::get('regular',                      [PickupRequestController::class, 'regular'])->name('regular')->middleware('hasPermission:pickup_request_regular');
                            Route::get('express',                      [PickupRequestController::class, 'express'])->name('express')->middleware('hasPermission:pickup_request_express');
                        });
                        //parcel search
                        Route::get('parcel/specific/search',                    [ParcelController::class, 'ParcelSearchs'])->name('parcel.specific.search');
                        // GoogleMap settings
                        Route::get('googlemap-settings/index',        [GoogleMapSettingsController::class, 'index'])->name('googlemap-settings.index');
                        Route::put('googlemap-settings/update',       [GoogleMapSettingsController::class, 'update'])->name('googlemap-settings.update');

                        // Notification settings
                        Route::get('notification-settings/index',        [NotificationSettingsController::class, 'index'])->name('notification-settings.index')->middleware('hasPermission:notification_settings_read');
                        Route::put('notification-settings/update',       [NotificationSettingsController::class, 'update'])->name('notification-settings.update')->middleware('hasPermission:notification_settings_update');
                        // push-notification
                        Route::get('push-notification',                [PushNotificationController::class, 'index'])->name('push-notification.index')->middleware('hasPermission:push_notification_read');
                        Route::get('push-notification/create',         [PushNotificationController::class, 'create'])->name('push-notification.create')->middleware('hasPermission:push_notification_create');
                        Route::post('push-notification/store',         [PushNotificationController::class, 'store'])->name('push-notification.store')->middleware('hasPermission:push_notification_create');
                        Route::delete('push-notification/delete/{id}', [PushNotificationController::class, 'destroy'])->name('push-notification.delete')->middleware('hasPermission:push_notification_delete');
                        Route::post('push-notification/users',        [PushNotificationController::class, 'Users'])->name('push-notification.users');

                        //social login settings
                        Route::get('social-login-settings',                     [SocialLoginController::class, 'socialLoginSettingsIndex'])->name('social.login.settings.index')->middleware('hasPermission:social_login_settings_read');
                        Route::put('social-login-settings/update/{social}',     [SocialLoginController::class, 'socialLoginSettingsUpdate'])->name('social.login.settings.update')->middleware('hasPermission:social_login_settings_update');
                        //Payout
                        Route::prefix('payout')->name('payout.')->group(function () {
                            //stripe payment gateway
                            Route::get('/',                                     [PayoutController::class, 'index'])->name('index');
                            Route::get('/merchant/payout',                      [PayoutController::class, 'merchantPayout'])->name('merchant.payout');
                            Route::get('/stripe',                               [PayoutController::class, 'stripe'])->name('merchant.stripe');
                            Route::post('/stripe/post',                         [PayoutController::class, 'stripePost'])->name('merchant.stripe.post');

                            Route::get('/razorpay',                              [PayoutController::class, 'razorpay'])->name('merchant.razorpay');
                            Route::get('/razorpay/payment',                     [PayoutController::class, 'razorpayPost'])->name('merchant.razorpay.post');

                            //paypal payment gateway
                            Route::get('paypal-index',                          [PayoutController::class, 'paypalIndex'])->name('paypal.index');
                            Route::post('paypal-payment',                       [PayoutController::class, 'paypalpayment'])->name('paypal');
                            // SSLCOMMERZ Start
                            Route::get('/sslcommerz',                 [AdminSslCommerzController::class, 'sslcommerzIndex'])->name('sslcommerz.index');
                            Route::post('/pay-via-ajax',              [AdminSslCommerzController::class, 'payViaAjax'])->name('pay.via.ajax');
                            Route::post('/success',                   [AdminSslCommerzController::class, 'success']);
                            Route::post('/fail',                      [AdminSslCommerzController::class, 'fail']);
                            Route::post('/cancel',                    [AdminSslCommerzController::class, 'cancel']);
                            Route::post('/ipn',                       [AdminSslCommerzController::class, 'ipn']);
                            //skrill payment start
                            Route::get('skrill',                      [AdminSkrillController::class, 'index'])->name('skrill.index');
                            Route::get('skrill-make-payment',         [AdminSkrillController::class, 'makePayment'])->name('skrill.make.payment');
                            Route::get('payment-completed',           [AdminSkrillController::class, 'paymentCompleted'])->name('skrill.payment.completed');
                            Route::get('payment-cancelled',           [AdminSkrillController::class, 'PaymentCancelled']);
                            //amarpay
                            Route::get('/aamarpay',                   [AdminAamarpayController::class, 'aamarpayIndex'])->name('aamarpay.index');
                            Route::get('/aamarpay-payment',           [AdminAamarpayController::class, 'payment'])->name('aamarpay.payment');
                            Route::post('/aamarpay-success',          [AdminAamarpayController::class, 'success'])->name('aamarpay.payment.success');
                            Route::post('/aamarpay-fail',             [AdminAamarpayController::class, 'fail'])->name('aamarpay.payment.fail');
                            //bkash payment
                            Route::get('/online-payment/bkash',       [AdminBkashController::class, 'index'])->name('bkash.index');
                            Route::get('bkash/redirect',              [AdminBkashController::class, 'bkashRedirect'])->name('bkash.redirect');
                            Route::get('bkash/execute',               [AdminBkashController::class, 'bkashExecute'])->name('bkash.execute');
                        });
                        Route::get('online-payment-list',                                [PayoutSetupController::class, 'onlinePaymentList'])->name('online.payment.list')->middleware('hasPermission:online_payment_read');
                        //payout setup settings
                        Route::get('/settings/pay-out/setup',                            [PayoutSetupController::class, 'index'])->name('payout.setup.settings.index')->middleware('hasPermission:payout_setup_settings_read');
                        Route::put('/settings/pay-out/setup/update/{paymentmethod}',     [PayoutSetupController::class, 'PayoutSetupUpdate'])->name('payout.setup.settings.update')->middleware('hasPermission:payout_setup_settings_update');


                        //wallet request
                        Route::prefix('wallet-request')
                            ->controller(WalletController::class)
                            ->name('wallet.request.')
                            ->group(function () {
                                Route::get('/',                 'requestIndex')->name('index')->middleware('hasPermission:wallet_request_read');
                                Route::post('/recharge',        'adminstore')->name('recharge')->middleware('hasPermission:wallet_request_create');
                                Route::delete('/delete/{id}',    'delete')->name('delete')->middleware('hasPermission:wallet_request_delete');
                                Route::put('/approve/{id}',     'approve')->name('approve')->middleware('hasPermission:wallet_request_approve');
                                Route::put('/reject/{id}',      'reject')->name('reject')->middleware('hasPermission:wallet_request_reject');
                            });

                        //Front web (frontend setup) ============================
                        Route::prefix('front-web')->group(function () {
                            //social link
                            Route::prefix('social-link')
                                ->name('social.link.')
                                ->controller(SocialLinkController::class)
                                ->group(function () {
                                    Route::get('/',              'index')->name('index')->middleware('hasPermission:social_link_read');
                                    Route::get('create',         'create')->name('create')->middleware('hasPermission:social_link_create');
                                    Route::post('store',         'store')->name('store')->middleware('hasPermission:social_link_create');
                                    Route::get('edit/{id}',      'edit')->name('edit')->middleware('hasPermission:social_link_update');
                                    Route::put('update/{id}',    'update')->name('update')->middleware('hasPermission:social_link_update');
                                    Route::delete('delete/{id}', 'delete')->name('delete')->middleware('hasPermission:social_link_delete');
                                });

                            //Service
                            Route::prefix('service')
                                ->name('service.')
                                ->controller(ServiceController::class)
                                ->group(function () {
                                    Route::get('/',              'index')->name('index')->middleware('hasPermission:service_read');
                                    Route::get('create',         'create')->name('create')->middleware('hasPermission:service_create');
                                    Route::post('store',         'store')->name('store')->middleware('hasPermission:service_create');
                                    Route::get('edit/{id}',      'edit')->name('edit')->middleware('hasPermission:service_update');
                                    Route::put('update/{id}',    'update')->name('update')->middleware('hasPermission:service_update');
                                    Route::delete('delete/{id}', 'delete')->name('delete')->middleware('hasPermission:service_delete');
                                });

                            //why courier
                            Route::prefix('why-courier')
                                ->name('why.courier.')
                                ->controller(WhyCourierController::class)
                                ->group(function () {
                                    Route::get('/',              'index')->name('index')->middleware('hasPermission:why_courier_read');
                                    Route::get('create',         'create')->name('create')->middleware('hasPermission:why_courier_create');
                                    Route::post('store',         'store')->name('store')->middleware('hasPermission:why_courier_create');
                                    Route::get('edit/{id}',      'edit')->name('edit')->middleware('hasPermission:why_courier_update');
                                    Route::put('update/{id}',    'update')->name('update')->middleware('hasPermission:why_courier_update');
                                    Route::delete('delete/{id}', 'delete')->name('delete')->middleware('hasPermission:why_courier_delete');
                                });

                            //faq
                            Route::prefix('faq')
                                ->name('faq.')
                                ->controller(FaqController::class)
                                ->group(function () {
                                    Route::get('/',              'index')->name('index')->middleware('hasPermission:faq_read');
                                    Route::get('create',         'create')->name('create')->middleware('hasPermission:faq_create');
                                    Route::post('store',         'store')->name('store')->middleware('hasPermission:faq_create');
                                    Route::get('edit/{id}',      'edit')->name('edit')->middleware('hasPermission:faq_update');
                                    Route::put('update/{id}',    'update')->name('update')->middleware('hasPermission:faq_update');
                                    Route::delete('delete/{id}', 'delete')->name('delete')->middleware('hasPermission:faq_delete');
                                });

                            //partner
                            Route::prefix('partner')
                                ->name('partner.')
                                ->controller(PartnerController::class)
                                ->group(function () {
                                    Route::get('/',              'index')->name('index')->middleware('hasPermission:partner_read');
                                    Route::get('create',         'create')->name('create')->middleware('hasPermission:partner_create');
                                    Route::post('store',         'store')->name('store')->middleware('hasPermission:partner_create');
                                    Route::get('edit/{id}',      'edit')->name('edit')->middleware('hasPermission:partner_update');
                                    Route::put('update/{id}',    'update')->name('update')->middleware('hasPermission:partner_update');
                                    Route::delete('delete/{id}', 'delete')->name('delete')->middleware('hasPermission:partner_delete');
                                });

                            //blogs
                            Route::prefix('blogs')
                                ->name('blogs.')
                                ->controller(BlogController::class)
                                ->group(function () {
                                    Route::get('/',              'index')->name('index')->middleware('hasPermission:blogs_read');
                                    Route::get('create',         'create')->name('create')->middleware('hasPermission:blogs_create');
                                    Route::post('store',         'store')->name('store')->middleware('hasPermission:blogs_create');
                                    Route::get('edit/{id}',      'edit')->name('edit')->middleware('hasPermission:blogs_update');
                                    Route::put('update/{id}',    'update')->name('update')->middleware('hasPermission:blogs_update');
                                    Route::delete('delete/{id}', 'delete')->name('delete')->middleware('hasPermission:blogs_delete');
                                });

                            //pages
                            Route::prefix('pages')
                                ->name('pages.')
                                ->controller(PageController::class)
                                ->group(function () {
                                    Route::get('/',              'index')->name('index')->middleware('hasPermission:pages_read');
                                    Route::get('edit/{id}',      'edit')->name('edit')->middleware('hasPermission:pages_update');
                                    Route::put('update/{id}',    'update')->name('update')->middleware('hasPermission:pages_update');
                                });

                            //pages
                            Route::prefix('section')
                                ->name('section.')
                                ->controller(SectionController::class)
                                ->group(function () {
                                    Route::get('/',              'index')->name('index')->middleware('hasPermission:section_read');
                                    Route::get('edit/{id}',      'edit')->name('edit')->middleware('hasPermission:section_update');
                                    Route::put('update/{id}',    'update')->name('update')->middleware('hasPermission:section_update');
                                });
                        });
                        //end Front web (frontend setup) ============================

                    });


                    // Merchant panel Routes
                    Route::group(['prefix' => 'merchant'], function () {
                        Route::post('/dashboard/filter',                     [DashbordController::class, 'merchantDashboardFilter'])->name('merchant-panel.dashboard.filter');
                        //accounts
                        Route::get('/accounts/payment-accounts',              [PaymentAccountController::class, 'index'])->name('merchant.accounts.payment-account.index');
                        Route::get('/accounts/payment-accounts/create',       [PaymentAccountController::class, 'create'])->name('payment.account.create');
                        Route::post('/accounts/payment-account/store',        [PaymentAccountController::class, 'store'])->name('payment.account.store');
                        Route::get('/accounts/payment-account/edit/{id}',     [PaymentAccountController::class, 'edit'])->name('payment.account.edit');
                        Route::put('/accounts/payment-account/update',        [PaymentAccountController::class, 'update'])->name('payment.account.update');
                        Route::delete('/accounts/payment-account/delete/{id}', [PaymentAccountController::class, 'delete'])->name('payment.account.delete');
                        // Account Transaction
                        Route::get('/accounts/account-transaction',         [AccountTransactionController::class, 'index'])->name('merchant.accounts.account-transaction.index');
                        Route::post('/accounts/account-transaction-filter', [AccountTransactionController::class, 'filter'])->name('merchant.accounts.account-transaction.filter');
                        // Statements
                        Route::get('/accounts/statements',                  [StatementsController::class, 'index'])->name('merchant.accounts.statements.index');
                        Route::post('/accounts/statements-filter',          [StatementsController::class, 'filter'])->name('merchant.accounts.statements.filter');
                        //settings
                        Route::get('/settings/cod-charges',     [SettingsController::class, 'CODcharges'])->name('merchant.cod-charges.index');
                        Route::get('/settings/delivery-charges', [SettingsController::class, 'deliveryCharges'])->name('merchant.delivery-charges.index');
                        // Merchant profile
                        Route::get('profile/{id}',                  [MerchantProfileController::class, 'view'])->name('merchant-profile.index')->withoutMiddleware('subscriptionCheck');
                        Route::get('profile/update/{id}',           [MerchantProfileController::class, 'create'])->name('merchant-profile.edit')->withoutMiddleware('subscriptionCheck');
                        Route::get('profile/change-password/{id}',  [MerchantProfileController::class, 'changePassword'])->name('merchant-password.change')->withoutMiddleware('subscriptionCheck');
                        Route::put('profile/update/{id}',           [MerchantProfileController::class, 'update'])->name('merchant-profile.update')->withoutMiddleware('subscriptionCheck');
                        Route::put('profile/update-password/{id}',  [MerchantProfileController::class, 'updatePassword'])->name('merchant-profile.password.update')->withoutMiddleware('subscriptionCheck');
                        // Shops routes
                        Route::get('shops/index',            [ShopsController::class, 'index'])->name('merchant-panel.shops.index');
                        Route::get('shops/create',           [ShopsController::class, 'create'])->name('merchant-panel.shops.create');
                        Route::post('shops/store',           [ShopsController::class, 'store'])->name('merchant-panel.shops.store');
                        Route::get('shops/edit/{id}',        [ShopsController::class, 'edit'])->name('merchant-panel.shops.edit');
                        Route::put('shops/update/{id}',      [ShopsController::class, 'update'])->name('merchant-panel.shops.update');
                        Route::delete('shops/delete/{id}',   [ShopsController::class, 'delete'])->name('merchant-panel.shops.delete');
                        // Parcel Routes
                        Route::get('parcel/filter',          [MerchantParcelController::class, 'filter'])->name('merchant-panel.parcel.filter');
                        Route::get('parcel/index',           [MerchantParcelController::class, 'index'])->name('merchant-panel.parcel.index');
                        Route::get('parcel-bank/index',      [MerchantParcelController::class, 'parcelBank'])->name('merchant-panel.parcel-bank.index');
                        Route::get('parcel/create',          [MerchantParcelController::class, 'create'])->name('merchant-panel.parcel.create');
                        Route::post('parcel/store',          [MerchantParcelController::class, 'store'])->name('merchant-panel.parcel.store');
                        Route::get('parcel/clone/{id}',      [MerchantParcelController::class, 'duplicate'])->name('merchant-parcel.clone');
                        Route::post('parcel/clone-store',    [MerchantParcelController::class, 'duplicateStore'])->name('merchant-parcel.clone-store');
                        Route::get('parcel/edit/{id}',       [MerchantParcelController::class, 'edit'])->name('merchant-panel.parcel.edit');
                        Route::get('parcel/details/{id}',    [MerchantParcelController::class, 'details'])->name('merchant-panel.parcel.details');
                        Route::get('parcel/logs/{id}',       [MerchantParcelController::class, 'logs'])->name('merchant-panel.parcel.logs');
                        Route::put('parcel/update/{id}',     [MerchantParcelController::class, 'update'])->name('merchant-panel.parcel.update');
                        Route::get('parcel/status-update/{id}/{status_id}',   [MerchantParcelController::class, 'statusUpdate'])->name('merchant-panel.parcel.status-update');
                        Route::delete('parcel/delete/{id}',     [MerchantParcelController::class, 'destroy'])->name('merchant-panel.parcel.delete');
                        Route::post('parcel/merchant',          [MerchantParcelController::class, 'getMerchant'])->name('merchant-panel.parcel.merchant.get');
                        Route::post('parcel/merchant/shops',    [MerchantParcelController::class, 'merchantShops'])->name('merchant-panel.parcel.merchant.shops');
                        Route::post('parcel/delivery-category', [MerchantParcelController::class, 'deliveryWeight'])->name('merchant-panel.parcel.deliveryCategory.deliveryWeight');
                        Route::post('parcel/delivery-charge',   [MerchantParcelController::class, 'deliveryCharge'])->name('merchant-panel.parcel.deliveryCharge.get');
                        Route::post('parcel/quote',             ParcelQuoteController::class)->name('merchant-panel.parcel.quote');
                        //import
                        Route::get('parcel/import-parcel',  [MerchantParcelController::class, 'parcelImportExport'])->name('merchant-panel.parcel.parcel-import');
                        Route::post('parcel/file-import',   [MerchantParcelController::class, 'parcelImport'])->name('merchant-panel.parcel.file-import');
                        Route::get('parcel/file-export',    [MerchantParcelController::class, 'parcelExport'])->name('merchant-panel.parcel.file-export');
                        Route::get('reports/parcel-reports',                [MerchantReportsController::class, 'parcelReports'])->name('merchant-panel.parcel.reports');
                        Route::get('reports/parcel-filter-reports',         [MerchantReportsController::class, 'parcelSReports'])->name('merchant-panel.parcel.filter.reports');
                        Route::get('parcel-reports-print-page/{array}',     [MerchantReportsController::class, 'parcelReportsPrint'])->name('merchant-panel.parcel.reports.print.page');
                        //payment request
                        Route::get('payment-request/index',         [PaymentRequestController::class, 'index'])->name('merchant-panel.payment-request.index');
                        Route::get('payment-request/create',        [PaymentRequestController::class, 'create'])->name('merchant-panel.payment-request.create');
                        Route::post('payment-request/store',        [PaymentRequestController::class, 'store'])->name('merchant-panel.payment-request.store');
                        Route::get('payment-request/edit/{id}',     [PaymentRequestController::class, 'edit'])->name('merchant-panel.payment-request.edit');
                        Route::put('payment-request/update',        [PaymentRequestController::class, 'update'])->name('merchant-panel.payment-request.update');
                        Route::delete('payment-request/delete/{id}', [PaymentRequestController::class, 'delete'])->name('merchant-panel.payment-request.delete');
                        // News & Offer
                        Route::get('news-offer/index',      [MerchantNewsOfferController::class, 'index'])->name('merchant-panel.news-offer.index');
                        // Support
                        Route::get('support/index',          [MerchantPanelSupportController::class, 'index'])->name('merchant-panel.support.index');
                        Route::get('support/create',         [MerchantPanelSupportController::class, 'create'])->name('merchant-panel.support.add');
                        Route::post('support/store',         [MerchantPanelSupportController::class, 'store'])->name('merchant-panel.support.store');
                        Route::get('support/edit/{id}',      [MerchantPanelSupportController::class, 'edit'])->name('merchant-panel.support.edit');
                        Route::put('support/update/{id}',    [MerchantPanelSupportController::class, 'update'])->name('merchant-panel.support.update');
                        Route::delete('support/delete/{id}', [MerchantPanelSupportController::class, 'destroy'])->name('merchant-panel.support.delete');
                        Route::get('support/view/{id}',      [MerchantPanelSupportController::class, 'view'])->name('merchant-panel.support.view');
                        Route::post('support/reply',         [MerchantPanelSupportController::class, 'supportReply'])->name('merchant-panel.support.reply');
                        // Fraud
                        Route::get('fraud',                [MerchantPanelFraudController::class, 'index'])->name('merchant-panel.fraud.index');
                        Route::get('fraud/create',         [MerchantPanelFraudController::class, 'create'])->name('merchant-panel.fraud.create');
                        Route::post('fraud/store',         [MerchantPanelFraudController::class, 'store'])->name('merchant-panel.fraud.store');
                        Route::get('fraud/edit/{id}',      [MerchantPanelFraudController::class, 'edit'])->name('merchant-panel.fraud.edit');
                        Route::put('fraud/update',         [MerchantPanelFraudController::class, 'update'])->name('merchant-panel.fraud.update');
                        Route::delete('fraud/delete/{id}', [MerchantPanelFraudController::class, 'destroy'])->name('merchant-panel.fraud.delete');
                        Route::get('fraud/filter',         [MerchantPanelFraudController::class, 'filter'])->name('merchant-panel.fraud.filter');
                        Route::post('fraud/check',         [MerchantPanelFraudController::class, 'check'])->name('merchant-panel.fraud.check');
                        //reports
                        Route::get('reports/total-summery',            [MerchantPanelReportsController::class, 'TotalSummeryReports'])->name('merchant.total.summery');
                        Route::get('reports/total-summery-filter',     [MerchantPanelReportsController::class, 'TotalSummeryReportsFilter'])->name('merchant.parcel.filter.total.summery');
                        //pickup request
                        Route::prefix('pickup-request')->name('merchant.panel.pickup.request.')->group(function () {
                            Route::post('regular',                      [MerchantPanelPickupRequestController::class, 'regularStore'])->name('regular.store');
                            Route::post('express',                      [MerchantPanelPickupRequestController::class, 'expressStore'])->name('express.store');
                        });
                        Route::prefix('invoice')->name('merchant.panel.invoice.')->group(function () {
                            Route::get('/',                             [InvoiceController::class, 'index'])->name('index');
                            Route::get('/{invoice_id}',                  [InvoiceController::class, 'InvoiceDetails'])->name('details');
                            Route::get('/pdf/{merchant_id}/{invoice_id}', [MerchantInvoiceController::class, 'InvoicePdf'])->name('pdf');
                            Route::get('/csv/{merchant_id}/{invoice_id}', [MerchantInvoiceController::class, 'InvoiceCSV'])->name('csv');
                            Route::get('/journal/{merchant_id}/{invoice_id}', [MerchantInvoiceController::class, 'InvoiceJournal'])->name('journal');
                        });
                        //erchant online payment  received setup
                        Route::get('/settings/online-payment-setup',                            [MerchantOnlinePaymentSetupController::class, 'index'])->name('merchant.online.payment.setup.index');
                        Route::put('/settings/online-payment-setup/update/{paymentmethod}',     [MerchantOnlinePaymentSetupController::class, 'paymentReceivedSetupUpdate'])->name('merchant.online.payment.setup.update');
                        Route::get('online-payment-received-list',                              [MerchantOnlinePaymentSetupController::class, 'onlinePaymentReceivedList'])->name('merchant.online.payment.list');
                        //online payment module
                        Route::get('/payment/received',                            [OnlinePaymentController::class, 'merchantPaymentReceived'])->name('online.payment.received');
                        Route::prefix('online-payment')->name('online.payment.')->group(function () {
                            //stripe payment gateway
                            Route::get('/',                                     [OnlinePaymentController::class, 'index'])->name('index');
                            Route::get('/stripe',                               [OnlinePaymentController::class, 'stripe'])->name('stripe');
                            Route::post('/stripe/post',                         [OnlinePaymentController::class, 'stripePost'])->name('stripe.post');
                            //paypal payment gateway
                            Route::get('paypal-index',                         [OnlinePaymentController::class, 'paypalIndex'])->name('paypal.index');
                            Route::post('paypal-payment',                      [OnlinePaymentController::class, 'paypalpayment'])->name('paypal');
                            //ssl commerz
                            Route::get('/sslcommerz',                          [OnlinePaymentController::class, 'sslcommerzIndex'])->name('sslcommerz.index');
                            Route::get('/aamarpay',                            [OnlinePaymentController::class, 'aamarpayIndex'])->name('aamarpay.index');
                        });


                        //My wallet and recharge
                        Route::prefix('my-wallet')
                            ->controller(WalletController::class)
                            ->name('merchant-panel.my.wallet.')
                            ->group(function () {
                                Route::get('/',                 'index')->name('index');
                                Route::get('/recharge',         'recharge')->name('recharge');
                                Route::post('/recharge-add',    'rechargeAdd')->name('recharge.add');
                                Route::post('/recharge-status', 'rechargeStatus')->name('recharge.status');
                            });
                    });
                    // SSLCOMMERZ Start
                    Route::post('/pay-via-ajax',              [SslCommerzPaymentController::class, 'payViaAjax']);
                    Route::post('/success',                   [SslCommerzPaymentController::class, 'success']);
                    Route::post('/fail',                      [SslCommerzPaymentController::class, 'fail']);
                    Route::post('/cancel',                    [SslCommerzPaymentController::class, 'cancel']);
                    Route::post('/ipn',                       [SslCommerzPaymentController::class, 'ipn']);
                    //skrill payment start
                    Route::get('skrill',                      [SkrillController::class, 'index'])->name('skrill.index');
                    Route::get('skrill-make-payment',         [SkrillController::class, 'makePayment'])->name('skrill.make.payment');
                    Route::get('payment-completed',           [SkrillController::class, 'paymentCompleted'])->name('skrill.payment.completed');
                    Route::get('payment-cancelled',           [SkrillController::class, 'PaymentCancelled']);
                    //bkash payment
                    Route::get('/online-payment/bkash',       [BkashController::class, 'index'])->name('online.payment.bkash.index');
                    Route::get('bkash/redirect',              [BkashController::class, 'bkashRedirect'])->name('bkash.redirect');
                    Route::get('bkash/execute',               [BkashController::class, 'bkashExecute'])->name('bkash.execute');
                    //amarpay
                    Route::get('/aamarpay-payment',           [AamarpayController::class, 'payment'])->name('aamarpay.payment');
                    Route::post('/aamarpay-success',          [AamarpayController::class, 'success'])->name('aamarpay.payment.success');
                    Route::post('/aamarpay-fail',             [AamarpayController::class, 'fail'])->name('aamarpay.payment.fail');
                });
                // Theme Pages
                Route::get('/dashboard-finance', function () {
                    return view('theme.dashboard-finance');
                })->name('dashboard.finance');
                Route::get('/dashboard-influencer', function () {
                    return view('theme.dashboard-influencer');
                })->name('dashboard.influencer');
                Route::get('/dashboard-sales', function () {
                    return view('theme.dashboard-sales');
                })->name('dashboard.sales');
                Route::get('/ecommerce-product-checkout', function () {
                    return view('theme.ecommerce-product-checkout');
                })->name('ecommerce.product.checkout');
                Route::get('/ecommerce-product-single', function () {
                    return view('theme.ecommerce-product-single');
                })->name('ecommerce.product.single');
                Route::get('/ecommerce-product', function () {
                    return view('theme.ecommerce-product');
                })->name('ecommerce.product');
                Route::get('/influencer-finder', function () {
                    return view('theme.influencer-finder');
                })->name('influencer.finder');
                Route::get('/influencer-profile', function () {
                    return view('theme.influencer-profile');
                })->name('influencer.profile');
                // FCM Token
            });

            Route::get('/deliveryMan/parcel/map/{id}/{lat}/{long}/{status}',           [MapParcelController::class, 'parcelMap']);
        });
    endif;

    Route::group(['middleware' => 'auth'], function () {
        Route::post('/store-token', [WebNotificationController::class, 'store'])->name('notification-store.token');
    });
});

/*
| FedaPay (chantier 3).
| Le webhook est PUBLIC : FedaPay ne porte aucun jeton. Il se protege par
| la signature X-FEDAPAY-SIGNATURE, verifiee dans le controleur. Il est
| hors du groupe tenant : l'appel vient de FedaPay, sans sous-domaine.
| Exclu de la protection CSRF (voir VerifyCsrfToken::$except).
*/
Route::post('fedapay/webhook',   [\App\Http\Controllers\Payment\FedaPayController::class, 'webhook'])->name('fedapay.webhook')->withoutMiddleware(['web']);
Route::get('fedapay/callback',   [\App\Http\Controllers\Payment\FedaPayController::class, 'callback'])->name('fedapay.callback');

/*
| Chantier 4 — relevé de règlement en PDF par lien signé temporaire.
| L'app mobile obtient le lien via l'API (jeton Sanctum), puis l'ouvre dans
| le navigateur : la signature remplace la session. Hors du groupe tenant,
| comme le rappel FedaPay ; la société vient de la facture, pas de l'hôte.
*/
Route::get('invoice/statement/{invoice}/pdf', [\App\Http\Controllers\Backend\MerchantInvoiceController::class, 'signedPdf'])->name('invoice.statement.pdf')->middleware('signed');
