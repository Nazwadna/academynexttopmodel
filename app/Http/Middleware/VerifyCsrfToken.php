<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * URI yang dikecualikan dari verifikasi CSRF.
     *
     * @var array<int, string>
     */
    protected $except = [
        'chatbot/chat',
        'chatbot/analyze',
        'chatbot/train',
        'chatbot/*',
    ];
}
