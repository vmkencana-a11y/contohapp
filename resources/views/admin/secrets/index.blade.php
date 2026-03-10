@extends('layouts.admin')

@section('title', 'Secret Manager')

@section('content')
<div class="max-w-6xl mx-auto space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Secret Manager</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Kelola kredensial SMTP, Google OAuth, dan S3 KYC dari database terenkripsi.</p>
        </div>
        <div class="flex gap-3">
            @if($canManage)
                <form action="{{ route('admin.secrets.refresh-cache') }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Refresh Cache
                    </button>
                </form>
                <a href="{{ route('admin.secrets.create') }}" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Tambah Secret
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        @foreach ($serviceSummary as $summary)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $summary['label'] }}</p>
                <div class="mt-3 flex items-end justify-between">
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $summary['configured'] }}/{{ $summary['expected'] }}</p>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $summary['configured'] === $summary['expected'] ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-200' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200' }}">
                        {{ $summary['configured'] === $summary['expected'] ? 'Lengkap' : 'Belum lengkap' }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Daftar Secret</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/40">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Service</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Key</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Value</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Updated</th>
                        @if($canManage)
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($secrets as $secret)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                <div class="font-semibold">{{ \App\Support\SecretCatalog::serviceLabel($secret->service) }}</div>
                                <div class="text-xs text-gray-500">{{ $secret->service }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                <div class="font-semibold">{{ \App\Support\SecretCatalog::keyLabel($secret->service, $secret->secret_key) }}</div>
                                <div class="text-xs text-gray-500">{{ $secret->secret_key }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm font-mono text-gray-700 dark:text-gray-300">{{ $secret->masked_value }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $secret->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}">
                                    {{ $secret->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ optional($secret->updated_at)->format('d M Y H:i') ?? '-' }}</td>
                            @if($canManage)
                                <td class="px-6 py-4 text-right text-sm">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('admin.secrets.edit', $secret) }}" class="font-semibold text-indigo-600 hover:text-indigo-500">Edit</a>
                                        <form action="{{ route('admin.secrets.destroy', $secret) }}" method="POST" onsubmit="return confirm('Hapus secret ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-semibold text-red-600 hover:text-red-500">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 6 : 5 }}" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada secret tersimpan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
