<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KycStatusEnum;
use App\Http\Controllers\Admin\Concerns\ResolvesCurrentAdmin;
use App\Http\Controllers\Controller;
use App\Models\Logs\KycFileAccessLog;
use App\Models\UserKyc;
use App\Services\KycEncryptionService;
use App\Services\KycStorageService;
use App\Services\LoggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\KycStatusUpdated;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class KycReviewController extends Controller
{
    use ResolvesCurrentAdmin;

    public function __construct(
        private LoggingService $logger,
        private KycEncryptionService $encryptionService,
        private KycStorageService $storageService
    ) {}

    /**
     * List all KYC submissions.
     */
    public function index(Request $request): View
    {
        $query = UserKyc::with('user:id,name,status');

        // Filter by status
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $kycList = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.kyc.index', [
            'kycList' => $kycList,
            'statuses' => KycStatusEnum::cases(),
            'currentStatus' => $status,
        ]);
    }

    /**
     * Show KYC detail for review.
     */
    public function show(UserKyc $kyc): View
    {
        // Check breach flag
        if ($kyc->breach_flag) {
            abort(403, 'Akses file KYC ini diblokir karena investigasi keamanan.');
        }

        $kyc->load('user', 'verifier');

        // Mark as under review if pending
        if ($kyc->isPending()) {
            $kyc->submitForReview();
        }

        return view('admin.kyc.show', [
            'kyc' => $kyc,
        ]);
    }

    /**
     * Serve private KYC images securely with decryption.
     */
    public function serveImage(UserKyc $kyc, string $type): Response
    {
        $admin = $this->currentAdmin();

        // Check breach flag
        if ($kyc->breach_flag) {
            abort(403, 'Akses file KYC ini diblokir karena investigasi keamanan.');
        }

        // Determine path and key based on type
        [$path, $encryptedKey] = match ($type) {
            'id_card' => [$kyc->id_card_path, $kyc->encrypted_id_card_key],
            'selfie' => [$kyc->selfie_path, $kyc->encrypted_selfie_key],
            'left_side' => [$kyc->left_side_path, $kyc->encrypted_left_side_key],
            'right_side' => [$kyc->right_side_path, $kyc->encrypted_right_side_key],
            default => [null, null],
        };

        if (!$path) {
            abort(404, 'Image not found');
        }

        $disk = $this->resolveKycImageDisk($kyc, $path);
        if (!$disk) {
            abort(404, 'Image not found');
        }

        // Log access
        KycFileAccessLog::logAccess(
            $kyc->id,
            $admin->id,
            $type,
            'view',
            $admin->role ?? 'admin'
        );

        // Check if file is encrypted (has encrypted key)
        if ($encryptedKey) {
            // Decrypt file
            try {
                $encryptedContent = $disk->get($path);
                $decryptedContent = $this->encryptionService->decryptFromStorage(
                    $encryptedContent,
                    $encryptedKey
                );

                // Log decrypt action
                KycFileAccessLog::logAccess(
                    $kyc->id,
                    $admin->id,
                    $type,
                    'decrypt',
                    $admin->role ?? 'admin'
                );

                return response($decryptedContent)
                    ->header('Content-Type', 'image/jpeg')
                    ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
                    ->header('Pragma', 'no-cache');

            } catch (\Exception $e) {
                report($e);
                abort(500, 'Gagal mendekripsi file');
            }
        }

        // Legacy: unencrypted file (backward compatibility)
        $content = $disk->get($path);
        return response($content)
            ->header('Content-Type', 'image/jpeg')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Resolve disk for a KYC image path with backward-compatible fallbacks.
     */
    private function resolveKycImageDisk(UserKyc $kyc, string $path): ?\Illuminate\Contracts\Filesystem\Filesystem
    {
        $availableDisks = array_keys((array) config('filesystems.disks', []));
        $metadataDisk = is_array($kyc->metadata ?? null) ? ($kyc->metadata['storage_disk'] ?? null) : null;

        $candidates = array_values(array_unique(array_filter([
            $metadataDisk,
            $this->storageService->getDiskName(),
            'private',
            's3_kyc',
        ])));

        foreach ($candidates as $diskName) {
            if (!in_array($diskName, $availableDisks, true)) {
                continue;
            }

            try {
                $disk = Storage::disk($diskName);

                // Prefer fileExists() to avoid directory existence checks on some S3-compatible providers.
                $exists = method_exists($disk, 'fileExists')
                    ? $disk->fileExists($path)
                    : $disk->exists($path);

                if ($exists) {
                    return $disk;
                }
            } catch (\Throwable $e) {
                // Ignore disk-level existence errors and continue fallback candidates.
                continue;
            }
        }

        return null;
    }

    /**
     * Approve KYC.
     */
    public function approve(UserKyc $kyc): JsonResponse|RedirectResponse
    {
        if ($kyc->isVerified()) {
            return $this->errorResponse('KYC sudah disetujui sebelumnya.');
        }

        if ($kyc->breach_flag) {
            return $this->errorResponse('KYC tidak dapat diproses karena investigasi keamanan.');
        }

        $admin = $this->currentAdmin();
        $kyc->approve($admin->id);

        $this->logger->logAdminActivity(
            $admin->id,
            'kyc.approve',
            'UserKyc',
            (string) $kyc->id
        );

        // Send email notification
        Mail::to($kyc->user->email)->send(new KycStatusUpdated($kyc));

        return $this->successResponse('KYC berhasil disetujui.');
    }

    /**
     * Reject KYC.
     */
    public function reject(Request $request, UserKyc $kyc): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'Alasan penolakan wajib diisi.',
        ]);

        if ($kyc->isVerified()) {
            return $this->errorResponse('KYC yang sudah disetujui tidak dapat ditolak.');
        }

        if ($kyc->breach_flag) {
            return $this->errorResponse('KYC tidak dapat diproses karena investigasi keamanan.');
        }

        $admin = $this->currentAdmin($request);
        $kyc->reject($admin->id, $validated['reason']);

        $this->logger->logAdminActivity(
            $admin->id,
            'kyc.reject',
            'UserKyc',
            (string) $kyc->id,
            $validated['reason']
        );

        // Send email notification
        Mail::to($kyc->user->email)->send(new KycStatusUpdated($kyc));

        return $this->successResponse('KYC ditolak.');
    }

    /**
     * Flag KYC for breach investigation.
     */
    public function flagBreach(UserKyc $kyc): JsonResponse|RedirectResponse
    {
        $admin = $this->currentAdmin();
        
        $kyc->update(['breach_flag' => true]);

        $this->logger->logSecurityEvent(
            'kyc_breach_flagged',
            (string) $admin->id,
            'high',
            ['kyc_id' => $kyc->id, 'user_id' => $kyc->user_id]
        );

        return $this->successResponse('KYC ditandai untuk investigasi keamanan.');
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
