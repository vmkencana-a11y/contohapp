<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralController extends Controller
{
    /**
     * Show referral page with stats and downlines.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        
        $referrals = $user->referrals()
            ->with('user:id,name,status,created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        // Optimize: combine 3 count queries into 1
        $statsQuery = $user->referrals()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            ")
            ->first();
        
        $stats = [
            'total' => (int) $statsQuery->total,
            'active' => (int) $statsQuery->active,
            'cancelled' => (int) $statsQuery->cancelled,
        ];
        
        return view('user.referral', [
            'user' => $user,
            'referrals' => $referrals,
            'stats' => $stats,
        ]);
    }
}

