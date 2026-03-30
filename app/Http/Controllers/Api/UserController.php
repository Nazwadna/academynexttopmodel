<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Models\Talent;
use App\Models\FeaturedTalent;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    public function edit()
    {
        $user = auth()->user();
        return view('editprofile', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email,' . $user->id_user . ',id_user',
            'number_phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6|confirmed',
        ]);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->number_phone = $request->number_phone;
        if ($request->password) {
            $user->password = bcrypt($request->password);
        }
        $user->save();
        return redirect()->route('profile.edit')->with('success', 'Profile updated!');
    }

    public function destroy(Request $request)
    {
        $user = auth()->user();
        auth()->logout();
        $user->delete();
        return redirect('/')->with('success', 'Account deleted!');
    }

    public function adminHome()
    {
        $user = auth()->user();
        if (!$user || $user->role !== 'admin') {
            return redirect('/');
        }
        
        // Clean up inactive visitors
        \DB::table('visitor')
            ->where('last_activity', '<', now()->subMinutes(30))
            ->update(['is_online' => 0]);
        
        // Hitung user non-admin yang sedang online
        $visitorCount = \DB::table('visitor')
            ->join('users', 'visitor.user_id', '=', 'users.id_user')
            ->where('visitor.is_online', 1)
            ->where('users.role', '!=', 'admin')
            ->count();
        
        // Hitung jumlah model dari tabel models
        $modelCount = \DB::table('models')->count();
        
        // Hitung jumlah portfolio dari tabel portfolios
        $portfolioCount = \DB::table('portfolios')->count();
        
        // Hitung jumlah user dengan role client
        $clientCount = \DB::table('users')->where('role', 'client')->count();
        
        // Ambil semua user untuk tabel profile, join dengan visitor untuk status online dan last_activity
        $users = \DB::table('users')
            ->leftJoin('visitor', function($join) {
                $join->on('users.id_user', '=', 'visitor.user_id')
                    ->where('visitor.is_online', 1);
            })
            ->select('users.*', 'visitor.last_activity as visitor_last_activity', 'visitor.is_online')
            ->get();
        
        $models = Talent::all();
        $featured = FeaturedTalent::orderBy('order')->get();
        
        // Get recent activities
        $recentActivities = $this->getRecentActivities();
        
        // Get system status
        $systemStatus = $this->getSystemStatus();
        
        return view('adminhome', compact(
            'user', 
            'visitorCount', 
            'modelCount', 
            'portfolioCount',
            'clientCount',
            'users', 
            'models', 
            'featured',
            'recentActivities',
            'systemStatus'
        ));
    }

    // Tambahkan method untuk redirect setelah login
    public function redirectAfterLogin()
    {
        $user = auth()->user();
        if ($user && $user->role === 'admin') {
            return redirect()->route('admin.home');
        }
        return redirect('/');
    }

    // Portofolio: admin ke portofolio.blade.php, user ke portofolioasli.blade.php
    public function portofolio()
    {
        $user = auth()->user();
        $models = \App\Models\Talent::all();
        if ($user && $user->role === 'admin') {
            $selected = $models->first();
            $portfolio = $selected ? \App\Models\Portfolio::where('id_model', $selected->id_model)->get() : collect();
            $portfolioSlots = [];
            foreach (range(1,10) as $slot) {
                $portfolioSlots[$slot] = $portfolio->firstWhere('description', 'slot_' . $slot);
            }
            $career = $selected ? \App\Models\Career::where('id_model', $selected->id_model)->get() : collect();
            $careerSlots = [];
            foreach (range(1,6) as $slot) {
                $careerSlots[$slot] = $career->get($slot-1);
            }
            $award = $selected ? \App\Models\Award::where('id_model', $selected->id_model)->get() : collect();
            $awardSlots = [];
            foreach (range(1,6) as $slot) {
                $awardSlots[$slot] = $award->get($slot-1);
            }
            return view('portofolio', compact('models', 'portfolioSlots', 'selected', 'careerSlots', 'awardSlots'));
        } else {
            $selected = $models->first();
            $portfolio = $selected ? \App\Models\Portfolio::where('id_model', $selected->id_model)->get() : collect();
            $portfolioSlots = [];
            foreach (range(1,10) as $slot) {
                $portfolioSlots[$slot] = $portfolio->firstWhere('description', 'slot_' . $slot);
            }
            return view('portofolioasli', compact('models', 'portfolioSlots', 'selected'));
        }
    }

    public function getRecentActivities()
    {
        $activities = [];

        try {
            // 1. Recent online users
            $recentVisitors = \DB::table('visitor')
                ->join('users', 'visitor.user_id', '=', 'users.id_user')
                ->where('visitor.last_activity', '>=', now()->subHours(24))
                ->where('users.role', '!=', 'admin')
                ->orderBy('visitor.last_activity', 'desc')
                ->limit(3)
                ->get();

            foreach ($recentVisitors as $visitor) {
                $activities[] = [
                    'type' => 'user_activity',
                    'message' => $visitor->name . ' ' . (__('messages.admin_logged_in') ?: 'logged in'),
                    'time' => \Carbon\Carbon::parse($visitor->last_activity)->diffForHumans(),
                    'color' => 'green',
                    'icon' => 'fa-sign-in-alt'
                ];
            }

            // 2. Recent portfolio uploads
            $recentPortfolios = \DB::table('portfolios')
                ->orderBy('id_portfolios', 'desc')
                ->limit(3)
                ->get();

            foreach ($recentPortfolios as $portfolio) {
                $activities[] = [
                    'type' => 'portfolio',
                    'message' => $portfolio->nama_model . ' ' . (__('messages.admin_uploaded_portfolio') ?: 'uploaded portfolio'),
                    'time' => __('messages.admin_recently') ?: 'Recently',
                    'color' => 'blue',
                    'icon' => 'fa-image'
                ];
            }

            // 3. System status
            $onlineUsers = \DB::table('visitor')
                ->join('users', 'visitor.user_id', '=', 'users.id_user')
                ->where('visitor.is_online', 1)
                ->where('users.role', '!=', 'admin')
                ->count();

            if ($onlineUsers > 0) {
                $activities[] = [
                    'type' => 'system',
                    'message' => $onlineUsers . ' ' . (__('messages.admin_users_online') ?: 'users currently online'),
                    'time' => __('messages.admin_now') ?: 'Now',
                    'color' => 'yellow',
                    'icon' => 'fa-users'
                ];
            }

        } catch (\Exception $e) {
            $activities = [
                [
                    'type' => 'system',
                    'message' => __('messages.admin_system_running') ?: 'System is running',
                    'time' => __('messages.admin_now') ?: 'Now',
                    'color' => 'green',
                    'icon' => 'fa-check-circle'
                ]
            ];
        }

        return array_slice($activities, 0, 5);
    }

    public function getSystemStatus()
    {
        try {
            \DB::connection()->getPdo();
            $dbStatus = 'Online';
            $dbColor = 'green';
        } catch (\Exception $e) {
            $dbStatus = 'Offline';
            $dbColor = 'red';
        }

        $storagePath = storage_path('app/public');
        $fileCount = 0;
        if (is_dir($storagePath)) {
            $fileCount = count(glob($storagePath . '/*'));
        }
        
        $storageStatus = $fileCount > 0 ? 'Healthy' : 'Empty';
        $storageColor = $fileCount > 0 ? 'green' : 'yellow';

        try {
            $lastActivity = \DB::table('visitor')
                ->orderBy('last_activity', 'desc')
                ->first();
            
            $lastBackup = $lastActivity ? \Carbon\Carbon::parse($lastActivity->last_activity)->format('Y-m-d H:i') : 'Never';
        } catch (\Exception $e) {
            $lastBackup = 'Never';
        }

        return [
            'database' => [
                'status' => $dbStatus,
                'color' => $dbColor
            ],
            'storage' => [
                'status' => $storageStatus,
                'color' => $storageColor
            ],
            'last_backup' => $lastBackup
        ];
    }
}
