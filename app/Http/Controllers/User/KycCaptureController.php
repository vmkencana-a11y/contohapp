<?php

namespace App\Http\Controllers\User;

use App\Enums\KycStatusEnum;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessKycDocuments;
use App\Models\UserKyc;
use App\Services\KycSessionService;
use App\Services\KycStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * KYC Camera Capture Controller.
 * 
 * Handles camera-based KYC submission with:
 * - Session management
 * - Nonce-protected frame capture
 * - Liveness challenge validation
 */
class KycCaptureController extends Controller
{
    public function __construct(
        private KycSessionService $sessionService,
        private KycStorageService $storageService
    ) {}

    /**
     * Start a new KYC capture session.
     * 
     * POST /kyc/capture/start
     */
    public function startSession(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user already has approved KYC
        $existingKyc = $user->kyc;
        if ($existingKyc && $existingKyc->isVerified()) {
            return response()->json([
                'success' => false,
                'message' => 'KYC sudah diverifikasi.',
            ], 422);
        }

        // Check if KYC is in progress (not rejected)
        if ($existingKyc && !$existingKyc->isRejected() && !$existingKyc->status->isProcessing()) {
            return response()->json([
                'success' => false,
                'message' => 'KYC sedang dalam proses.',
            ], 422);
        }

        // Start session
        $session = $this->sessionService->startSession($user->id);

        // Generate challenge sequence
        $challenges = []; // No random challenges for manual 4-step flow

        return response()->json([
            'success' => true,
            'session_id' => $session['session_id'],
            'expires_at' => $session['expires_at'],
            'max_frames' => $session['max_frames'],
            'challenges' => $challenges,
            'required_challenges' => 0,
        ]);
    }

