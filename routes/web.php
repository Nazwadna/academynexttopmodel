<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TalentController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\GoogleController;
use App\Models\Talent;
use App\Models\FeaturedTalent;
use Illuminate\Http\Request;
use App\Http\Controllers\FeaturedModelsController;
use App\Http\Controllers\OurTalentController;
use App\Http\Controllers\PopularTalentController;
// VisitorLogger middleware untuk halaman utama
Route::middleware([\App\Http\Middleware\VisitorLogger::class, \App\Http\Middleware\CleanupInactiveUsers::class])->group(function () {
    Route::get('/', [FeaturedModelsController::class, 'welcome']);
    Route::get('/register', function () { return view('register'); });
    Route::get('/model', function () { return view('model'); });
    Route::get('/joinacademy', function () { return view('joinacademy'); });
    // Tambahkan route lain yang ingin dicatat visitor-nya di sini
});

// Routes untuk guest (belum login)
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('login');
    })->name('login');
    Route::get('/register', function () {
        return view('register');
    });
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
});

// Route untuk user yang sudah login
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
});


// GET portofolio publik (hanya lihat)
Route::get('/portofolio', [\App\Http\Controllers\Api\UserController::class, 'portofolio'])->name('portofolio');
Route::get('/portofolio/{id_model}', [\App\Http\Controllers\Api\TalentController::class, 'portofolioDetail'])->name('portofolio.detail');

// Aksi-aksi yang butuh login
Route::middleware('auth')->group(function () {
    Route::post('/portofolio/{id_model}/upload/{slot}', [\App\Http\Controllers\Api\TalentController::class, 'uploadPortfolio'])->name('portofolio.upload');
    Route::post('/portofolio/{id_model}/update/{slot}', [\App\Http\Controllers\Api\TalentController::class, 'updatePortfolio'])->name('portofolio.update');
    Route::delete('/portofolio/{id_model}/delete/{slot}', [\App\Http\Controllers\Api\TalentController::class, 'deletePortfolio'])->name('portofolio.delete');

    Route::post('/portofolio/{id_model}/career/upload/{slot}', [\App\Http\Controllers\Api\TalentController::class, 'uploadCareer'])->name('career.upload');
    Route::post('/portofolio/{id_model}/career/update/{slot}', [\App\Http\Controllers\Api\TalentController::class, 'updateCareer'])->name('career.update');
    Route::delete('/portofolio/{id_model}/career/delete/{slot}', [\App\Http\Controllers\Api\TalentController::class, 'deleteCareer'])->name('career.delete');

    Route::post('/portofolio/{id_model}/award/upload/{slot}', [\App\Http\Controllers\Api\TalentController::class, 'uploadAward'])->name('award.upload');
    Route::post('/portofolio/{id_model}/award/update/{slot}', [\App\Http\Controllers\Api\TalentController::class, 'updateAward'])->name('award.update');
    Route::delete('/portofolio/{id_model}/award/delete/{slot}', [\App\Http\Controllers\Api\TalentController::class, 'deleteAward'])->name('award.delete');
});

Route::get('/addmodel', [TalentController::class, 'create'])->name('models.create');
Route::post('/models', [TalentController::class, 'store'])->name('models.store');


// routes/web.php
Route::get('/models', [TalentController::class, 'list'])->name('models.list');
Route::get('/models/{id_model}/edit', [\App\Http\Controllers\Api\TalentController::class, 'edit'])->name('models.edit');
Route::put('/models/{id_model}', [\App\Http\Controllers\Api\TalentController::class, 'update'])->name('models.update');
Route::delete('/models/{id_model}', [\App\Http\Controllers\Api\TalentController::class, 'destroy'])->name('models.destroy');

Route::get('/profile/edit', [\App\Http\Controllers\Api\UserController::class, 'edit'])->name('profile.edit');
Route::post('/profile/update', [\App\Http\Controllers\Api\UserController::class, 'update'])->name('profile.update');
Route::delete('/profile/delete', [\App\Http\Controllers\Api\UserController::class, 'destroy'])->name('profile.delete');
// Middleware admin closure
Route::get('/admin', function() {
    $user = Auth::user();
    if (!$user || $user->role !== 'admin') {
        return redirect('/');
    }
    return app(\App\Http\Controllers\Api\UserController::class)->adminHome();
})->name('admin.home');
Route::get('/api/new-models', [\App\Http\Controllers\Api\TalentController::class, 'newModels'])->name('api.newmodels');



