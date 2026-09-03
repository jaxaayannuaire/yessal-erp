<?php

use App\Http\Middleware\EnsureEntitlement;
use App\Http\Middleware\EnsureSubscriptionActive;
use App\Http\Middleware\ResolveOrganizationContext;
use App\Http\Middleware\CheckPermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => null);

        $middleware->alias([
            'subscription' => EnsureSubscriptionActive::class,
            'entitlement' => EnsureEntitlement::class,
			'organization.context' => ResolveOrganizationContext::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'platform.admin' => \App\Http\Middleware\EnsurePlatformAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) =>
                $request->is('api/*') || $request->expectsJson(),
        );
    })
	->withSchedule(function (Schedule $schedule): void {
    $schedule->command('subscriptions:process-lifecycle')
        ->dailyAt('01:00');
	})
    ->create();
