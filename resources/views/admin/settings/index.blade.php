@extends('layouts.admin')

@section('title', 'System Settings')

@section('content')
<div class="max-w-4xl mx-auto">
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
                    <span class="ml-4 text-sm font-medium text-gray-500" aria-current="page">System Settings</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Pengaturan Sistem</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Konfigurasi pengaturan global aplikasi secara real-time.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-xl bg-green-50 p-4 border border-green-200 dark:bg-green-900/30 dark:border-green-900/50">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST"
          x-data="settingsForm"
          data-kyc-driver="{{ $groupedSettings->get('kyc_storage')?->firstWhere('key', 'kyc_storage.driver')?->value ?? 'local' }}"
          data-maintenance-mode="{{ $groupedSettings->get('general')?->firstWhere('key', 'general.maintenance_mode')?->value ?? '0' }}"
          data-test-url="{{ route('admin.settings.kyc-storage.test') }}">
        @csrf
        @method('PUT')

        <div class="space-y-8">
            @foreach($groupedSettings as $group => $settings)
            {{-- Custom rendering for KYC Storage group --}}
            @if($group === 'kyc_storage')
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden">
                <div class="border-b border-gray-100 dark:border-gray-700 px-6 py-4 bg-gray-50 dark:bg-gray-800/50">
                    <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="flex h-2 w-2 rounded-full bg-amber-500"></span>
                        KYC Storage Configuration
                    </h2>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pilih lokasi penyimpanan file KYC (foto identitas & selfie).</p>
                </div>

                <div class="p-6 sm:p-8">
                    {{-- Driver Toggle --}}
                    @php $currentDriver = $settings->firstWhere('key', 'kyc_storage.driver')?->value ?? 'local'; @endphp
                    <div class="space-y-4">
                        <label class="block text-sm font-medium text-gray-900 dark:text-white">Storage Driver</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Local Option --}}
                            <label class="relative flex cursor-pointer rounded-xl border-2 p-4 transition-all"
                                   :class="kycDriver === 'local' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'">
                                <input type="radio" name="settings[kyc_storage.driver]" value="local"
                                       {{ $currentDriver === 'local' ? 'checked' : '' }}
                                       class="sr-only" x-model="kycDriver">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg"
                                         :class="kycDriver === 'local' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-500'">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Local Storage</p>
                                        <p class="text-xs text-gray-500">File disimpan di server lokal</p>
                                    </div>
                                </div>
                            </label>

                            {{-- S3 Option --}}
                            <label class="relative flex cursor-pointer rounded-xl border-2 p-4 transition-all"
                                   :class="kycDriver === 's3' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'">
                                <input type="radio" name="settings[kyc_storage.driver]" value="s3"
                                       {{ $currentDriver === 's3' ? 'checked' : '' }}
                                       class="sr-only" x-model="kycDriver">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg"
                                         :class="kycDriver === 's3' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-500'">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">S3-Compatible</p>
                                        <p class="text-xs text-gray-500">IDCloudHost, BiznetGio, dll</p>
                                    </div>
                                </div>
                            </label>
                        </div>

                        {{-- S3 Config Info (shown when S3 selected) --}}
                        <div x-show="kycDriver === 's3'" x-transition class="mt-4 space-y-4">
                            @if($kycStorageInfo['s3_configured'])
                                <div class="rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
                                    <div class="flex items-start gap-3">
                                        <svg class="h-5 w-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        <div class="text-sm">
                                            <p class="font-medium text-green-800 dark:text-green-200">Konfigurasi S3 terdeteksi</p>
                                            <div class="mt-2 text-green-700 dark:text-green-300 space-y-1">
                                                <p><span class="font-mono text-xs bg-green-100 dark:bg-green-900/40 px-1.5 py-0.5 rounded">Endpoint:</span> {{ $kycStorageInfo['s3_endpoint'] }}</p>
                                                <p><span class="font-mono text-xs bg-green-100 dark:bg-green-900/40 px-1.5 py-0.5 rounded">Bucket:</span> {{ $kycStorageInfo['s3_bucket'] }}</p>
                                                <p><span class="font-mono text-xs bg-green-100 dark:bg-green-900/40 px-1.5 py-0.5 rounded">Region:</span> {{ $kycStorageInfo['s3_region'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4">
                                    <div class="flex items-start gap-3">
                                        <svg class="h-5 w-5 text-amber-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        <div class="text-sm">
                                            <p class="font-medium text-amber-800 dark:text-amber-200">Konfigurasi S3 belum lengkap</p>
                                            <p class="mt-1 text-amber-700 dark:text-amber-300">Tambahkan variabel berikut di file <code class="font-mono text-xs bg-amber-100 dark:bg-amber-900/40 px-1 rounded">.env</code>:</p>
                                            <pre class="mt-2 text-xs font-mono bg-amber-100 dark:bg-amber-900/30 rounded-lg p-3 text-amber-800 dark:text-amber-200 overflow-x-auto">S3_KYC_ENDPOINT=https://s3.your-provider.com
S3_KYC_BUCKET=your-bucket-name
S3_KYC_REGION=id-jkt-1
S3_KYC_KEY=your-access-key
S3_KYC_SECRET=your-secret-key
S3_KYC_PATH_STYLE=true</pre>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Test Connection Button --}}
                            <div>
                                <button type="button" id="btn-test-s3"
                                        @click="testS3Connection()"
                                        :disabled="testingS3"
                                        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 transition-all">
                                    <svg x-show="!testingS3" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <svg x-show="testingS3" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    <span x-text="testingS3 ? 'Menguji koneksi...' : 'Test Koneksi S3'"></span>
                                </button>

                                {{-- Test Result --}}
                                <div x-show="hasTestResult()" x-transition class="mt-3">
                                    <div x-show="isTestSuccess()" class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3">
                                        <p class="text-sm text-green-800 dark:text-green-200" x-text="testResultMessage()"></p>
                                    </div>
                                    <div x-show="isTestFailure()" class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3">
                                        <p class="text-sm text-red-800 dark:text-red-200" x-text="testResultMessage()"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p class="text-xs text-gray-500 font-mono">kyc_storage.driver</p>
                    </div>
                </div>
            </div>
            @else
            {{-- Generic settings rendering --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden">
                <div class="border-b border-gray-100 dark:border-gray-700 px-6 py-4 bg-gray-50 dark:bg-gray-800/50">
                     <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white capitalize flex items-center gap-2">
                        <span class="flex h-2 w-2 rounded-full bg-indigo-600"></span>
                        {{ $group }} Configuration
                    </h2>
                </div>
                
                <div class="p-6 sm:p-8">
                    <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-2">
                        @foreach($settings as $setting)
                        <div class="col-span-1"
                             @if($setting->key === 'general.maintenance_end_time')
                             x-show="showMaintenanceEndTime()"
                             x-transition
                             @endif>
                            <label for="{{ $setting->key }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white mb-2">
                                {{ $setting->label ?? ucwords(str_replace('_', ' ', $setting->key)) }}
                            </label>
                            
                            @if($setting->type === 'boolean')
                                <select name="settings[{{ $setting->key }}]" id="{{ $setting->key }}" 
                                class="input-field w-full block"
                                @if($setting->key === 'general.maintenance_mode')
                                x-model="maintenanceMode"
                                @endif>
                                    <option value="1" {{ $setting->value == '1' ? 'selected' : '' }}>Enabled</option>
                                    <option value="0" {{ $setting->value == '0' ? 'selected' : '' }}>Disabled</option>
                                </select>
                            @elseif($setting->type === 'integer')
                                <input type="number" name="settings[{{ $setting->key }}]" id="{{ $setting->key }}" value="{{ $setting->value }}"
                                       class="input-field w-full block">
                            @elseif($setting->type === 'text')
                                <textarea name="settings[{{ $setting->key }}]" id="{{ $setting->key }}" rows="3"
                                          class="input-field w-full block">{{ $setting->value }}</textarea>
                            @elseif($setting->type === 'datetime')
                                <input type="datetime-local" name="settings[{{ $setting->key }}]" id="{{ $setting->key }}" value="{{ $setting->value }}"
                                       class="input-field w-full block">
                            @elseif($setting->type === 'email')
                                <input type="email" name="settings[{{ $setting->key }}]" id="{{ $setting->key }}" value="{{ $setting->value }}"
                                       class="input-field w-full block">
                            @else
                                <input type="text" name="settings[{{ $setting->key }}]" id="{{ $setting->key }}" value="{{ $setting->value }}"
                                       class="input-field w-full block">
                            @endif
                            <p class="mt-1 text-xs text-gray-500 font-mono">{{ $setting->key }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
            @endforeach
        </div>

        <div class="mt-8 flex items-center justify-end gap-x-6">
            <button type="button" class="text-sm font-semibold leading-6 text-gray-900 dark:text-white">Batal</button>
            <button type="submit" class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
