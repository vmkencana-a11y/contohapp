@extends('layouts.admin')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.blacklist.index') }}" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tambah Blacklist</h1>
    </div>

    <div class="card p-6">
        <form action="{{ route('admin.blacklist.store') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipe <span class="text-red-500">*</span></label>
                <select name="type" id="type" required class="input-field w-full" x-data x-on:change="$refs.value.placeholder = $el.options[$el.selectedIndex].dataset.placeholder">
                    <option value="">Pilih Tipe</option>
                    @foreach($types as $type)
                    <option value="{{ $type->value }}" data-placeholder="{{ $type->placeholder() }}" {{ old('type') === $type->value ? 'selected' : '' }}>
                        {{ $type->label() }}
                    </option>
                    @endforeach
                </select>
                @error('type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nilai <span class="text-red-500">*</span></label>
                <input type="text" name="value" id="value" x-ref="value" required
                       value="{{ old('value') }}"
                       class="input-field w-full"
                       placeholder="Masukkan nilai yang akan diblokir">
                <p class="mt-1 text-xs text-gray-500">Nilai akan disimpan dalam huruf kecil</p>
                @error('value')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alasan</label>
                <textarea name="reason" id="reason" rows="3"
                          class="input-field w-full"
                          placeholder="Alasan pemblokiran (opsional)">{{ old('reason') }}</textarea>
                @error('reason')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="expires_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kadaluarsa</label>
                <input type="datetime-local" name="expires_at" id="expires_at"
                       value="{{ old('expires_at') }}"
                       class="input-field w-full">
                <p class="mt-1 text-xs text-gray-500">Kosongkan untuk blokir permanen</p>
                @error('expires_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('admin.blacklist.index') }}" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
