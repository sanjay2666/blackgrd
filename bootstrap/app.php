<?php

use App\Http\Middleware\AuditMutation;
use App\Http\Middleware\EnforceFrontendPagePermission;
use App\Http\Middleware\ResolveOrganizationContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'organization' => ResolveOrganizationContext::class,
            'frontend-page' => EnforceFrontendPagePermission::class,
            'audit' => AuditMutation::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            return $request->is('admin') || $request->is('admin/*')
                ? route('admin.login')
                : route('login');
        });

        $middleware->redirectUsersTo(function (Request $request) {
            return $request->is('admin') || $request->is('admin/*')
                ? route('admin.dashboard')
                : route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
