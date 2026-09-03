<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\EntitlementController;
use App\Http\Controllers\Api\V1\Caisse\ShopController;
use App\Http\Controllers\Api\V1\Caisse\TerminalController;
use App\Http\Controllers\Api\V1\Caisse\DeviceController;
use App\Http\Controllers\Api\V1\Caisse\CashSessionController;
use App\Http\Controllers\Api\V1\Caisse\SaleController;
use App\Http\Controllers\Api\V1\Caisse\PaymentController as CaissePaymentController;
use App\Http\Controllers\Api\V1\Caisse\StockController;
use App\Http\Controllers\Api\V1\Caisse\SyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Health check
    |--------------------------------------------------------------------------
    */

    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'application' => 'Yessal ERP API',
            'version' => 'v1',
        ]);
    });


    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::prefix('auth')->group(function () {

        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {

            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });


    /*
    |--------------------------------------------------------------------------
    | Routes publiques Wave
    |--------------------------------------------------------------------------
    */

    Route::post(
        'payments/wave/webhook',
        [PaymentController::class, 'waveWebhook']
    );

    Route::get(
        '/payments/wave/success',
        function () {
            return response()->json([
                'success' => true,
                'message' => 'Paiement Wave terminé. Le paiement est en cours de confirmation.',
            ]);
        }
    )->name('payments.wave.success');

    Route::get(
        '/payments/wave/error',
        function () {
            return response()->json([
                'success' => false,
                'message' => 'Le paiement Wave a été annulé ou a échoué.',
            ]);
        }
    )->name('payments.wave.error');


    /*
    |--------------------------------------------------------------------------
    | Routes protégées
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Entitlements
        |--------------------------------------------------------------------------
        */

        Route::middleware('organization.context')->get(
            '/organization/entitlements',
            [EntitlementController::class, 'index']
        );


        /*
        |--------------------------------------------------------------------------
        | Caisse
        |--------------------------------------------------------------------------
        */

        Route::middleware('organization.context')
            ->prefix('caisse')
            ->group(function () {

                /*
                |--------------------------------------------------------------------------
                | Boutiques
                |--------------------------------------------------------------------------
                */

                Route::get(
                    'shops',
                    [ShopController::class, 'index']
                )
                    ->middleware('permission:shops.view')
                    ->name('shops.index');

                Route::post(
                    'shops',
                    [ShopController::class, 'store']
                )
                    ->middleware('permission:shops.manage')
                    ->name('shops.store');

                Route::get(
                    'shops/{shop}',
                    [ShopController::class, 'show']
                )
                    ->middleware('permission:shops.view')
                    ->name('shops.show');

                Route::match(
                    ['put', 'patch'],
                    'shops/{shop}',
                    [ShopController::class, 'update']
                )
                    ->middleware('permission:shops.manage')
                    ->name('shops.update');


                /*
                |--------------------------------------------------------------------------
                | Terminaux
                |--------------------------------------------------------------------------
                */

                Route::get(
                    'terminals',
                    [TerminalController::class, 'index']
                )
                    ->middleware('permission:terminals.view')
                    ->name('terminals.index');

                Route::post(
                    'terminals',
                    [TerminalController::class, 'store']
                )
                    ->middleware('permission:terminals.manage')
                    ->name('terminals.store');

                Route::get(
                    'terminals/{terminal}',
                    [TerminalController::class, 'show']
                )
                    ->middleware('permission:terminals.view')
                    ->name('terminals.show');

                Route::match(
                    ['put', 'patch'],
                    'terminals/{terminal}',
                    [TerminalController::class, 'update']
                )
                    ->middleware('permission:terminals.manage')
                    ->name('terminals.update');


                /*
                |--------------------------------------------------------------------------
                | Devices
                |--------------------------------------------------------------------------
                */

                Route::get(
                    'devices',
                    [DeviceController::class, 'index']
                )
                    ->middleware('permission:devices.view')
                    ->name('devices.index');

                Route::post(
                    'devices',
                    [DeviceController::class, 'store']
                )
                    ->middleware('permission:devices.manage')
                    ->name('devices.store');

                Route::get(
                    'devices/{device}',
                    [DeviceController::class, 'show']
                )
                    ->middleware('permission:devices.view')
                    ->name('devices.show');

                Route::match(
                    ['put', 'patch'],
                    'devices/{device}',
                    [DeviceController::class, 'update']
                )
                    ->middleware('permission:devices.manage')
                    ->name('devices.update');

                Route::get(
                    'devices/{device}/activity',
                    [DeviceController::class, 'activity']
                )
                    ->middleware('permission:devices.view')
                    ->name('devices.activity');

                Route::post(
                    'devices/{device}/revoke',
                    [DeviceController::class, 'revoke']
                )
                    ->middleware('permission:devices.manage')
                    ->name('devices.revoke');

                Route::post(
                    'devices/{device}/activate',
                    [DeviceController::class, 'activate']
                )
                    ->middleware('permission:devices.manage')
                    ->name('devices.activate');


                /*
                |--------------------------------------------------------------------------
                | Sessions de caisse
                |--------------------------------------------------------------------------
                */

                Route::get(
                    'cash-sessions',
                    [CashSessionController::class, 'index']
                )
                    ->middleware('permission:cash.view')
                    ->name('cash-sessions.index');

                Route::post(
                    'cash-sessions/open',
                    [CashSessionController::class, 'store']
                )
                    ->middleware('permission:cash.open')
                    ->name('cash-sessions.open');

                Route::get(
                    'cash-sessions/{cashSession}',
                    [CashSessionController::class, 'show']
                )
                    ->middleware('permission:cash.view')
                    ->name('cash-sessions.show');

                Route::post(
                    'cash-sessions/{cashSession}/close',
                    [CashSessionController::class, 'close']
                )
                    ->middleware('permission:cash.close')
                    ->name('cash-sessions.close');


                /*
                |--------------------------------------------------------------------------
                | Ventes
                |--------------------------------------------------------------------------
                */

                Route::get(
                    'sales',
                    [SaleController::class, 'index']
                )
                    ->middleware('permission:sales.view')
                    ->name('sales.index');

                Route::post(
                    'sales',
                    [SaleController::class, 'store']
                )
                    ->middleware('permission:sales.create')
                    ->name('sales.store');

                Route::get(
                    'sales/{sale}',
                    [SaleController::class, 'show']
                )
                    ->middleware('permission:sales.view')
                    ->name('sales.show');

                Route::post(
                    'sales/{sale}/finalize',
                    [SaleController::class, 'finalize']
                )
                    ->middleware('permission:sales.finalize')
                    ->name('sales.finalize');


                /*
                |--------------------------------------------------------------------------
                | Paiements des ventes
                |--------------------------------------------------------------------------
                */

                Route::get(
                    'sales/{sale}/payments',
                    [CaissePaymentController::class, 'index']
                )
                    ->middleware('permission:sales.view')
                    ->name('sales.payments.index');

                Route::post(
                    'sales/{sale}/payments/cash',
                    [CaissePaymentController::class, 'payCash']
                )
                    ->middleware('permission:sales.create')
                    ->name('sales.payments.cash');


                /*
                |--------------------------------------------------------------------------
                | Stock
                |--------------------------------------------------------------------------
                */

                Route::get(
                    'stock',
                    [StockController::class, 'index']
                )
                    ->middleware('permission:stock.view')
                    ->name('stock.index');

                Route::post(
                    'stock/adjustments',
                    [StockController::class, 'adjust']
                )
                    ->middleware('permission:stock.manage')
                    ->name('stock.adjust');


                /*
                |--------------------------------------------------------------------------
                | Synchronisation
                |--------------------------------------------------------------------------
                */

                Route::post(
                    'sync/push',
                    [SyncController::class, 'push']
                )
                    ->middleware('permission:sync.push')
                    ->name('sync.push');
            });


        /*
        |--------------------------------------------------------------------------
        | Organisations
        |--------------------------------------------------------------------------
        */

        Route::post(
            'organizations',
            [OrganizationController::class, 'store']
        );

        Route::middleware('organization.context')->group(function () {

            Route::get(
                'organizations',
                [OrganizationController::class, 'index']
            );

            Route::get(
                'organizations/{organization}',
                [OrganizationController::class, 'show']
            );

            Route::put(
                'organizations/{organization}',
                [OrganizationController::class, 'update']
            );

            Route::patch(
                'organizations/{organization}',
                [OrganizationController::class, 'update']
            );

            Route::delete(
                'organizations/{organization}',
                [OrganizationController::class, 'destroy']
            );
        });


        /*
        |--------------------------------------------------------------------------
        | Plans
        |--------------------------------------------------------------------------
        */

        Route::apiResource(
            'plans',
            PlanController::class
        );


        /*
        |--------------------------------------------------------------------------
        | Abonnements
        |--------------------------------------------------------------------------
        */

        Route::middleware('organization.context')->group(function () {

            Route::apiResource(
                'subscriptions',
                SubscriptionController::class
            );

            Route::post(
                'subscriptions/{subscription}/activate',
                [SubscriptionController::class, 'activate']
            );

            Route::post(
                'subscriptions/{subscription}/cancel',
                [SubscriptionController::class, 'cancel']
            );
        });


        /*
        |--------------------------------------------------------------------------
        | Paiements plateforme
        |--------------------------------------------------------------------------
        */

        Route::middleware('organization.context')->group(function () {

            Route::apiResource(
                'payments',
                PaymentController::class
            )->only([
                'index',
                'store',
                'show',
            ]);

            Route::post(
                'payments/{payment}/confirm',
                [PaymentController::class, 'confirm']
            );

            Route::post(
                'payments/{payment}/initiate',
                [PaymentController::class, 'initiate']
            );
        });
    });


    /*
    |--------------------------------------------------------------------------
    | Wave Balance
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->get(
        'payments/wave/balance',
        [PaymentController::class, 'waveBalance']
    );
});
