<?php

use App\Http\Controllers\Backend\CurrencyController;
use App\Http\Controllers\Backend\DatabaseBackupController;
use App\Http\Controllers\Backend\DepartmentController;
use App\Http\Controllers\Backend\DesignationController;
use App\Http\Controllers\Backend\FrontWeb\BlogController;
use App\Http\Controllers\Backend\FrontWeb\FaqController;
use App\Http\Controllers\Backend\FrontWeb\PageController;
use App\Http\Controllers\Backend\FrontWeb\PartnerController;
use App\Http\Controllers\Backend\FrontWeb\SectionController;
use App\Http\Controllers\Backend\FrontWeb\ServiceController;
use App\Http\Controllers\Backend\FrontWeb\SocialLinkController;
use App\Http\Controllers\Backend\FrontWeb\WhyCourierController;
use App\Http\Controllers\Backend\GeneralSettingsController;
use App\Http\Controllers\Backend\PayoutSetupController;
use App\Http\Controllers\Backend\ProfileController;
use App\Http\Controllers\Backend\RoleController;
use App\Http\Controllers\Backend\SalaryGenerateController;
use App\Http\Controllers\Backend\SmsSettingsController;
use App\Http\Controllers\Backend\Superadmin\CompanyController;
use App\Http\Controllers\Backend\Superadmin\PlanController;
use App\Http\Controllers\Backend\SupportController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\DashbordController;
use App\Http\Controllers\Frontend\FrontendController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Database\Models\Domain;

