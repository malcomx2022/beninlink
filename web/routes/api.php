<?php

use App\Http\Controllers\Api\V10\AccountTransactionController;
use App\Http\Controllers\Api\V10\AnalyticsController;
use App\Http\Controllers\Api\V10\DashboardController;
use App\Http\Controllers\Api\V10\DeliveryManIncomeExpenseController;
use App\Http\Controllers\Api\V10\DeliveryManParcelController;
use App\Http\Controllers\Api\V10\FraudController;
use App\Http\Controllers\Api\V10\HubController;
use App\Http\Controllers\Api\V10\NewsOfferController;
use App\Http\Controllers\Api\V10\ParcelController;
use App\Http\Controllers\Api\V10\CustomsController;
use App\Http\Controllers\Api\V10\PaymentAccountController;
use App\Http\Controllers\Api\V10\PaymentRequestController;
use App\Http\Controllers\Api\V10\PushNotificationController;
use App\Http\Controllers\Api\V10\SettingsController;
use App\Http\Controllers\Api\V10\ShopsController;
use App\Http\Controllers\Api\V10\StatementsController;
use App\Http\Controllers\Api\V10\SupportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Payment\FedaPayController;
use App\Http\Controllers\Api\V10\WalletController;
use App\Http\Controllers\Api\V10\NotificationController;
use App\Http\Controllers\Api\V10\AuthController;
use App\Http\Controllers\Api\V10\DeliverymanController;
use App\Http\Controllers\Api\V10\GeneralSettingCotroller;
use App\Http\Controllers\Api\V10\InvoiceController;
use App\Http\Controllers\Api\V10\ReportController;
use App\Http\Controllers\InstallerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v10')->group(function() {

    Route::middleware(['CheckApiKey'])->group(function () {

        // all apis goes here
        Route::post('/register',                                        [AuthController::class, 'register']);
        Route::post('/signin',                                          [AuthController::class, 'signin']);
        Route::post('/deliveryman/login',                               [AuthController::class, 'deliveryManLogin']);
        Route::post('/otp-verification',                                [AuthController::class, 'otpVerification']);
        Route::post('/resend-otp',                                      [AuthController::class, 'resendOTP']);
        Route::post('/password/email',                                  [AuthController::class, 'sendPasswordResetLinkEmail'])->middleware('throttle:5,1');
        Route::post('/password/reset',                                  [AuthController::class, 'resetPassword']);
        Route::get('/hub',                                              [HubController::class, 'index']);
        //general settings api
        Route::get('/general-settings',                                 [GeneralSettingCotroller::class, 'index']);
        Route::get('/all-currencies',                                   [GeneralSettingCotroller::class, 'currencies']);

        Route::group(['middleware'=> ['auth:sanctum']], function () {
            // Routes communes aux deux apps (marchand et livreur) : session,
            // profil, mot de passe, push. Tout le reste est cloisonné par
            // `userType` (S5) — voir App\Http\Middleware\UserTypeMiddleware.
            Route::get('/refresh',                                      [AuthController::class, 'refresh']);
            Route::get('/profile',                                      [AuthController::class, 'profile']);
            Route::post('/profile/update',                              [AuthController::class,'profileUpdate']);
            // Notifications poussées : l'app dépose le jeton de son appareil,
            // le serveur le rattache au compte authentifié (D11).
            Route::post('push/register',                                [PushNotificationController::class, 'register']);
            Route::post('push/forget',                                  [PushNotificationController::class, 'forget']);
            // Topics FCM du socle (apps Flutter dépréciées) — API arrêtée côté Google.
            Route::post('fcm-subscribe',                                [PushNotificationController::class, 'fcmSubscribe']);
            Route::post('fcm-unsubscribe',                              [PushNotificationController::class, 'fcmUnsubscribe']);

            Route::put('/update-password',                              [AuthController::class,'updatePassword']);
            Route::post('/sign-out',                                    [AuthController::class, 'logout']);


            // S5 — espace MARCHAND : un jeton livreur reçoit 403.
            Route::middleware('userType:merchant')->group(function () {
                Route::get('/dashboard',                                    [DashboardController::class, 'index']);
                Route::get('/dashboard/filter',                             [DashboardController::class, 'filter']);
                Route::get('/settings/cod-charges',                         [SettingsController::class,'codCharges']);
                Route::get('/settings/delivery-charges',                    [SettingsController::class,'deliveryCharges']);


                Route::get('shops/index',                                   [ShopsController::class,'index']);
                Route::post('shops/store',                                  [ShopsController::class,'store']);
                Route::get('shops/edit/{id}',                               [ShopsController::class,'edit']);
                Route::put('shops/update/{id}',                             [ShopsController::class,'update']);
                Route::delete('shops/delete/{id}',                          [ShopsController::class,'delete']);


                Route::get('/payment-accounts/index',                       [PaymentAccountController::class,'index']);
                Route::post('/payment-account/store',                       [PaymentAccountController::class,'store']);
                Route::get('/payment-account/edit/{id}',                    [PaymentAccountController::class,'edit']);
                Route::put('/payment-account/update',                       [PaymentAccountController::class,'update']);
                Route::delete('/payment-account/delete/{id}',               [PaymentAccountController::class,'delete']);


                Route::get('/account-transaction/index',                    [AccountTransactionController::class,'index']);
                Route::post('/account-transaction/filter',                  [AccountTransactionController::class,'filter']);


                Route::get('/statements/index',                             [StatementsController::class,'index']);
                Route::post('/statements/filter',                           [StatementsController::class,'filter']);


                Route::get('payment-request/index',                         [PaymentRequestController::class,'index']);
                Route::get('payment-request/create',                        [PaymentRequestController::class,'create']);
                Route::post('payment-request/store',                        [PaymentRequestController::class,'store']);
                Route::get('payment-request/edit/{id}',                     [PaymentRequestController::class,'edit']);
                Route::put('payment-request/update/{id}',                   [PaymentRequestController::class,'update']);
                Route::delete('payment-request/delete/{id}',                [PaymentRequestController::class,'delete']);


                Route::get('fraud/index',                                   [FraudController::class,'index']);
                Route::post('fraud/store',                                  [FraudController::class,'store']);
                Route::get('fraud/edit/{id}',                               [FraudController::class,'edit']);
                Route::put('fraud/update/{id}',                             [FraudController::class,'update']);
                Route::delete('fraud/delete/{id}',                          [FraudController::class,'destroy']);
                Route::post('fraud/check',                                  [FraudController::class,'check']);

                Route::get('news-offer/index',                              [NewsOfferController::class,'index']);

                // Fil de notifications du marchand (statuts colis, recharges,
                // relevés, douane, messages de l'administration, retraits).
                Route::get('notifications/index',                           [NotificationController::class,'index']);
                Route::get('notifications/unread-count',                    [NotificationController::class,'unreadCount']);
                Route::put('notifications/read-all',                        [NotificationController::class,'readAll']);
                Route::put('notifications/{id}/read',                       [NotificationController::class,'read']);

                Route::get('support/index',                                 [SupportController::class,'index']);
                Route::get('support/create',                                [SupportController::class,'create']);
                Route::post('support/store',                                [SupportController::class,'store']);
                Route::get('support/edit/{id}',                             [SupportController::class,'edit']);
                Route::put('support/update/{id}',                           [SupportController::class,'update']);
                Route::delete('support/delete/{id}',                        [SupportController::class,'destroy']);
                Route::get('support/view/{id}',                             [SupportController::class,'view']);
                Route::post('support/reply',                                [SupportController::class,'supportReply']);

                Route::get('parcel/index',                                  [ParcelController::class,'index']);
                Route::get('parcel/create',                                 [ParcelController::class,'create']);
                Route::post('parcel/store',                                 [ParcelController::class,'store']);
                Route::post('parcel/quote',                                 [ParcelController::class,'quote']);

                // Chantier 5 — alertes douanieres. Le controle « ce colis passe-t-il »
                // est rendu par parcel/quote, que l'ecran de creation appelle deja.
                Route::get('customs/reference',                              [CustomsController::class,'reference']);
                Route::get('customs/alerts',                                 [CustomsController::class,'alerts']);
                Route::put('customs/alerts/{id}/resolve',                    [CustomsController::class,'resolve']);
                Route::get('parcel/details/{id}',                           [ParcelController::class,'details']);
                Route::get('parcel/edit/{id}',                              [ParcelController::class,'edit']);
                Route::put('parcel/update/{id}',                            [ParcelController::class,'update']);
                Route::get('parcel/logs/{id}',                              [ParcelController::class,'logs']);
                Route::get('parcel/filter',                                 [ParcelController::class,'filter']);
                Route::get('parcel/{id}/status/{statusId}',                 [ParcelController::class,'statusUpdate']);
                Route::delete('parcel/delete/{id}',                         [ParcelController::class,'destroy']);


                // update v1.2
                Route::get('/dashboard/balance-details',                    [DashboardController::class, 'balanceDetails']);
                Route::get('/dashboard/available-parcels',                  [DashboardController::class, 'availableParcels']);
                Route::get('/invoice-list/index',                           [InvoiceController::class,   'invoiceLists']);
                Route::get('/invoice-details/{id}',                         [InvoiceController::class,   'invoiceDetails']);
                // Chantier 4 : lien signé (15 min) vers le relevé en PDF.
                Route::get('/invoice-pdf-link/{id}',                        [InvoiceController::class,   'pdfLink']);
                Route::get('parcel/all/status',                             [ParcelController::class,    'parcelAllStatus']);
                Route::get('status-wise/parcel/list/{status}',              [ParcelController::class,    'statusWiseParcelList']);
                Route::get('analytics' ,                                    [AnalyticsController::class, 'index']);
                Route::post('statement-reports',                            [ReportController::class,    'TotalSummeryStatementReports']);

                // FedaPay — recharge du wallet marchand (chantier 3).
                // Sous auth:sanctum : seul un marchand connecté initie une recharge.
                // Le webhook, lui, est PUBLIC (FedaPay n'a pas de jeton) et se
                // protège par la signature — voir routes/web.php.
                Route::post('fedapay/initiate',                              [FedaPayController::class, 'initiate']);
                Route::get('fedapay/status/{reference}',                     [FedaPayController::class, 'status']);
                // Mouvements du porte-monnaie prépayé (recharges FedaPay, crédits
                // manuels, dépenses). Le solde, lui, arrive avec `/profile`.
                Route::get('wallet/history',                                 [WalletController::class, 'history']);
            });

            // S5 — espace LIVREUR : un jeton marchand reçoit 403.
            Route::middleware('userType:deliveryman')->group(function () {
                //deliveryman
                Route::get('deliveryman/parcel/index',                      [DeliveryManParcelController::class,'index']);
                Route::get('deliveryman/parcel/details/{id}',               [DeliveryManParcelController::class,'details']);
                Route::post('deliveryman/parcel/delivered/{id}',            [DeliveryManParcelController::class,'parcelDelivered']);
                Route::post('deliveryman/parcel/partial-delivered/{id}',    [DeliveryManParcelController::class,'parcelPartialDelivered']);

                Route::get('deliveryman/income-expense',                    [DeliveryManIncomeExpenseController::class,'deliverymanIncomeExpense']);


                Route::get('deliveryman/dashboard',                          [DeliverymanController::class,'dashboard']);
                Route::get('deliveryman/profile',                           [DeliverymanController::class,'profile']);
                Route::get('deliveryman/payment-logs',                      [DeliverymanController::class,'paymentLogs']);
                Route::get('deliveryman/parcel-payment-logs',               [DeliverymanController::class,'parcelPaymentLogs']);

                Route::get('deliveryman/parcel-status',                     [DeliverymanController::class,'parcelStatus']);
                Route::post('deliveryman/parcel-status-update',              [DeliverymanController::class,'parcelStatusUpdate']);

                // S4 — remontée de position du livreur. Était HORS du groupe
                // auth:sanctum : protégée par la seule clé API statique et publique,
                // n'importe qui pouvait donc écrire la position d'un colis.
                // Rentrée dans le groupe authentifié.
                Route::post('deliveryman/parcel-location-update',            [DeliverymanController::class, 'parcelLocationUpdate']);
            });

        });

    });
    // Chantier 7 — contrat de l'API. Public, sans clé : c'est un document.
    Route::get('openapi.json',                                        [\App\Http\Controllers\Api\V10\OpenApiController::class, 'spec'])->name('openapi.spec');

    Route::get('customer/installation',                               [InstallerController::class,'customerInstallation']);
    //frontend api
    Route::get('parcel/tracking/{tracking_id}',                         [ParcelController::class,'parcelTrackingLogs']);
    Route::post('/contact-us',                                          [ParcelController::class,'ContactUs']);
    Route::post('/subscribe',                                           [ParcelController::class,'subscribe']);

    /*
    | S10 — `/delivery-charges` était PUBLIQUE. Sans utilisateur authentifié,
    | `settings()` retombe sur la société 1 (bloc A) et l'API n'initialise aucune
    | tenancy (bloc I) : la route servait donc les tarifs de la société
    | « plateforme » à n'importe quel appelant, quel que soit le sous-domaine.
    |
    | Placée sous `auth:sanctum` : le locataire se résout alors par
    | `Auth::user()->company_id` et chacun obtient SES tarifs. Un appelant qui
    | s'y fiait reçoit désormais un 401 — préférable à des prix silencieusement
    | faux. Aucun appelant n'a été trouvé dans les vues, le JS ni les apps ;
    | `/settings/delivery-charges` couvre déjà le besoin authentifié.
    */
    Route::middleware('auth:sanctum')->get('/delivery-charges',         [ParcelController::class, 'DeliveryCharges']);
});


