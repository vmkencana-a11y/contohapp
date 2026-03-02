@extends('layouts.admin')

@section('title', 'Logs Dashboard')

@section('content')
<div class="max-w-7xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Logs System & Audit Trail</h1>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="?tab=security"
               class="{{ $tab === 'security' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Security Events
            </a>
            <a href="?tab=admin"
               class="{{ $tab === 'admin' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Admin Activity
            </a>
            <a href="?tab=login"
               class="{{ $tab === 'login' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                User Login
            </a>
            <a href="?tab=status"
               class="{{ $tab === 'status' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                User Status Changes
            </a>
        </nav>
    </div>

    {{-- Content --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase font-medium text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-4">Timestamp</th>
                        @if($tab === 'security')
                            <th class="px-6 py-4">Severity</th>
                            <th class="px-6 py-4">Event Type</th>
                            <th class="px-6 py-4">Actor / IP</th>
                            <th class="px-6 py-4">Metadata</th>
                        @elseif($tab === 'admin')
                            <th class="px-6 py-4">Admin</th>
                            <th class="px-6 py-4">Action</th>
                            <th class="px-6 py-4">Subject</th>
                            <th class="px-6 py-4">Details</th>
                        @elseif($tab === 'login')
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4">IP Address</th>
                            <th class="px-6 py-4">Browser / Device</th>
                            <th class="px-6 py-4">Metadata</th>
                        @elseif($tab === 'status')
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4">Transition</th>
                            <th class="px-6 py-4">Changed By</th>
                            <th class="px-6 py-4">Reason</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                            {{ $log->created_at->format('d M Y H:i:s') }}
                        </td>

                        @if($tab === 'security')
                            <td class="px-6 py-4">
                                <span class="badge {{ match($log->severity) {
                                    'high', 'critical' => 'badge-error',
                                    'medium' => 'badge-warning',
                                    default => 'badge-info'
                                } }}">
                                    {{ ucfirst($log->severity) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">{{ $log->event_type }}</td>
                            <td class="px-6 py-4 text-xs">
                                <div>{{ $log->actor_identifier ?? '-' }}</div>
                                <div class="text-gray-400">{{ $log->ip_address }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <button type="button"
                                        class="text-primary-600 text-xs hover:underline js-view-metadata"
                                        data-metadata="{{ base64_encode(json_encode(is_array($log->metadata) ? $log->metadata : json_decode($log->metadata, true), JSON_PRETTY_PRINT)) }}">
                                    View JSON
                                </button>
                            </td>

                        @elseif($tab === 'admin')
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                {{ $log->admin->name ?? 'Unknown (ID:'.$log->admin_id.')' }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">
                                {{ $log->action }}
                            </td>
                            <td class="px-6 py-4 text-xs">
                                {{ $log->subject_type }} #{{ $log->subject_id }}
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500 max-w-xs truncate">
                                {{ $log->reason }}
                                @if($log->metadata)
                                    <span class="block font-mono text-[10px] mt-1 text-gray-400">
                                        {{ Str::limit(is_array($log->metadata) ? json_encode($log->metadata) : $log->metadata, 50) }}
                                    </span>
                                @endif
                            </td>

                        @elseif($tab === 'login')
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                {{ $log->user->name ?? 'User #'.$log->user_id }}
                            </td>
                            <td class="px-6 py-4 text-xs font-mono">
                                {{ $log->ip_address }}
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500">
                                {{ $log->user_agent }}
                            </td>
                            <td class="px-6 py-4 text-xs font-mono text-gray-400">
                                {{ Str::limit(is_array($log->metadata) ? json_encode($log->metadata) : $log->metadata, 30) }}
                            </td>

                        @elseif($tab === 'status')
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                {{ $log->user->name ?? 'User #'.$log->user_id }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2 text-xs uppercase font-bold">
                                    <span class="text-gray-400">{{ $log->old_status }}</span>
                                    <span>&rarr;</span>
                                    <span class="{{ match($log->new_status) {
                                        'active' => 'text-green-600',
                                        'banned', 'suspended' => 'text-red-600',
                                        'inactive' => 'text-gray-600',
                                        default => 'text-blue-600'
                                    } }}">{{ $log->new_status }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <span class="badge badge-gray">{{ $log->changed_by_type }}</span>
                                <span class="ml-1 text-gray-500">
                                    @if($log->changed_by_type === 'admin' && $log->admin)
                                        {{ $log->admin->name }}
                                    @else
                                        {{ $log->changed_by_id }}
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500">
                                {{ $log->reason }}
                            </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            Tidak ada data log untuk kategori ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
<script @cspNonce>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-view-metadata').forEach((button) => {
        button.addEventListener('click', () => {
            const encoded = button.dataset.metadata || '';
            let decoded = 'Metadata tidak valid.';
            try {
                decoded = atob(encoded);
            } catch (error) {
                // Keep fallback message if decoding fails.
            }
            alert(decoded);
        });
    });
});
</script>
@endsection
