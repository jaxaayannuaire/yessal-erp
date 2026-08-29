<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\EntitlementController;
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