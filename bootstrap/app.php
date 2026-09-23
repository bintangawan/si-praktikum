<?php

use App\Http\Middleware\EnsureAccountApproved;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\ProtectArchivedCourse;
use App\Http\Middleware\RoleCheck;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', SetLocale::class);
        $middleware->alias([
            'password.changed' => EnsurePasswordChanged::class,
            'role' => RoleCheck::class,
            'course.archive' => ProtectArchivedCourse::class,
            'account.approved' => EnsureAccountApproved::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
