@extends('layouts.admin')

@section('title', 'Edit Profil')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Edit Profil</h1>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(session('info'))
        <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg mb-6">
            {{ session('info') }}
        </div>
    @endif

    <!-- Profile Information -->
    <div class="card p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informasi Profil</h2>
        
        <form action="{{ route('admin.profile.update') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama</label>
                <input type="text" name="name" id="name" value="{{ old('name', $admin->name) }}" required
                       class="input-field w-full">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email', $admin->email) }}" required
                       class="input-field w-full">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>

    <!-- Change Password -->
    <div class="card p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Ubah Password</h2>
        
        <form action="{{ route('admin.profile.password') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password Lama</label>
                <input type="password" name="current_password" id="current_password" required
                       class="input-field w-full">
                @error('current_password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password Baru</label>
                <input type="password" name="password" id="password" required minlength="8"
                       class="input-field w-full">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Konfirmasi Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                       class="input-field w-full">
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-primary">Ubah Password</button>
            </div>
        </form>
    </div>

    <!-- 2FA Section -->
    <div class="card p-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-lg bg-primary-100 dark:bg-primary-900 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Google Authenticator (2FA)</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Tambahkan lapisan keamanan ekstra dengan verifikasi 2 langkah.
                </p>

                @if($admin->has2faEnabled())
                    <div class="my-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-green-700 dark:text-green-400 font-medium">2FA Aktif</span>
                        </div>
                        <p class="text-sm text-green-600 dark:text-green-500 mt-1">
                            Diaktifkan: {{ $admin->two_factor_enabled_at?->format('d M Y H:i') }}
                        </p>
                    </div>

                    <div class="mt-4" x-data="{ showConfirm: false }">
                        <button @click="showConfirm = true" class="btn-danger">
                            Nonaktifkan 2FA
                        </button>

                        <!-- Confirm Modal -->
                        <div x-show="showConfirm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6" @click.outside="showConfirm = false">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Konfirmasi Nonaktifkan 2FA</h3>
                                <p class="text-sm text-gray-500 mb-4">Masukkan password untuk konfirmasi.</p>
                                <form action="{{ route('admin.profile.2fa.disable') }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <input type="password" name="password" placeholder="Password" required
                                           class="input-field w-full mb-4">
                                    <div class="flex gap-3">
                                        <button type="button" @click="showConfirm = false" class="btn-secondary flex-1">Batal</button>
                                        <button type="submit" class="btn-danger flex-1">Nonaktifkan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mt-6 mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span class="text-amber-700 dark:text-amber-400 font-medium">2FA Belum Aktif</span>
                        </div>
                        <p class="text-sm text-amber-600 dark:text-amber-500 mt-1">
                            Sangat disarankan untuk mengaktifkan 2FA.
                        </p>
                    </div>

                    <a href="{{ route('admin.profile.2fa.setup') }}" class="btn-primary mt-4 inline-block">
                        Aktifkan 2FA
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Account Info -->
    <div class="card p-6 mt-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informasi Akun</h2>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-gray-500">ID Admin</dt>
                <dd class="font-medium text-gray-900 dark:text-white font-mono">{{ $admin->id }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Status</dt>
                <dd>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $admin->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ ucfirst($admin->status) }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">Login Terakhir</dt>
                <dd class="font-medium text-gray-900 dark:text-white">
                    {{ $admin->last_login_at?->format('d M Y H:i') ?? '-' }}
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">Dibuat</dt>
                <dd class="font-medium text-gray-900 dark:text-white">
                    {{ $admin->created_at?->format('d M Y') ?? '-' }}
                </dd>
            </div>
        </dl>
    </div>
</div>
@endsection