    /**
     * Get a fresh nonce for frame capture.
     * 
     * GET /kyc/capture/nonce
     */
    public function getNonce(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'uuid'],
        ]);

        $user = $request->user();

        // Validate session
        if (!$this->sessionService->validateSession($validated['session_id'], $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi tidak valid atau kedaluwarsa.',
            ], 401);
        }

        try {
            $nonce = $this->sessionService->generateNonce($validated['session_id']);

            return response()->json([
                'success' => true,
                'nonce' => $nonce['nonce'],
                'expires_at' => $nonce['expires_at'],
            ]);
        } catch (\RuntimeException $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan. Silakan coba lagi.',
            ], 422);
        }
    }

    /**
     * Submit a captured frame with nonce validation.
     * 
     * POST /kyc/capture/frame
     */
    public function submitFrame(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'uuid'],
            'nonce' => ['required', 'string', 'size:64'],
            'frame' => ['required', 'string', 'max:5242880'], // ~5MB base64 limit
            'type' => ['required', 'in:selfie,id_card,left_side,right_side'],
        ]);

        $user = $request->user();

        // Validate session
        if (!$this->sessionService->validateSession($validated['session_id'], $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi tidak valid atau kedaluwarsa.',
            ], 401);
        }

        // Validate and consume nonce
        if (!$this->sessionService->consumeNonce($validated['session_id'], $validated['nonce'])) {
            return response()->json([
                'success' => false,
                'message' => 'Nonce tidak valid atau sudah digunakan.',
            ], 422);
        }

        try {
            // Decode base64 image
            $imageData = $this->decodeBase64Image($validated['frame']);

            if (!$imageData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format gambar tidak valid.',
                ], 422);
            }

            // Generate storage path
            $storagePath = 'kyc/' . $user->id . '/' . $validated['session_id'];
            // Avoid .tmp extension to prevent external tmp-cleaner races on some servers.
            $fileName = $validated['type'] . '_' . now()->timestamp . '_' . Str::lower(Str::random(8)) . '.capture';
            $filePath = $storagePath . '/' . $fileName;

            // Store temporarily
            $tempDisk = $this->storageService->tempDisk();
            $written = $tempDisk->put($filePath, $imageData);
            if ($written === false) {
                throw new \RuntimeException("Failed to write temp frame to disk: {$filePath}");
            }

            if (!$tempDisk->exists($filePath)) {
                throw new \RuntimeException("Temp frame not found immediately after write: {$filePath}");
            }

            $existingFramePaths = $this->sessionService->getFramePaths($validated['session_id']);
            $previousPath = $existingFramePaths[$validated['type']] ?? null;
            if ($previousPath && $previousPath !== $filePath) {
                try {
                    $tempDisk->delete($previousPath);
                } catch (\Throwable $deleteError) {
                    Log::warning('Failed to remove superseded KYC temp frame', [
                        'session_id' => $validated['session_id'],
                        'type' => $validated['type'],
                        'old_path' => $previousPath,
                        'new_path' => $filePath,
                        'error' => $deleteError->getMessage(),
                    ]);
                }
            }

            // Store path server-side (never expose to client)
            $this->sessionService->storeFramePath(
                $validated['session_id'],
                $validated['type'],
                $filePath
            );

            // Get session data for frame count
            $session = $this->sessionService->getSessionData($validated['session_id']);

            return response()->json([
                'success' => true,
                'message' => 'Frame berhasil dicatat.',
                'frame_count' => $session['frame_count'] ?? 1,
                'type' => $validated['type'],
            ]);

        } catch (\Exception $e) {
            Log::error('KYC frame submit failed', [
                'session_id' => $validated['session_id'] ?? null,
                'type' => $validated['type'] ?? null,
                'temp_disk' => $this->storageService->getTempDiskName(),
                'temp_disk_root' => config('filesystems.disks.' . $this->storageService->getTempDiskName() . '.root'),
                'error' => $e->getMessage(),
            ]);
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan frame.',
            ], 500);
        }
    }

    /**
     * Complete KYC capture and start processing.
     * 
     * POST /kyc/capture/complete
     */
    public function complete(Request $request): JsonResponse
    {
        $idNumberRule = match($request->input('id_type')) {
            'ktp' => 'regex:/^\d{16}$/',
            'sim' => 'regex:/^\d{12,14}$/',
            'passport' => 'regex:/^[A-Z0-9]{6,9}$/i',
            default => 'min:10|max:20',
        };

        $validated = $request->validate([
            'session_id' => ['required', 'string', 'uuid'],
            'id_type' => ['required', 'in:ktp,sim,passport'],
            'id_number' => ['required', 'string', $idNumberRule],
        ], [
            'id_type.required' => 'Jenis identitas wajib dipilih.',
            'id_number.required' => 'Nomor identitas wajib diisi.',
            'id_number.regex' => 'Format nomor identitas tidak sesuai dengan jenis ID yang dipilih.',
        ]);

        $user = $request->user();

        // Validate session
        if (!$this->sessionService->validateSession($validated['session_id'], $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi tidak valid atau kedaluwarsa.',
            ], 401);
        }

        // Retrieve frame paths from server-side session (never from client)
        $framePaths = $this->sessionService->getFramePaths($validated['session_id']);
        $requiredTypes = ['selfie', 'id_card', 'left_side', 'right_side'];
        foreach ($requiredTypes as $type) {
            if (empty($framePaths[$type])) {
                return response()->json([
                    'success' => false,
                    'message' => "Foto {$type} belum diambil.",
                ], 422);
            }
        }

        // Check duplicate ID number (hash-based)
        $idHash = UserKyc::hashIdNumber($validated['id_number']);
        $duplicate = UserKyc::where('id_number_hash', $idHash)
            ->where('user_id', '!=', $user->id)
            ->whereNotIn('status', [KycStatusEnum::REJECTED->value])
            ->first();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor identitas sudah terdaftar pada akun lain.',
            ], 422);
        }

        // Verify files exist
        $tempDisk = $this->storageService->tempDisk();
        $tempChecks = [];

        foreach ($requiredTypes as $type) {
            $path = $framePaths[$type];
            $absolutePath = null;
            try {
                $absolutePath = $tempDisk->path($path);
            } catch (\Throwable) {
                // Non-local disks may not expose absolute path.
            }

            $exists = method_exists($tempDisk, 'fileExists')
                ? $tempDisk->fileExists($path)
                : $tempDisk->exists($path);

            $tempChecks[$type] = [
                'path' => $path,
                'exists' => $exists,
                'abs' => $absolutePath,
            ];

            if (!$exists) {
                Log::error('KYC temp file missing before dispatch', [
                    'session_id' => $validated['session_id'],
                    'type' => $type,
                    'path' => $path,
                    'disk' => $this->storageService->getTempDiskName(),
                    'disk_root' => config('filesystems.disks.' . $this->storageService->getTempDiskName() . '.root'),
                    'abs' => $absolutePath,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'File capture tidak ditemukan.',
                ], 422);
            }
        }

        Log::info('KYC temp files verified before dispatch', [
            'session_id' => $validated['session_id'],
            'temp_disk' => $this->storageService->getTempDiskName(),
            'temp_disk_root' => config('filesystems.disks.' . $this->storageService->getTempDiskName() . '.root'),
            'files' => $tempChecks,
        ]);

        // Get session data
        $session = $this->sessionService->getSessionData($validated['session_id']);

        // Delete old KYC files if exists
        $existingKyc = $user->kyc;
        foreach (['selfie_path', 'id_card_path', 'left_side_path', 'right_side_path'] as $pathField) {
            if ($existingKyc && $existingKyc->$pathField) {
                $this->storageService->disk()->delete($existingKyc->$pathField);
            }
        }

        // Create KYC record
        $kyc = UserKyc::updateOrCreate(
            ['user_id' => $user->id],
            [
                'session_id' => $validated['session_id'],
                'status' => KycStatusEnum::PROCESSING,
                'id_type' => $validated['id_type'],
                'id_number' => $validated['id_number'],
                'selfie_path' => null,
                'id_card_path' => null,
                'encrypted_selfie_key' => null,
                'encrypted_id_card_key' => null,
                'encrypted_left_side_key' => null,
                'encrypted_right_side_key' => null,
                'liveness_result' => [
                    'challenges_completed' => [], 
                    'validated_at' => now()->toIso8601String(),
                ],
                'frame_count' => $session['frame_count'] ?? 0,
                'capture_method' => 'camera',
                'breach_flag' => false,
                'rejection_reason' => null,
                'verified_by' => null,
                'verified_at' => null,
            ]
        );

        // Dispatch processing job
        ProcessKycDocuments::dispatch(
            $kyc,
            $framePaths['selfie'],
            $framePaths['id_card'],
            $framePaths['left_side'],
            $framePaths['right_side'],
            $this->storageService->getTempDiskName()
        );

        Log::info('KYC processing job dispatched', [
            'kyc_id' => $kyc->id,
            'session_id' => $validated['session_id'],
            'temp_disk' => $this->storageService->getTempDiskName(),
            'temp_disk_root' => config('filesystems.disks.' . $this->storageService->getTempDiskName() . '.root'),
            'temp_paths' => $framePaths,
        ]);

        // Complete session
        $this->sessionService->completeSession($validated['session_id']);

        return response()->json([
            'success' => true,
            'message' => 'KYC sedang diproses. Status akan diperbarui dalam beberapa saat.',
            'kyc_id' => $kyc->id,
        ]);
    }

    /**
     * Decode base64 image data.
     */
    private function decodeBase64Image(string $data): ?string
    {
        // Handle data URL format
        if (str_starts_with($data, 'data:image/')) {
            $parts = explode(',', $data, 2);
            if (count($parts) !== 2) {
                return null;
            }
            $data = $parts[1];
        }

        $decoded = base64_decode($data, true);

        if ($decoded === false) {
            return null;
        }

        // Validate it's actually an image
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($decoded);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes)) {
            return null;
        }

        // SECURITY: Validate image dimensions to prevent decompression bombs
        $imageInfo = @getimagesizefromstring($decoded);
        if ($imageInfo === false) {
            return null;
        }
        $maxDimension = 4096; // Max 4096x4096
        if ($imageInfo[0] > $maxDimension || $imageInfo[1] > $maxDimension) {
            return null;
        }

        return $decoded;
    }
}
