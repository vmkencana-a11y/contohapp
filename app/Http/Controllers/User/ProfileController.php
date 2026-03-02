<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Show profile page.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        
        return view('user.profile', [
            'user' => $user,
            'activeSessions' => $user->activeSessions()->count(),
        ]);
    }

    /**
     * Update profile.
     */
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['nullable', 'string', 'regex:/^08[0-9]{8,12}$/'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'phone.regex' => 'Format nomor HP tidak valid (contoh: 08123456789).',
        ]);

        $user = $request->user();
        $user->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Profil berhasil diperbarui.',
            ]);
        }

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
