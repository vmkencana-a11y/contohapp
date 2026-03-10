@extends('layouts.admin')

@section('title', 'Edit Secret')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <a href="{{ route('admin.secrets.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Kembali ke Secret Manager</a>
        <h1 class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">Edit Secret</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $serviceLabel }} - {{ $definition['label'] ?? $secret->secret_key }}</p>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('admin.secrets.update', $secret) }}" method="POST" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white">Service</label>
                    <input type="text" value="{{ $serviceLabel }} ({{ $secret->service }})" class="mt-2 block w-full rounded-xl border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:text-white" disabled>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white">Key</label>
                    <input type="text" value="{{ $definition['label'] ?? $secret->secret_key }} ({{ $secret->secret_key }})" class="mt-2 block w-full rounded-xl border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:text-white" disabled>
                </div>
            </div>

            <div>
                <label for="value" class="block text-sm font-medium text-gray-900 dark:text-white">Nilai Baru</label>
                <textarea id="value" name="value" rows="5" class="mt-2 block w-full rounded-xl border-gray-300 font-mono dark:border-gray-600 dark:bg-gray-900 dark:text-white" placeholder="Kosongkan jika tidak ingin mengubah nilainya."></textarea>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Nilai yang tersimpan saat ini dimasking dan tidak ditampilkan kembali.</p>
            </div>

            <div>
                <label class="inline-flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('is_active', $secret->is_active ? '1' : '0') === '1' ? 'checked' : '' }}>
                    <span class="text-sm text-gray-700 dark:text-gray-300">Secret aktif</span>
                </label>
            </div>
        </div>

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('admin.secrets.index') }}" class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Batal</a>
            <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
