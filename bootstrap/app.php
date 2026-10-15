<?php

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
        // alias "teacher" => only users with role=teacher may pass
        $middleware->alias([
            'student' => \App\Http\Middleware\StudentMiddleware::class,
            'teacher' => \App\Http\Middleware\TeacherMiddleware::class,
        ]);

        // guests trying to open protected pages go to the student login
        $middleware->redirectGuestsTo(fn () => route('login'));
        // logged-in users opening /login go to their dashboard
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();