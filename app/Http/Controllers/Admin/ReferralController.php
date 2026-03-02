<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserReferral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReferralController extends Controller
{
    /**
     * Referral Dashboard.
     */
    public function index(): View
    {
        // Statistics
        $totalReferrals = UserReferral::count();
        $activeReferrals = UserReferral::where('status', 'active')->count();
        $cancelledReferrals = UserReferral::where('status', 'cancelled')->count();

        // Top Referrers
        $topReferrers = UserReferral::select('referrer_id', DB::raw('count(*) as count'))
            ->where('status', 'active')
            ->groupBy('referrer_id')
            ->orderByDesc('count')
            ->limit(5)
            ->with('referrer')
            ->get();

        // Recent Referrals List
        $referrals = UserReferral::with(['referrer', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.referrals.index', [
            'stats' => [
                'total' => $totalReferrals,
                'active' => $activeReferrals,
                'cancelled' => $cancelledReferrals,
            ],
            'topReferrers' => $topReferrers,
            'referrals' => $referrals,
        ]);
    }
}
