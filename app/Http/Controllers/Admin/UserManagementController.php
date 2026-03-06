<?php

namespace App\Http\Controllers\Admin;

use App\Actions\User\BanUserAction;
use App\Actions\User\ReactivateUserAction;
use App\Actions\User\SuspendUserAction;
use App\Enums\UserStatusEnum;
use App\Http\Controllers\Admin\Concerns\ResolvesCurrentAdmin;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    use ResolvesCurrentAdmin;

    public function __construct(
        private SuspendUserAction $suspendAction,
        private BanUserAction $banAction,
        private ReactivateUserAction $reactivateAction
    ) {
    }

    /**
     * List all users.
     */
    public function index(Request $request): View
    {
        $query = User::with('kyc:user_id,status');

        // Filter by status
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Search by email hash or name
        if ($search = $request->query('search')) {
            $emailHash = hash('sha256', strtolower($search));
            $query->where('email_hash', $emailHash);
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.users.index', [
            'users' => $users,
            'statuses' => UserStatusEnum::cases(),
            'currentStatus' => $status,
        ]);
    }

    /**
     * Show user detail.
     */
    public function show(Request $request, User $user): View
    {
        // Load small relations directly
        $user->load(['kyc', 'referrer.referrer']);

        // Paginate large relations separately to prevent memory issues
        $referrals = $user->referrals()
            ->with('user:id,name,status')
            ->orderBy('referred_at', 'desc')
            ->paginate(10, ['*'], 'referrals_page');

        $statusLogs = $user->statusLogs()
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'status_page');

        $loginLogs = $user->loginLogs()
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'login_page');

        return view('admin.users.show', [
            'user' => $user,
            'referrals' => $referrals,
            'statusLogs' => $statusLogs,
            'loginLogs' => $loginLogs,
        ]);
    }

    /**
     * Suspend user.
     */
    public function suspend(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Alasan penangguhan wajib diisi.',
        ]);

        try {
            $this->suspendAction->execute($user, $this->currentAdminId($request), $validated['reason']);

            return $this->successResponse('User berhasil ditangguhkan.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ban user permanently.
     */
    public function ban(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Alasan pemblokiran wajib diisi.',
        ]);

        try {
            $this->banAction->execute($user, $this->currentAdminId($request), $validated['reason']);

            return $this->successResponse('User berhasil diblokir secara permanen.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Reactivate suspended user.
     */
    public function reactivate(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $reason = $request->input('reason', 'Reactivated by admin');

        try {
            $this->reactivateAction->execute($user, $this->currentAdminId($request), $reason);

            return $this->successResponse('User berhasil diaktifkan kembali.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    private function successResponse(string $message): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return back()->with('success', $message);
    }

    private function errorResponse(string $message): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }
        return back()->withErrors(['error' => $message]);
    }
}
