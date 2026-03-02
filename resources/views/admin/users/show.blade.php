@extends('layouts.admin')

@section('title', 'Detail User')

@section('content')
<div class="max-w-6xl mx-auto" x-data="{ showSuspendModal: false, showBanModal: false }">
    {{-- Breadcrumb & Back --}}
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
                    <a href="{{ route('admin.users.index') }}" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">Pengguna</a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="h-5 w-5 flex-shrink-0 text-gray-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                    </svg>
                    <span class="ml-4 text-sm font-medium text-gray-500" aria-current="page">{{ $user->name }}</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Main Profile Column --}}
        <div class="lg:col-span-2 space-y-8">
            {{-- Profile Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6 sm:p-8">
                <div class="sm:flex sm:items-center sm:justify-between">
                    <div class="sm:flex sm:space-x-5">
                        <div class="flex-shrink-0">
                            <span class="inline-flex h-20 w-20 items-center justify-center rounded-2xl bg-indigo-100 dark:bg-indigo-900/50">
                                <span class="text-3xl font-bold font-sans text-indigo-600 dark:text-indigo-400">{{ substr($user->name, 0, 1) }}</span>
                            </span>
                        </div>
                        <div class="mt-4 text-center sm:mt-0 sm:pt-1 sm:text-left">
                            <p class="text-xl font-bold text-gray-900 dark:text-white sm:text-2xl">{{ $user->name }}</p>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $user->email }}</p>
                            <div class="mt-2.5">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $user->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $user->status->label() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 border-t border-gray-100 dark:border-gray-700 pt-8">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID Pengguna</dt>
                            <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700/50 px-2 py-1 rounded inline-block">{{ $user->id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kode Referral</dt>
                            <dd class="mt-1 text-sm font-mono text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50 dark:bg-indigo-900/20 px-2 py-1 rounded inline-block">{{ $user->referral_code ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Bergabung</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->created_at->format('d F Y') }}</dd>
                        </div>
                         <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status KYC</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                @if($user->kyc)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded text-xs font-medium {{ $user->kyc->status === 'approved' ? 'bg-blue-50 text-blue-700' : ($user->kyc->status === 'rejected' ? 'bg-red-50 text-red-700' : 'bg-yellow-50 text-yellow-700') }}">
                                        @if($user->kyc->status === 'approved')
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        @endif
                                        {{ $user->kyc->status->label() }}
                                    </span>
                                @else
                                    <span class="text-gray-400 italic">Belum submit</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Activity / History Placeholder --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden">
                <div class="border-b border-gray-100 dark:border-gray-700 px-6 py-5">
                    <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white">Riwayat Status Akun</h3>
                </div>
                 <div class="flow-root p-6">
                    <ul role="list" class="-mb-8">
                        @forelse($statusLogs as $log)
                        <li>
                            <div class="relative pb-8">
                                @if(!$loop->last)
                                <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                @endif
                                <div class="relative flex space-x-3">
                                    <div>
                                        <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-800 {{ $log->action === 'banned' ? 'bg-red-500' : ($log->action === 'active' ? 'bg-green-500' : 'bg-yellow-500') }}">
                                            @if($log->action === 'active')
                                                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            @elseif($log->action === 'banned')
                                                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            @else
                                                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400"><span class="font-medium text-gray-900 dark:text-white">{{ ucfirst($log->action) }}</span> - {{ $log->reason }}</p>
                                        </div>
                                        <div class="whitespace-nowrap text-right text-sm text-gray-500">{{ $log->created_at->format('d M Y') }}</div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @empty
                        <li class="text-center py-4 text-gray-500 italic text-sm text-center">Belum ada riwayat perubahan status.</li>
                        @endforelse
                    </ul>
                    @if($statusLogs->hasPages())
                    <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
                        {{ $statusLogs->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Actions Column --}}
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white mb-4">Aksi Cepat</h3>
                <div class="space-y-3">
                    @if($user->status->value === 'active')
                    <button @click="showSuspendModal = true" class="w-full rounded-xl bg-yellow-50 px-3.5 py-2.5 text-sm font-semibold text-yellow-600 shadow-sm hover:bg-yellow-100 border border-yellow-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-600 transition-all flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Tangguhkan Akun
                    </button>
                    <button @click="showBanModal = true" class="w-full rounded-xl bg-red-50 px-3.5 py-2.5 text-sm font-semibold text-red-600 shadow-sm hover:bg-red-100 border border-red-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 transition-all flex items-center justify-center gap-2">
                         <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Blokir Permanen
                    </button>
                    @elseif($user->status->value === 'suspended')
                    <form action="{{ route('admin.users.reactivate', $user) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full rounded-xl bg-green-50 px-3.5 py-2.5 text-sm font-semibold text-green-600 shadow-sm hover:bg-green-100 border border-green-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 transition-all flex items-center justify-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Aktifkan Kembali
                        </button>
                    </form>
                    <button @click="showBanModal = true" class="w-full rounded-xl bg-red-50 px-3.5 py-2.5 text-sm font-semibold text-red-600 shadow-sm hover:bg-red-100 border border-red-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 transition-all flex items-center justify-center gap-2">
                        Blokir Permanen
                    </button>
                    @elseif($user->status->value === 'banned')
                    <div class="rounded-xl bg-red-50 p-4 border border-red-100">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Akun Diblokir</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p>Pengguna ini telah diblokir secara permanen dan tidak dapat dipulihkan.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="bg-indigo-900 rounded-2xl shadow-sm ring-1 ring-gray-900/5 p-6 text-white overflow-hidden relative">
                 <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-white/10 blur-xl"></div>
                 <h3 class="text-base font-semibold leading-6 text-white mb-2 relative z-10">Kontak User</h3>
                 <p class="text-indigo-200 text-sm mb-4 relative z-10">Hubungi user via email jika diperlukan.</p>
                 <a href="mailto:{{ $user->email }}" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-sm font-semibold text-white hover:bg-white/20 transition-colors relative z-10 w-full justify-center">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Kirim Email
                 </a>
            </div>
        </div>
    </div>

    {{-- Modals --}}
    
    {{-- Suspend Modal --}}
    <div x-show="showSuspendModal" x-cloak class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div x-show="showSuspendModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="showSuspendModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" @click.away="showSuspendModal = false" class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <div class="bg-white dark:bg-gray-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white" id="modal-title">Tangguhkan Akun</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Apakah Anda yakin ingin menangguhkan akun ini? Pengguna tidak akan bisa login sementara.</p>
                                    <form id="suspendForm" action="{{ route('admin.users.suspend', $user) }}" method="POST" class="mt-4">
                                        @csrf
                                        <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alasan Penangguhan</label>
                                        <textarea name="reason" id="reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 sm:text-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white" required placeholder="Jelaskan alasan..."></textarea>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="submit" form="suspendForm" class="inline-flex w-full justify-center rounded-lg bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500 sm:ml-3 sm:w-auto">Tangguhkan</button>
                        <button type="button" @click="showSuspendModal = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 sm:mt-0 sm:w-auto">Batal</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Ban Modal --}}
    <div x-show="showBanModal" x-cloak class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div x-show="showBanModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="showBanModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" @click.away="showBanModal = false" class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <div class="bg-white dark:bg-gray-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white" id="modal-title">Blokir Akun Permanen</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-red-600 font-medium bg-red-50 p-3 rounded-lg border border-red-100 mb-4">
                                        ⚠️ Tindakan ini tidak dapat dibatalkan. Pengguna akan kehilangan akses selamanya.
                                    </p>
                                    <form id="banForm" action="{{ route('admin.users.ban', $user) }}" method="POST">
                                        @csrf
                                        <label for="ban_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alasan Pemblokiran</label>
                                        <textarea name="reason" id="ban_reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white" required placeholder="Sebutkan pelanggaran yang dilakukan..."></textarea>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="submit" form="banForm" class="inline-flex w-full justify-center rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Blokir Permanen</button>
                        <button type="button" @click="showBanModal = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 sm:mt-0 sm:w-auto">Batal</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
