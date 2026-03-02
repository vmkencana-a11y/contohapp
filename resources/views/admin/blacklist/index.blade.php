@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Registration Blacklist</h1>
        <a href="{{ route('admin.blacklist.create') }}" class="btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Blacklist
        </a>
    </div>

    {{-- Filters --}}
    <div class="card p-4 mb-6">
        <form action="{{ route('admin.blacklist.index') }}" method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label for="type" class="text-sm font-medium text-gray-700 dark:text-gray-300">Tipe:</label>
                <select name="type" id="type" class="input-field w-auto min-w-[150px]">
                    <option value="">Semua</option>
                    @foreach($types as $type)
                    <option value="{{ $type->value }}" {{ $currentType === $type->value ? 'selected' : '' }}>
                        {{ $type->label() }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <label for="search" class="text-sm font-medium text-gray-700 dark:text-gray-300">Cari:</label>
                <input type="text" name="search" id="search" 
                       value="{{ $search }}"
                       class="input-field w-auto min-w-[200px]"
                       placeholder="Cari nilai...">
            </div>
            <button type="submit" class="btn-secondary">Filter</button>
            @if($currentType || $search)
            <a href="{{ route('admin.blacklist.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Reset</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nilai</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alasan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kadaluarsa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dibuat</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($entries as $entry)
                    <tr class="{{ $entry->isExpired() ? 'opacity-50' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="{{ $entry->type->badgeClass() }}">{{ $entry->type->label() }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <code class="text-sm bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">{{ $entry->value }}</code>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                            {{ $entry->reason ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($entry->expires_at)
                                <span class="{{ $entry->isExpired() ? 'text-red-500' : 'text-gray-500' }}">
                                    {{ $entry->expires_at->format('d M Y H:i') }}
                                    @if($entry->isExpired())
                                        <span class="text-xs">(Expired)</span>
                                    @endif
                                </span>
                            @else
                                <span class="text-gray-400">Permanen</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $entry->created_at->format('d M Y') }}
                            @if($entry->creator)
                                <br><span class="text-xs">oleh {{ $entry->creator->name }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                            <a href="{{ route('admin.blacklist.edit', $entry) }}" class="text-primary-600 hover:text-primary-800 text-sm font-medium">Edit</a>
                            <form action="{{ route('admin.blacklist.destroy', $entry) }}" method="POST" class="inline" onsubmit="return confirm('Hapus entry ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            Belum ada blacklist entry
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($entries->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $entries->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