Route::middleware(['XSS', 'IsInstalled'])->group(function () {
    
    $domain = false; 
    if(Config::get('app.app_installed') == 'yes'  && Schema::hasTable('domains')):
        $domain = in_array(request()->getHost(), Domain::pluck('domain')->toArray());
    endif;

    if (!$domain):

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

        Route::get('company/sign-up',                   [CompanyController::class, 'signUp'])->name('company.sign-up');
        Route::post('company/sign-up/store',            [CompanyController::class, 'signUpStore'])->name('company.sign-up.store');
        Route::get('company/otp-verification-form',     [CompanyController::class, 'otpVerificationForm'])->name('company.otp-verification-form');
        Route::post('company/resend-otp',               [CompanyController::class, 'resendOTP'])->name('company.resend-otp');
        Route::post('company/otp-verification',         [CompanyController::class, 'otpVerification'])->name('company.otp-verification');

        Route::group(['middleware' => 'auth'], function () {

            Route::get('/dashboard',                   [DashbordController::class, 'index'])->name('dashboard.index');
            Route::get('/subscription',                [PlanController::class, 'subscription'])->name('subscription.index');
            Route::get('/admin/subscription/history',  [PlanController::class, 'subscriptionHistory'])->name('admin.subscription.history');

            Route::prefix('super-admin')->middleware('panel:super-admin')->group(function () {
                Route::prefix('plan')
                    ->controller(PlanController::class)
                    ->name('plan.')
                    ->group(function () {
                        Route::get('/',                'index')->name('index')->middleware('hasPermission:plans_read');
                        Route::get('/create',           'create')->name('create')->middleware('hasPermission:plans_create');
                        Route::post('/store',           'store')->name('store')->middleware('hasPermission:plans_create');
                        Route::get('/edit/{id}',        'edit')->name('edit')->middleware('hasPermission:plans_update');
                        Route::put('/update',            'update')->name('update')->middleware('hasPermission:plans_update');
                        Route::delete('/delete/{id}',     'delete')->name('delete')->middleware('hasPermission:plans_delete');
                        Route::get('/modules/{plan_id}',   'modulesView')->name('modules.view')->middleware('hasPermission:plans_read');
                    });
                Route::get('/subscription/history', [PlanController::class, 'subscriptionHistory'])->name('subscription.history');
                // Chantier 6 — reporting SaaS (MRR, ARR, churn, LTV, CAC). Super-admin seul, lecture seule.
                Route::get('/reporting', [\App\Http\Controllers\Backend\Superadmin\ReportingController::class, 'index'])->name('saas.reporting');
                Route::prefix('company')
                    ->controller(CompanyController::class)
                    ->name('company.')
                    ->group(function () {
                        Route::get('/',               'index')->name('index')->middleware('hasPermission:company_read');
                        Route::get('/create',         'create')->name('create')->middleware('hasPermission:company_create');
                        Route::post('/store',         'store')->name('store')->middleware('hasPermission:company_create');
                        Route::get('/edit/{id}',      'edit')->name('edit')->middleware('hasPermission:company_update');
                        Route::put('/update',         'update')->name('update')->middleware('hasPermission:company_update');
                        Route::delete('/delete/{id}', 'delete')->name('delete')->middleware('hasPermission:company_delete');
                        Route::get('/subscription/switch/{id}', 'switchSubscription')->name('subscription.switch')->middleware('hasPermission:company_subscribe');
                        Route::post('/subscription/switch/store', 'switchSubscriptionStore')->name('subscription.switch.store')->middleware('hasPermission:company_subscribe');
                    });
            });


            Route::group(['prefix' => 'admin', 'middleware' => 'panel:back-office'], function () {

                Route::get('subscribe',                            [SalaryGenerateController::class, 'subscribe'])->name('subscribe.index');

                // Support route
                Route::get('support/index',         [SupportController::class, 'index'])->name('support.index')->middleware('hasPermission:support_read');
                Route::get('support/create',        [SupportController::class, 'create'])->name('support.add')->middleware('hasPermission:support_create');
                Route::post('support/store',        [SupportController::class, 'store'])->name('support.store')->middleware('hasPermission:support_create');
                Route::get('support/edit/{id}',     [SupportController::class, 'edit'])->name('support.edit')->middleware('hasPermission:support_update');
                Route::put('support/update',        [SupportController::class, 'update'])->name('support.update')->middleware('hasPermission:support_update');
                Route::delete('support/delete/{id}', [SupportController::class, 'destroy'])->name('support.delete')->middleware('hasPermission:support_delete');
                // S36 — meme droit que son index, comme cote locataire. `support_read` figure bien
                // dans le jeu du super-administrateur : personne ne perd l'acces.
                Route::get('support/view/{id}',     [SupportController::class, 'view'])->name('support.view')->middleware('hasPermission:support_read');
                Route::post('support/reply',        [SupportController::class, 'supportReply'])->name('support.reply')->middleware('hasPermission:support_reply');
                Route::get('support/status-update/{id}',  [SupportController::class, 'statusUpdate'])->name('support.status.update')->middleware('hasPermission:support_status_update');


                Route::get('roles',                                             [RoleController::class, 'index'])->name('roles.index')->middleware('hasPermission:role_read');
                Route::get('roles/create',                                      [RoleController::class, 'create'])->name('roles.create')->middleware('hasPermission:role_create');
                Route::post('roles/store',                                      [RoleController::class, 'store'])->name('roles.store')->middleware('hasPermission:role_create');
                Route::get('roles/edit/{id}',                                   [RoleController::class, 'edit'])->name('roles.edit')->middleware('hasPermission:role_update');
                Route::put('roles/update',                                      [RoleController::class, 'update'])->name('roles.update')->middleware('hasPermission:role_update');
                Route::delete('role/delete/{id}',                               [RoleController::class, 'destroy'])->name('role.delete')->middleware('hasPermission:role_delete');

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


                Route::get('users',          [UserController::class, 'index'])->name('users.index')->middleware('hasPermission:user_read');
                Route::get('users/filter',   [UserController::class, 'filter'])->name('users.filter')->middleware('hasPermission:user_read');
                Route::get('users/create',   [UserController::class, 'create'])->name('users.create')->middleware('hasPermission:user_create');
                Route::post('users/store',   [UserController::class, 'store'])->name('users.store')->middleware('hasPermission:user_create');
                Route::get('users/edit/{id}', [UserController::class, 'edit'])->name('users.edit')->middleware('hasPermission:user_update');
                Route::put('users/update',   [UserController::class, 'update'])->name('users.update')->middleware('hasPermission:user_update');
                Route::get('users/permissions/{id}',  [UserController::class, 'permission'])->name('users.permission')->middleware('hasPermission:permission_update');
                Route::put('users/permissions/update', [UserController::class, 'permissionsUpdate'])->name('users.permissions.update')->middleware('hasPermission:permission_update');
                Route::delete('user/delete/{id}',     [UserController::class, 'destroy'])->name('user.delete')->middleware('hasPermission:user_delete');

                // User profile
                Route::get('profile/{id}',                  [ProfileController::class, 'view'])->name('profile.index');
                Route::get('profile/update/{id}',           [ProfileController::class, 'create'])->name('profile.edit');
                Route::get('profile/change-password/{id}',  [ProfileController::class, 'changePassword'])->name('password.change');
                Route::put('profile/update/{id}',           [ProfileController::class, 'update'])->name('profile.update');
                Route::put('profile/update-password/{id}',  [ProfileController::class, 'updatePassword'])->name('profile.password.update');

                // General settings
                Route::get('general-settings/index',        [GeneralSettingsController::class, 'index'])->name('general-settings.index')->middleware('hasPermission:general_settings_read');
                Route::put('general-settings/update',       [GeneralSettingsController::class, 'update'])->name('general-settings.update')->middleware('hasPermission:general_settings_update');


                //payout setup settings
                Route::get('/settings/pay-out/setup',                            [PayoutSetupController::class, 'index'])->name('payout.setup.settings.index')->middleware('hasPermission:payout_setup_settings_read');
                Route::put('/settings/pay-out/setup/update/{paymentmethod}',     [PayoutSetupController::class, 'PayoutSetupUpdate'])->name('payout.setup.settings.update')->middleware('hasPermission:payout_setup_settings_update');

                Route::get('sms-settings/index',            [SmsSettingsController::class, 'index'])->name('sms-settings.index')->middleware('hasPermission:sms_settings_read');
                // S35 — `edit/{id}` et `status` retirees : methodes inexistantes sur
                // `SmsSettingsController`, comme leurs jumelles du cote locataire.
                Route::put('sms-settings/update/{id}',      [SmsSettingsController::class, 'update'])->name('sms-settings.update')->middleware('hasPermission:sms_settings_update');

                //currency settings
                Route::get('currency',                      [CurrencyController::class, 'index'])->name('currency.index')->middleware('hasPermission:currency_read');
                Route::get('currency/create',               [CurrencyController::class, 'create'])->name('currency.create')->middleware('hasPermission:currency_create');
                Route::post('currency/store',               [CurrencyController::class, 'store'])->name('currency.store')->middleware('hasPermission:currency_create');
                Route::get('currency/edit/{id}',            [CurrencyController::class, 'edit'])->name('currency.edit')->middleware('hasPermission:currency_update');
                Route::put('currency/update',               [CurrencyController::class, 'update'])->name('currency.update')->middleware('hasPermission:currency_update');
                Route::delete('currency/delete/{id}',       [CurrencyController::class, 'delete'])->name('currency.delete')->middleware('hasPermission:currency_delete');

                // database backup
                Route::get('/database-backup',             [DatabaseBackupController::class, 'index'])->name('database.backup.index')->middleware('hasPermission:database_backup_read');
                Route::get('database-backup/download',     [DatabaseBackupController::class, 'databaseBackup'])->name('database.backup.download')->middleware('hasPermission:database_backup_read');


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
        });
    endif;
});
