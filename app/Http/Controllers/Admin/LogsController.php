<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Logs\AdminActivityLog;
use App\Models\Logs\SecurityEventLog;
use App\Models\Logs\UserLoginLog;
use App\Models\Logs\UserStatusLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogsController extends Controller
{
    /**
     * Display logs dashboard.
     */
    public function index(Request $request): View
    {
        $allowedTabs = ['security', 'login', 'admin', 'status'];
        $tab = in_array($request->query('tab'), $allowedTabs) ? $request->query('tab') : 'security';
        
        $data = [];
        
        switch ($tab) {
            case 'login':
                $data = UserLoginLog::with('user')
                    ->orderBy('created_at', 'desc')
                    ->paginate(20)
                    ->withQueryString();
                break;

            case 'admin':
                $data = AdminActivityLog::with('admin')
                    ->orderBy('created_at', 'desc')
                    ->paginate(20)
                    ->withQueryString();
                break;

            case 'status':
                $data = UserStatusLog::with(['user', 'admin'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(20)
                    ->withQueryString();
                break;

            case 'security':
            default:
                $data = SecurityEventLog::orderBy('created_at', 'desc')
                    ->paginate(20)
                    ->withQueryString();
                $tab = 'security'; // Ensure default matches
                break;
        }

        return view('admin.logs.index', [
            'tab' => $tab,
            'logs' => $data,
        ]);
    }

    /**
     * Show log detail (optional, for json metadata).
     * For now, we display metadata in the table or a modal.
     */
}