// Endpoint statistik visitor untuk grafik
Route::get('/admin/visitor-stats', function () {
    // Hapus user yang sudah tidak aktif (lebih dari 30 menit)
    DB::table('visitor')
        ->where('last_activity', '<', now()->subMinutes(30))
        ->update(['is_online' => 0]);
    
    // Hitung user non-admin yang sedang online
    $onlineCount = DB::table('visitor')
        ->join('users', 'visitor.user_id', '=', 'users.id_user')
        ->where('visitor.is_online', 1)
        ->where('users.role', '!=', 'admin')
        ->count();
    
    // Data untuk grafik (7 hari terakhir) - hanya user non-admin
    $data = \DB::table('visitor')
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
    'onlineCount' => $onlineCount
]);
});

// Endpoint statistik model untuk grafik
Route::get('/admin/model-stats', function () {
// Hitung total model saat ini
$totalModels = \DB::table('models')->count();

// Data untuk grafik (7 hari terakhir) - menampilkan jumlah model yang konsisten
$labels = [];
$counts = [];
for ($i = 6; $i >= 0; $i--) {
    $date = now()->subDays($i)->toDateString();
    $labels[] = $date;
    // Tampilkan jumlah model yang sama untuk semua tanggal (karena tidak ada timestamp)
    $counts[] = $totalModels;
}

return response()->json([
    'labels' => $labels, 
    'counts' => $counts,
    'totalModels' => $totalModels
]);
});


Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('google.callback');

Route::get('lang/{lang}', function ($lang) {
    if (in_array($lang, ['en', 'id'])) {
        session(['locale' => $lang]);
        app()->setLocale($lang);
        \Log::info('Language switched to: ' . $lang);
        \Log::info('Session locale: ' . session('locale'));
        \Log::info('App locale: ' . app()->getLocale());
    }
    return back();
})->name('lang.switch');

// Routes untuk OTP verification
Route::get('/verify-otp', [AuthController::class, 'showOtpForm'])->name('auth.showOtpForm');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('auth.verifyOtp');
Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->name('auth.resendOtp');


// ROUTE AGAR SEMUA VIEW BISA DIAKSES LANGSUNG (HANYA UNTUK DEVELOPMENT/TESTING)
Route::get('/adminhome', function () { return view('adminhome'); });
Route::get('/editmodel', function () { return view('editmodel'); });
Route::get('/editprofile', function () { return view('editprofile'); });
Route::get('/joinacademy', function () { return view('joinacademy'); });
Route::get('/model', function () { return view('model'); });
Route::get('/navbar', function () { return view('navbar'); });
Route::get('/portofolioasli', function () { return view('portofolioasli'); });
Route::get('/test-lang', function () { return view('test-lang'); });
Route::get('/test-dropdown', function () { return view('test-dropdown'); });
Route::get('/test-indonesia', function () { return view('test-indonesia'); });
Route::get('/test-welcome-dropdown', function () { return view('test-welcome-dropdown'); });

Route::get('/admin/featured-talents', function() {
    $models = Talent::all();
    $featured = FeaturedTalent::orderBy('order')->get();
    return view('adminhome', array_merge(
        compact('models', 'featured'),
        [
            // data lain yang sudah dikirim ke adminhome
            'user' => auth()->user(),
            'visitorCount' => \DB::table('visitor')->join('users', 'visitor.user_id', '=', 'users.id_user')->where('visitor.is_online', 1)->where('users.role', '!=', 'admin')->count(),
            'modelCount' => \DB::table('models')->count(),
            'users' => \DB::table('users')->leftJoin('visitor', function($join) { $join->on('users.id_user', '=', 'visitor.user_id')->where('visitor.is_online', 1); })->select('users.*', 'visitor.last_activity as visitor_last_activity', 'visitor.is_online')->get(),
        ]
    ));
})->name('admin.featured-talents');

Route::post('/admin/featured-talents', [FeaturedModelsController::class, 'saveFeaturedTalents'])
    ->name('admin.featured-talents.save');

    Route::prefix('admin')->group(function () {
        Route::post('/ourtalent/store', [OurTalentController::class, 'store'])->name('admin.ourtalent.store');
    });
    Route::prefix('admin')->group(function () {
        Route::post('/populartalent/store', [PopularTalentController::class, 'store'])->name('admin.populartalent.store');
    });
    





