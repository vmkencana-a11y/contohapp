@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-8">Profil Saya</h1>

    <div class="card p-6">
        <form action="{{ route('profile.update') }}" method="POST" class="space-y-6" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            @method('PUT')

            {{-- Avatar & Basic Info --}}
            <div class="flex items-center gap-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white text-2xl font-bold">
                    {{ substr($user->name, 0, 1) }}
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $user->name }}</h2>
                    <p class="text-gray-500 dark:text-gray-400">{{ $user->masked_email }}</p>
                    <span class="{{ $user->status->badgeClass() }} mt-2 inline-block">{{ $user->status->label() }}</span>
                </div>
            </div>

            {{-- Editable Fields --}}
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" id="name" required
                           value="{{ old('name', $user->name) }}"
                           class="input-field w-full">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nomor HP</label>
                    <input type="text" name="phone" id="phone"
                           value="{{ old('phone', $user->decrypted_phone) }}"
                           class="input-field w-full"
                           placeholder="08123456789">
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                    <input type="email" disabled
                           value="{{ $user->masked_email }}"
                           class="input-field w-full bg-gray-100 dark:bg-gray-700 cursor-not-allowed">
                    <p class="mt-1 text-xs text-gray-500">Email tidak dapat diubah</p>
                </div>
            </div>

            {{-- Account Info (Read-only) --}}
            <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Informasi Akun</h3>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">ID Akun</dt>
                        <dd class="font-mono text-gray-900 dark:text-white">{{ $user->id }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Kode Referral</dt>
                        <dd class="font-mono text-primary-600 dark:text-primary-400">{{ $user->referral_code }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Bergabung</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $user->created_at->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Sesi Aktif</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $activeSessions }} perangkat</dd>
                    </div>
                </dl>
            </div>

            <div class="pt-6">
                <button type="submit" class="btn-primary" :disabled="loading">
                    <span x-show="!loading">Simpan Perubahan</span>
                    <span x-show="loading">Menyimpan...</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
