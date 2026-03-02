@extends('layouts.admin')

@section('title', 'Review KYC User')

@section('content')
<div class="max-w-6xl mx-auto" x-data="{ showRejectModal: false }">
    {{-- Breadcrumb --}}
    <nav class="flex mb-8" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li>
                <div>
                    <a href="{{ route('admin.dashboard') }}" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M9.293 2.293a1 1 0 011.414 0l7 7A1 1 0 0117 11h-1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1H9a1 1 0 00-1 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-6H3a1 1 0 01-.707-1.707l7-7z" clip-rule="evenodd" />
                        </svg>
                        <span class="sr-only">Home</span>
                    </a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="h-5 w-5 flex-shrink-0 text-gray-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                    </svg>
                    <a href="{{ route('admin.kyc.index') }}" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">Review KYC</a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="h-5 w-5 flex-shrink-0 text-gray-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                    </svg>
                    <span class="ml-4 text-sm font-medium text-gray-500" aria-current="page">{{ $kyc->user->name ?? 'Detail' }}</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Main Content Column --}}
        <div class="lg:col-span-2 space-y-8">
            {{-- User Info Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6 sm:p-8">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-100 dark:bg-indigo-900/50">
                                <span class="text-2xl font-bold font-sans text-indigo-600 dark:text-indigo-400">{{ substr($kyc->user->name ?? 'U', 0, 1) }}</span>
                            </span>
                             <span class="absolute -bottom-1 -right-1 block text-2xl" title="Status KYC">
                                @if($kyc->status->value === 'approved') 🟢 @elseif($kyc->status->value === 'rejected') 🔴 @else 🟡 @endif
                             </span>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $kyc->user->name ?? 'Unknown' }}</h1>
                            <p class="text-sm text-gray-500 font-mono">{{ $kyc->user->id ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Jenis Dokumen</dt>
                            <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white uppercase">{{ $kyc->id_type }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nomor Identitas (NIK)</dt>
                            <dd class="mt-1 text-sm font-mono font-bold text-gray-900 dark:text-white tracking-wide bg-gray-50 dark:bg-gray-700/50 px-2 py-1 rounded inline-block">{{ $kyc->decrypted_id_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Pengajuan</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $kyc->created_at->format('d F Y, H:i') }} WIB</dd>
                        </div>
                        @if($kyc->verified_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Verifikasi</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $kyc->verified_at->format('d F Y, H:i') }} WIB</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Documents Preview --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6 sm:p-8">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Dokumen Lampiran</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    {{-- Left Side --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Tampak Samping Kiri</p>
                            <a href="{{ route('admin.kyc.image', ['kyc' => $kyc, 'type' => 'left_side']) }}" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-500">Buka Full Size &nearr;</a>
                        </div>
                        <div class="relative aspect-[16/10] w-full rounded-xl bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 overflow-hidden group">
                             @if($kyc->left_side_path)
                                <img src="{{ route('admin.kyc.image', ['kyc' => $kyc, 'type' => 'left_side']) }}" alt="Left Side" class="absolute inset-0 h-full w-full object-contain transition-transform duration-500 group-hover:scale-105">
                            @else
                                <div class="absolute inset-0 flex items-center justify-center text-gray-400 flex-col gap-2">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span class="text-xs">Tidak tersedia</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Right Side --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Tampak Samping Kanan</p>
                            <a href="{{ route('admin.kyc.image', ['kyc' => $kyc, 'type' => 'right_side']) }}" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-500">Buka Full Size &nearr;</a>
                        </div>
                        <div class="relative aspect-[16/10] w-full rounded-xl bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 overflow-hidden group">
                             @if($kyc->right_side_path)
                                <img src="{{ route('admin.kyc.image', ['kyc' => $kyc, 'type' => 'right_side']) }}" alt="Right Side" class="absolute inset-0 h-full w-full object-contain transition-transform duration-500 group-hover:scale-105">
                            @else
                                <div class="absolute inset-0 flex items-center justify-center text-gray-400 flex-col gap-2">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span class="text-xs">Tidak tersedia</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- ID Card --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Foto Kartu Identitas</p>
                            <a href="{{ route('admin.kyc.image', ['kyc' => $kyc, 'type' => 'id_card']) }}" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-500">Buka Full Size &nearr;</a>
                        </div>
                        <div class="relative aspect-[16/10] w-full rounded-xl bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 overflow-hidden group">
                             @if($kyc->id_card_path)
                                <img src="{{ route('admin.kyc.image', ['kyc' => $kyc, 'type' => 'id_card']) }}" alt="ID Card" class="absolute inset-0 h-full w-full object-contain transition-transform duration-500 group-hover:scale-105">
                            @else
                                <div class="absolute inset-0 flex items-center justify-center text-gray-400 flex-col gap-2">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span class="text-xs">Tidak tersedia</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Selfie --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                             <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Foto Selfie dengan ID</p>
                             <a href="{{ route('admin.kyc.image', ['kyc' => $kyc, 'type' => 'selfie']) }}" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-500">Buka Full Size &nearr;</a>
                        </div>
                        <div class="relative aspect-[16/10] w-full rounded-xl bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 overflow-hidden group">
                            @if($kyc->selfie_path)
                                <img src="{{ route('admin.kyc.image', ['kyc' => $kyc, 'type' => 'selfie']) }}" alt="Selfie" class="absolute inset-0 h-full w-full object-contain transition-transform duration-500 group-hover:scale-105">
                            @else
                                <div class="absolute inset-0 flex items-center justify-center text-gray-400 flex-col gap-2">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span class="text-xs">Tidak tersedia</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if($kyc->rejection_reason)
            <div class="rounded-2xl bg-red-50 p-6 border border-red-100 dark:bg-red-900/20 dark:border-red-900/30">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Alasan Penolakan</h3>
                        <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                            <p>{{ $kyc->rejection_reason }}</p>
                        </div>
                        @if($kyc->verifier)
                        <div class="mt-4 text-xs text-red-600 dark:text-red-400">
                            Direview oleh: <span class="font-semibold">{{ $kyc->verifier->name }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Actions Column --}}
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white mb-4">Keputusan</h3>
                
                @if($kyc->status->value === 'pending' || $kyc->status->value === 'under_review')
                    <div class="space-y-3">
                        <form action="{{ route('admin.kyc.approve', $kyc) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full rounded-xl bg-green-600 px-3.5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 transition-all flex items-center justify-center gap-2">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Setujui Verifikasi
                            </button>
                        </form>
                        
                        <button @click="showRejectModal = true" class="w-full rounded-xl bg-white px-3.5 py-3 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-white dark:ring-gray-600 dark:hover:bg-gray-700 flex items-center justify-center gap-2">
                            <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Tolak Pengajuan
                        </button>
                    </div>
                    <p class="mt-4 text-xs text-gray-500 text-center">
                        Pastikan data sesuai sebelum menyetujui. Aksi ini akan mengupdate status akun pengguna menjadi <span class="font-medium text-gray-900 dark:text-white">Verified</span>.
                    </p>
                @elseif($kyc->status->value === 'approved')
                    <div class="rounded-xl bg-green-50 p-4 border border-green-100 flex flex-col items-center justify-center text-center">
                        <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center mb-2">
                            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <h3 class="text-sm font-medium text-green-800">Terverifikasi</h3>
                        <p class="text-xs text-green-600 mt-1">Disetujui pada {{ $kyc->verified_at ? $kyc->verified_at->format('d M Y') : '-' }}</p>
                        @if($kyc->verifier)
                        <p class="text-xs text-green-600 mt-1">Oleh: {{ $kyc->verifier->name }}</p>
                        @endif
                    </div>
                @else
                    <div class="rounded-xl bg-red-50 p-4 border border-red-100 flex flex-col items-center justify-center text-center">
                         <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center mb-2">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                        <h3 class="text-sm font-medium text-red-800">Ditolak</h3>
                        <p class="text-xs text-red-600 mt-1">Ditolak pada {{ $kyc->updated_at->format('d M Y') }}</p>
                    </div>
                @endif
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-4 border border-blue-100 dark:border-blue-900/30">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1 md:flex md:justify-between text-sm text-blue-700 dark:text-blue-300">
                         <p>Pastikan foto dokumen terbaca jelas dengan pencahayaan cukup dan data diri sesuai dengan input pengguna.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div x-show="showRejectModal" x-cloak class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div x-show="showRejectModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="showRejectModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" @click.away="showRejectModal = false" class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <div class="bg-white dark:bg-gray-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                             <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white" id="modal-title">Tolak Pengajuan KYC</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Mohon berikan alasan penolakan yang jelas agar pengguna dapat memperbaiki datanya.</p>
                                    <form id="rejectForm" action="{{ route('admin.kyc.reject', $kyc) }}" method="POST" class="mt-4">
                                        @csrf
                                        <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alasan Penolakan</label>
                                        <textarea name="reason" id="reason" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white" required placeholder="Contoh: Foto buram, data tidak sesuai, dokumen kadaluarsa..."></textarea>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="submit" form="rejectForm" class="inline-flex w-full justify-center rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Tolak Pengajuan</button>
                        <button type="button" @click="showRejectModal = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 sm:mt-0 sm:w-auto">Batal</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
