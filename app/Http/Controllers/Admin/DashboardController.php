<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use App\Models\UserKyc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show admin dashboard with stats.
     */
    public function index(Request $request): View
    {
        // Cache stats for 60 seconds to reduce database load
        $stats = Cache::remember('admin.dashboard.stats', 60, function () {
            // Combine user status counts into a single query
            $userStats = User::selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended,
                SUM(CASE WHEN status = 'banned' THEN 1 ELSE 0 END) as banned
            ")->first();

            return [
                'total_users' => (int) $userStats->total,
                'active_users' => (int) $userStats->active,
                'suspended_users' => (int) $userStats->suspended,
                'banned_users' => (int) $userStats->banned,
                'pending_kyc' => UserKyc::where('status', 'pending')->count(),
                'total_admins' => Admin::count(),
            ];
        });

        $recentUsers = User::orderBy('created_at', 'desc')
            ->take(5)
            ->get(['id', 'name', 'email', 'email_hash', 'status', 'created_at']);

        $pendingKyc = UserKyc::where('status', 'pending')
            ->with('user:id,name')
            ->orderBy('created_at', 'asc')
            ->take(5)
            ->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'pendingKyc' => $pendingKyc,
        ]);
    }
}

