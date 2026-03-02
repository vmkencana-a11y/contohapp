@extends('layouts.admin')

@section('title', 'Edit Admin')

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
                    <a href="{{ route('admin.admins.index') }}" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">Manajemen Admin</a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="h-5 w-5 flex-shrink-0 text-gray-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                         <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                    </svg>
                    <span class="ml-4 text-sm font-medium text-gray-500" aria-current="page">Edit Admin</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6 sm:p-8">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Admin</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Perbarui informasi dan hak akses administrator.</p>
            </div>
             @if($admin->id === Auth::guard('admin')->id())
                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-300">Akun Anda</span>
            @endif
        </div>

        <form action="{{ route('admin.admins.update', $admin) }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-2">
                <div class="col-span-full">
                    <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white">Informasi Akun</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400">Perbarui nama atau email login.</p>
                </div>

                <div class="sm:col-span-1">
                    <label for="name" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Nama Lengkap</label>
                    <div class="mt-2">
                        <input type="text" name="name" id="name" value="{{ old('name', $admin->name) }}" required
                               class="input-field w-full block">
                    </div>
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-1">
                    <label for="email" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Alamat Email</label>
                    <div class="mt-2">
                        <input type="email" name="email" id="email" value="{{ old('email', $admin->email) }}" required
                               class="input-field w-full block">
                    </div>
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-full border-t border-gray-100 dark:border-gray-700 pt-8">
                    <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white">Ubah Password</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400">Kosongkan jika tidak ingin mengubah password.</p>
                </div>

                <div class="sm:col-span-1">
                    <label for="password" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Password Baru</label>
                    <div class="mt-2">
                        <input type="password" name="password" id="password"
                               class="input-field w-full block" placeholder="••••••••">
                    </div>
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-1">
                    <label for="password_confirmation" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Konfirmasi Password Baru</label>
                    <div class="mt-2">
                        <input type="password" name="password_confirmation" id="password_confirmation"
                               class="input-field w-full block" placeholder="••••••••">
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 dark:border-gray-700 pt-8">
                <div class="col-span-full mb-6">
                    <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white">Hak Akses (Role)</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400">Perbarui peran akses untuk admin ini.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($roles as $role)
                    <div class="relative flex items-start p-4 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex h-6 items-center">
                            <input id="role-{{ $role->id }}" name="roles[]" value="{{ $role->id }}" type="checkbox"
                                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 dark:border-gray-600 dark:bg-gray-800"
                                   {{ in_array($role->id, old('roles', $adminRoles)) ? 'checked' : '' }}>
                        </div>
                        <div class="ml-3 text-sm leading-6">
                            <label for="role-{{ $role->id }}" class="font-medium text-gray-900 dark:text-white">{{ $role->name }}</label>
                             <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">Akses sebagai {{ strtolower($role->name) }} sistem.</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                 @error('roles')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="border-t border-gray-100 dark:border-gray-700 pt-8">
                <div class="col-span-full mb-6">
                    <h2 class="text-base font-semibold leading-7 text-gray-900 dark:text-white">Jam Kerja</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400">Batasi waktu login admin. Kosongkan untuk akses 24/7.</p>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-white mb-3">Hari Kerja</label>
                        <div class="flex flex-wrap gap-3">
                            @php
                                $days = [
                                    1 => 'Senin',
                                    2 => 'Selasa',
                                    3 => 'Rabu',
                                    4 => 'Kamis',
                                    5 => 'Jumat',
                                    6 => 'Sabtu',
                                    7 => 'Minggu',
                                ];
                                $oldDays = old('working_days', $admin->working_days ?? []);
                            @endphp
                            @foreach($days as $value => $label)
                            <label class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors cursor-pointer">
                                <input type="checkbox" name="working_days[]" value="{{ $value }}"
                                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 dark:border-gray-600 dark:bg-gray-800"
                                       {{ in_array($value, $oldDays ?? []) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="work_start_time" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Jam Mulai</label>
                            <div class="mt-2">
                                <input type="time" name="work_start_time" id="work_start_time" 
                                       value="{{ old('work_start_time', $admin->work_start_time ? substr($admin->work_start_time, 0, 5) : '') }}"
                                       class="input-field w-full block">
                            </div>
                            @error('work_start_time')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="work_end_time" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Jam Selesai</label>
                            <div class="mt-2">
                                <input type="time" name="work_end_time" id="work_end_time" 
                                       value="{{ old('work_end_time', $admin->work_end_time ? substr($admin->work_end_time, 0, 5) : '') }}"
                                       class="input-field w-full block">
                            </div>
                            @error('work_end_time')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <span class="font-medium text-amber-600 dark:text-amber-400">Catatan:</span> Jika hari kerja atau jam tidak diisi, admin dapat login kapan saja (24/7).
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-x-4 border-t border-gray-100 dark:border-gray-700 pt-8">
                <a href="{{ route('admin.admins.index') }}" class="text-sm font-semibold leading-6 text-gray-900 dark:text-white hover:text-gray-700 dark:hover:text-gray-300">Batal</a>
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection
