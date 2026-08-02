<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\LocaleMiddleware::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);

        // Izinkan POST chatbot tanpa CSRF (admin UI / API gateway / queue trigger)
        $middleware->validateCsrfTokens(except: [
            'chatbot/chat',
            'chatbot/analyze',
            'chatbot/train',
            'chatbot/*',
            'admin/chatbot-clear-logs',
            'admin/chatbot-evaluation/run',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
