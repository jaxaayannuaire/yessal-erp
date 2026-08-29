<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
	
	'wave' => [
		'api_url' => env('WAVE_API_URL', 'https://api.wave.com/v1'),
		'api_key' => env('WAVE_API_KEY'),
		'success_url' => env('WAVE_SUCCESS_URL'),
		'error_url' => env('WAVE_ERROR_URL'),
		'webhook_secret' => env('WAVE_WEBHOOK_SECRET'),
	],
	
	'wave_balance' => [
		'api_key' => env('WAVE_BALANCE_API_KEY'),
		'signing_secret' => env('WAVE_BALANCE_SIGNING_SECRET'),
	],

];
