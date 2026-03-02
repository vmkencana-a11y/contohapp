<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show user dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        
        return view('user.dashboard', [
            'user' => $user,
            'stats' => [
                'referral_count' => $user->referrals()->where('status', 'active')->count(),
                'kyc_status' => $user->kyc?->status?->label() ?? 'Belum Submit',
            ],
        ]);
    }
}
