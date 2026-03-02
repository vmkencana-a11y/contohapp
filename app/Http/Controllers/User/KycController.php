<?php

namespace App\Http\Controllers\User;

use App\Enums\KycStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\UserKyc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * KYC Controller (View & Status only).
 * 
 * Document capture is handled by KycCaptureController (camera-only).
 */
class KycController extends Controller
{
    /**
     * Show KYC page.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $kyc = $user->kyc;
        
        return view('user.kyc', [
            'user' => $user,
            'kyc' => $kyc,
            'canSubmit' => !$kyc || $kyc->status === KycStatusEnum::REJECTED,
        ]);
    }

    /**
     * Check KYC processing status (for AJAX polling).
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $kyc = $user->kyc;

        if (!$kyc) {
            return response()->json([
                'status' => null,
                'message' => 'Belum ada pengajuan KYC',
            ]);
        }

        return response()->json([
            'status' => $kyc->status->value,
            'label' => $kyc->status->label(),
            'isProcessing' => $kyc->status->isProcessing(),
            'message' => match($kyc->status) {
                KycStatusEnum::PROCESSING => 'Dokumen sedang diproses...',
                KycStatusEnum::PENDING => 'Dokumen menunggu verifikasi.',
                KycStatusEnum::UNDER_REVIEW => 'Dokumen sedang direview.',
                KycStatusEnum::APPROVED => 'KYC telah disetujui.',
                KycStatusEnum::REJECTED => 'KYC ditolak: ' . ($kyc->rejection_reason ?? 'Tidak ada alasan'),
            },
        ]);
    }
}
