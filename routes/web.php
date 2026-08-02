<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TalentController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\FeaturedModelsController;
use App\Http\Controllers\OurTalentController;
use App\Http\Controllers\PopularTalentController;
use App\Http\Controllers\ChatbotController;
use App\Models\Talent;
use App\Models\FeaturedTalent;

/*
|--------------------------------------------------------------------------
| Public pages (visitor tracking)
|--------------------------------------------------------------------------
*/
Route::middleware([
    \App\Http\Middleware\VisitorLogger::class,
])->group(function () {
    Route::get('/', [FeaturedModelsController::class, 'welcome']);
    Route::get('/model', function () {
        return view('model');
    });
    Route::get('/joinacademy', function () {
        return view('joinacademy');
    });
});

/*
|--------------------------------------------------------------------------
| Guest auth
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('login');
    })->name('login');

    Route::get('/register', function () {
        return view('register');
    })->name('register');

    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
});

/*
|--------------------------------------------------------------------------
| Portfolio
|--------------------------------------------------------------------------
*/
Route::get('/portofolio', [UserController::class, 'portofolio'])->name('portofolio');
Route::get('/portofolio/{id_model}', [TalentController::class, 'portofolioDetail'])->name('portofolio.detail');

Route::middleware('auth')->group(function () {
    Route::post('/portofolio/{id_model}/upload/{slot}', [TalentController::class, 'uploadPortfolio'])->name('portofolio.upload');
    Route::post('/portofolio/{id_model}/update/{slot}', [TalentController::class, 'updatePortfolio'])->name('portofolio.update');
    Route::delete('/portofolio/{id_model}/delete/{slot}', [TalentController::class, 'deletePortfolio'])->name('portofolio.delete');

    Route::post('/portofolio/{id_model}/career/upload/{slot}', [TalentController::class, 'uploadCareer'])->name('career.upload');
    Route::post('/portofolio/{id_model}/career/update/{slot}', [TalentController::class, 'updateCareer'])->name('career.update');
    Route::delete('/portofolio/{id_model}/career/delete/{slot}', [TalentController::class, 'deleteCareer'])->name('career.delete');

    Route::post('/portofolio/{id_model}/award/upload/{slot}', [TalentController::class, 'uploadAward'])->name('award.upload');
    Route::post('/portofolio/{id_model}/award/update/{slot}', [TalentController::class, 'updateAward'])->name('award.update');
    Route::delete('/portofolio/{id_model}/award/delete/{slot}', [TalentController::class, 'deleteAward'])->name('award.delete');
});

/*
|--------------------------------------------------------------------------
| Models & profile
|--------------------------------------------------------------------------
*/
Route::get('/addmodel', [TalentController::class, 'create'])->name('models.create');
Route::post('/models', [TalentController::class, 'store'])->name('models.store');
Route::get('/models', [TalentController::class, 'list'])->name('models.list');
Route::get('/models/{id_model}/edit', [TalentController::class, 'edit'])->name('models.edit');
Route::put('/models/{id_model}', [TalentController::class, 'update'])->name('models.update');
Route::delete('/models/{id_model}', [TalentController::class, 'destroy'])->name('models.destroy');

Route::get('/profile/edit', [UserController::class, 'edit'])->name('profile.edit');
Route::post('/profile/update', [UserController::class, 'update'])->name('profile.update');
Route::delete('/profile/delete', [UserController::class, 'destroy'])->name('profile.delete');

Route::get('/admin', function () {
    $user = Auth::user();
    if (!$user || $user->role !== 'admin') {
        return redirect('/');
    }

    return app(UserController::class)->adminHome();
})->name('admin.home');

Route::get('/api/new-models', [TalentController::class, 'newModels'])->name('api.newmodels');

/*
|--------------------------------------------------------------------------
| Admin stats
|--------------------------------------------------------------------------
*/
Route::get('/admin/visitor-stats', function () {
    DB::table('visitor')
        ->where('last_activity', '<', now()->subMinutes(30))
        ->update(['is_online' => 0]);

    $onlineCount = DB::table('visitor')
        ->join('users', 'visitor.user_id', '=', 'users.id_user')
        ->where('visitor.is_online', 1)
        ->where('users.role', '!=', 'admin')
        ->count();

    $data = DB::table('visitor')
        ->join('users', 'visitor.user_id', '=', 'users.id_user')
        ->selectRaw('DATE(visitor.visited_at) as date, COUNT(DISTINCT visitor.session_id) as count')
        ->where('visitor.visited_at', '>=', now()->subDays(6)->startOfDay())
        ->where('users.role', '!=', 'admin')
        ->groupBy('date')
        ->orderBy('date')
        ->get();

    $labels = [];
    $counts = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = now()->subDays($i)->toDateString();
        $labels[] = $date;
        $found = $data->firstWhere('date', $date);
        $counts[] = $found ? $found->count : 0;
    }

    return response()->json([
        'labels' => $labels,
        'counts' => $counts,
        'onlineCount' => $onlineCount,
    ]);
});

Route::get('/admin/model-stats', function () {
    $totalModels = DB::table('models')->count();

    $labels = [];
    $counts = [];
    for ($i = 6; $i >= 0; $i--) {
        $labels[] = now()->subDays($i)->toDateString();
        $counts[] = $totalModels;
    }

    return response()->json([
        'labels' => $labels,
        'counts' => $counts,
        'totalModels' => $totalModels,
    ]);
});

