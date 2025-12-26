<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Student, User, Course, Batch, Invoice, Payment};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function systemMetrics()
    {
        $metrics = [
            'total_users' => User::count(),
            'active_users' => User::whereNotNull('email_verified_at')->count(),
            'total_students' => Student::count(),
            'active_students' => Student::where('status', 'active')->count(),
            'total_courses' => Course::count(),
            'total_batches' => Batch::count(),
            'server_uptime' => $this->getServerUptime(),
            'database_size' => $this->getDatabaseSize(),
        ];
        
        return response()->json($metrics);
    }
    
    public function userActivity()
    {
        $activity = [
            'daily_logins' => $this->getDailyLogins(),
            'weekly_registrations' => $this->getWeeklyRegistrations(),
            'monthly_activity' => $this->getMonthlyActivity(),
            'user_roles_distribution' => $this->getUserRolesDistribution(),
        ];
        
        return response()->json($activity);
    }
    
    private function getServerUptime()
    {
        if (function_exists('exec')) {
            $uptime = exec('uptime');
            return $uptime ?: 'Unknown';
        }
        return 'Unknown';
    }
    
    private function getDatabaseSize()
    {
        try {
            $size = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS DB_Size FROM information_schema.tables WHERE table_schema = ?", [config('database.connections.mysql.database')]);
            return $size[0]->DB_Size . ' MB';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }
    
    private function getDailyLogins()
    {
        return User::whereDate('last_login_at', today())->count();
    }
    
    private function getWeeklyRegistrations()
    {
        return User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
    }
    
    private function getMonthlyActivity()
    {
        return User::whereMonth('updated_at', now()->month)->count();
    }
    
    private function getUserRolesDistribution()
    {
        return User::with('roles')->get()->groupBy(function($user) {
            return $user->roles->first()->name ?? 'No Role';
        })->map->count();
    }
}