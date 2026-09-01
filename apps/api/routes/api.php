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
use App\Http\Controllers\Api\V1\Caisse\PaymentController as CaissePaymentController;;
use App\Http\Controllers\Api\V1\Caisse\StockController;
use App\Http\Controllers\Api\V1\Caisse\SyncController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

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

        Route::post(
            '/register',
            [AuthController::class, 'register']
        );

        Route::post(
            '/login',
            [AuthController::class, 'login']
        );

        Route::middleware('auth:sanctum')->group(function () {

            Route::get(
                '/me',
                [AuthController::class, 'me']
            );

            Route::post(
                '/logout',
                [AuthController::class, 'logout']
            );
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
    | Routes protégées par authentification
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

		Route::middleware('organization.context')->get(
			'/organization/entitlements',
			[EntitlementController::class, 'index']
		);

		Route::middleware([
			'organization.context',
		])->prefix('caisse')->group(function () {

			Route::apiResource('shops', ShopController::class)
				->only([
					'index',
					'store',
					'show',
					'update',
				]);

			Route::apiResource('terminals', TerminalController::class)
				->only([
					'index',
					'store',
					'show',
					'update',
				]);

			Route::apiResource('devices', DeviceController::class)
				->only([
					'index',
					'store',
					'show',
					'update',
				]);

			Route::get(
					'devices/{device}/activity',
					[DeviceController::class, 'activity']
				)->name('devices.activity');

				Route::post(
					'devices/{device}/revoke',
					[DeviceController::class, 'revoke']
				)->name('devices.revoke');

				Route::post(
					'devices/{device}/activate',
					[DeviceController::class, 'activate']
				)->name('devices.activate');


			Route::get('cash-sessions', [CashSessionController::class, 'index'])
				->name('cash-sessions.index');

			Route::post('cash-sessions/open', [CashSessionController::class, 'store'])
				->name('cash-sessions.open');

			Route::get('cash-sessions/{cashSession}', [CashSessionController::class, 'show'])
				->name('cash-sessions.show');

			Route::post('cash-sessions/{cashSession}/close', [CashSessionController::class, 'close'])
				->name('cash-sessions.close');

			Route::get('sales', [SaleController::class, 'index'])
            ->name('sales.index');

			Route::post('sales', [SaleController::class, 'store'])
				->name('sales.store');

			Route::get('sales/{sale}', [SaleController::class, 'show'])
				->name('sales.show');

			Route::post('sales/{sale}/finalize', [SaleController::class, 'finalize'])
				->name('sales.finalize');

			Route::get(
				'sales/{sale}/payments',
				[CaissePaymentController::class, 'index']
			)->name('sales.payments.index');

			Route::post(
				'sales/{sale}/payments/cash',
				[CaissePaymentController::class, 'payCash']
			)->name('sales.payments.cash');

			Route::get('stock', [StockController::class, 'index'])
				->name('stock.index');

			Route::post('stock/adjustments', [StockController::class, 'adjust'])
				->name('stock.adjust');

			Route::post('sync/push', [SyncController::class, 'push'])
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
        | Paiements
        |--------------------------------------------------------------------------
        */

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


    /*
    |--------------------------------------------------------------------------
    | Wave Balance
    |--------------------------------------------------------------------------
    |
    | Cette route reste hors auth:sanctum comme dans ta configuration actuelle.
    |
    */

    Route::get(
        'payments/wave/balance',
        [PaymentController::class, 'waveBalance']
    );
});