/*
|--------------------------------------------------------------------------
| Google OAuth & OTP
|--------------------------------------------------------------------------
*/
Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('google.callback');

Route::post('/otp/send', [OtpController::class, 'sendOtp'])->name('otp.send');
Route::post('/otp/verify', [OtpController::class, 'verifyOtp'])->name('otp.verify');
Route::post('/otp/resend', [OtpController::class, 'resendOtp'])->name('otp.resend');

// Satu set route OTP UI (hindari duplikat /verify-otp)
Route::get('/verify-otp', [AuthController::class, 'showOtpForm'])->name('auth.showOtpForm');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('auth.verifyOtp');
Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->name('auth.resendOtp');

Route::get('lang/{lang}', function ($lang) {
    if (in_array($lang, ['en', 'id'], true)) {
        session(['locale' => $lang]);
        app()->setLocale($lang);
        Log::info('Language switched to: ' . $lang);
    }

    return back();
})->name('lang.switch');

/*
|--------------------------------------------------------------------------
| SEO
|--------------------------------------------------------------------------
*/
Route::get('/sitemap.xml', function () {
    return response()
        ->view('sitemap')
        ->header('Content-Type', 'application/xml; charset=UTF-8');
})->name('sitemap');

Route::get('/robots.txt', function () {
    $content = "User-agent: *\n"
        . "Allow: /\n"
        . "Disallow: /admin/\n"
        . "Disallow: /api/\n"
        . "Sitemap: " . url('/sitemap.xml') . "\n";

    return response($content, 200)
        ->header('Content-Type', 'text/plain; charset=UTF-8');
})->name('robots');

/*
|--------------------------------------------------------------------------
| Admin featured / our / popular talents
|--------------------------------------------------------------------------
*/
Route::get('/admin/featured-talents', function () {
    $models = Talent::all();
    $featured = FeaturedTalent::orderBy('order')->get();

    return view('adminhome', array_merge(
        compact('models', 'featured'),
        [
            'user' => auth()->user(),
            'visitorCount' => DB::table('visitor')
                ->join('users', 'visitor.user_id', '=', 'users.id_user')
                ->where('visitor.is_online', 1)
                ->where('users.role', '!=', 'admin')
                ->count(),
            'modelCount' => DB::table('models')->count(),
            'users' => DB::table('users')
                ->leftJoin('visitor', function ($join) {
                    $join->on('users.id_user', '=', 'visitor.user_id')
                        ->where('visitor.is_online', 1);
                })
                ->select('users.*', 'visitor.last_activity as visitor_last_activity', 'visitor.is_online')
                ->get(),
        ]
    ));
})->name('admin.featured-talents');

Route::post('/admin/featured-talents', [FeaturedModelsController::class, 'saveFeaturedTalents'])
    ->name('admin.featured-talents.save');

Route::prefix('admin')->group(function () {
    Route::post('/ourtalent/store', [OurTalentController::class, 'store'])->name('admin.ourtalent.store');
    Route::post('/populartalent/store', [PopularTalentController::class, 'store'])->name('admin.populartalent.store');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/chatbot-management', function () {
        return view('admin_chatbot_management');
    })->name('admin.chatbot.management');

    Route::redirect('/admin/model-training', '/admin/chatbot-management');

    Route::get('/admin/chatbot-analytics', [ChatbotController::class, 'adminAnalytics'])
        ->name('admin.chatbot.analytics');
    Route::post('/admin/chatbot-clear-logs', [ChatbotController::class, 'clearChatLogs'])
        ->name('admin.chatbot.clear-logs');
    Route::get('/admin/chatbot-report', [ChatbotController::class, 'adminReport'])
        ->name('admin.chatbot.report');
    Route::post('/admin/chatbot-evaluation/run', [ChatbotController::class, 'runEvaluation'])
        ->name('admin.chatbot.evaluation.run');
    Route::get('/admin/chatbot-evaluation-report', [ChatbotController::class, 'evaluationReport'])
        ->name('admin.chatbot.evaluation-report');
    Route::get('/admin/chatbot-evaluation-pdf', [ChatbotController::class, 'evaluationPdf'])
        ->name('admin.chatbot.evaluation-pdf');
    Route::get('/admin/intents/data', [ChatbotController::class, 'adminIntentsData'])
        ->name('admin.intents.data');
    Route::post('/admin/intents', [ChatbotController::class, 'storeAdminIntent'])
        ->name('admin.intents.store');
    Route::delete('/admin/intents/{intent}', [ChatbotController::class, 'deleteAdminIntent'])
        ->name('admin.intents.delete');

    Route::get('/admin/intents', function () {
        return view('admin_intents');
    })->name('admin.intents');
    Route::get('/admin/chat-logs', function () {
        return view('admin_chat_logs');
    })->name('admin.chat-logs');
});

/*
|--------------------------------------------------------------------------
| Chatbot (proxy ke Flask)
|--------------------------------------------------------------------------
*/
Route::get('/chatbot', [ChatbotController::class, 'index'])->name('chatbot.index');
Route::post('/chatbot/chat', [ChatbotController::class, 'chat'])->name('chatbot.chat');
Route::post('/chatbot/analyze', [ChatbotController::class, 'analyze'])->name('chatbot.analyze');
Route::get('/chatbot/health', [ChatbotController::class, 'health'])->name('chatbot.health');
Route::get('/chatbot/intents', [ChatbotController::class, 'intents'])->name('chatbot.intents');
Route::post('/chatbot/train', [ChatbotController::class, 'train'])->name('chatbot.train');
