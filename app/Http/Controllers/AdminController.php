<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Talent;
use App\Models\Portfolio; // Tambahkan ini
use App\Models\FeaturedTalent;
use App\Models\OurTalent;
use App\Models\PopularTalent;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        if (!$user || $user->role !== 'admin') {
            return redirect('/');
        }

        // Clean up inactive visitors
        DB::table('visitor')
            ->where('last_activity', '<', now()->subMinutes(30))
            ->update(['is_online' => 0]);

        // Get visitor count
        $visitorCount = DB::table('visitor')
            ->join('users', 'visitor.user_id', '=', 'users.id_user')
            ->where('visitor.is_online', 1)
            ->where('users.role', '!=', 'admin')
            ->count();

        // Get model count
        $modelCount = Talent::count();

        // Get portfolio count using Portfolio model
        $portfolioCount = Portfolio::count();

        // Get client count (users with role = client)
        $clientCount = User::where('role', 'client')->count();

        // Get users with online status
        $users = DB::table('users')
            ->leftJoin('visitor', function($join) {
                $join->on('users.id_user', '=', 'visitor.user_id')
                    ->where('visitor.is_online', 1);
            })
            ->select('users.*', 'visitor.last_activity as visitor_last_activity', 'visitor.is_online')
            ->get();

        // Get all models
        $models = Talent::all();

        // Get featured models
        $featured = FeaturedTalent::orderBy('order')->get();

        // Get recent activities from existing data
        $recentActivities = $this->getRecentActivities();

        // Get system status from existing data
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

    public function getRecentActivities()
    {
        $activities = [];

        try {
            // 1. Recent online users (from visitor table)
            $recentVisitors = DB::table('visitor')
                ->join('users', 'visitor.user_id', '=', 'users.id_user')
                ->where('visitor.last_activity', '>=', now()->subHours(24))
                ->where('users.role', '!=', 'admin')
                ->orderBy('visitor.last_activity', 'desc')
                ->limit(3)
                ->get();

            foreach ($recentVisitors as $visitor) {
                $activities[] = [
                    'type' => 'user_activity',
                    'message' => 'User activity: ' . $visitor->name,
                    'time' => Carbon::parse($visitor->last_activity)->diffForHumans(),
                    'color' => 'green',
                    'icon' => 'fa-sign-in-alt'
                ];
            }

            // 2. Recent portfolio uploads (from portfolios table using Portfolio model)
            $recentPortfolios = Portfolio::orderBy('id_portfolios', 'desc')
                ->limit(3)
                ->get();

            foreach ($recentPortfolios as $portfolio) {
                $activities[] = [
                    'type' => 'portfolio',
                    'message' => 'Portfolio uploaded: ' . $portfolio->nama_model,
                    'time' => 'Recently',
                    'color' => 'blue',
                    'icon' => 'fa-image'
                ];
            }

            // 3. System activities
            $totalUsers = User::where('role', '!=', 'admin')->count();
            $totalPortfolios = Portfolio::count();
            
            if ($totalPortfolios > 0) {
                $activities[] = [
                    'type' => 'system',
                    'message' => 'Total portfolios: ' . $totalPortfolios,
                    'time' => 'Current',
                    'color' => 'purple',
                    'icon' => 'fa-images'
                ];
            }

        } catch (\Exception $e) {
            // Fallback activities if database error
            $activities = [
                [
                    'type' => 'system',
                    'message' => 'System is running',
                    'time' => 'Now',
                    'color' => 'green',
                    'icon' => 'fa-check-circle'
                ]
            ];
        }

        // Sort and take latest 5
        return array_slice($activities, 0, 5);
    }

    public function getSystemStatus()
    {
        try {
            // Check database connection
            DB::connection()->getPdo();
            $dbStatus = 'Online';
            $dbColor = 'green';
        } catch (\Exception $e) {
            $dbStatus = 'Offline';
            $dbColor = 'red';
        }

        // Check storage (count files in storage/app/public)
        $storagePath = storage_path('app/public');
        $fileCount = 0;
        if (is_dir($storagePath)) {
            $fileCount = count(glob($storagePath . '/*'));
        }
        
        $storageStatus = $fileCount > 0 ? 'Healthy' : 'Empty';
        $storageColor = $fileCount > 0 ? 'green' : 'yellow';

        // Get last activity time (use last visitor activity as proxy)
        try {
            $lastActivity = DB::table('visitor')
                ->orderBy('last_activity', 'desc')
                ->first();
            
            $lastBackup = $lastActivity ? Carbon::parse($lastActivity->last_activity)->format('Y-m-d H:i') : 'Never';
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

    public function visitorStats()
    {
        try {
            // Clean up inactive visitors
            DB::table('visitor')
                ->where('last_activity', '<', now()->subMinutes(30))
                ->update(['is_online' => 0]);
            
            // Get online count
            $onlineCount = DB::table('visitor')
                ->join('users', 'visitor.user_id', '=', 'users.id_user')
                ->where('visitor.is_online', 1)
                ->where('users.role', '!=', 'admin')
                ->count();
            
            // Get data for chart (last 7 days)
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
                'onlineCount' => $onlineCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'labels' => [],
                'counts' => [],
                'onlineCount' => 0
            ]);
        }
    }

    public function modelStats()
    {
        try {
            $totalModels = Talent::count();

            $labels = [];
            $counts = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();
                $labels[] = $date;
                $counts[] = $totalModels; // Consistent count for now
            }

            return response()->json([
                'labels' => $labels, 
                'counts' => $counts,
                'totalModels' => $totalModels
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'labels' => [],
                'counts' => [],
                'totalModels' => 0
            ]);
        }
    }

    public function getDashboardStats()
    {
        try {
            $stats = [
                'visitorCount' => DB::table('visitor')
                    ->join('users', 'visitor.user_id', '=', 'users.id_user')
                    ->where('visitor.is_online', 1)
                    ->where('users.role', '!=', 'admin')
                    ->count(),
                'modelCount' => Talent::count(),
                'featuredCount' => FeaturedTalent::count(),
                'popularCount' => PopularTalent::count(),
                'totalUsers' => User::where('role', '!=', 'admin')->count(),
                'activeModels' => Talent::where('status', 'available')->count()
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'visitorCount' => 0,
                'modelCount' => 0,
                'featuredCount' => 0,
                'popularCount' => 0,
                'totalUsers' => 0,
                'activeModels' => 0
            ]);
        }
    }
} 