<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Maintenance Mode</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style @cspNonce>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen text-gray-800">

<div class="max-w-xl w-full text-center px-4" x-data="maintenanceCountdown('{{ $endTime }}')">
    <div class="mb-8 flex justify-center">
        <!-- Logo / Icon -->
        <div class="h-24 w-24 bg-indigo-100 rounded-full flex items-center justify-center animate-pulse">
            <svg class="h-12 w-12 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </div>
    </div>

    <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight sm:text-5xl">Sistem Sedang Diperbarui</h1>
    <p class="mt-4 text-base text-gray-500">
        Saat ini kami sedang melakukan pemeliharaan rutin untuk meningkatkan layanan. Mohon maaf atas ketidaknyamanan ini. Silakan kembali lagi nanti.
    </p>

    @if($endTime)
    <div class="mt-10" x-show="!isExpired">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Estimasi Pemeliharaan Selesai</h3>
        
        <div class="flex justify-center gap-3 sm:gap-4">
            <div class="flex flex-col items-center">
                <div class="w-16 h-16 sm:w-20 sm:h-20 bg-white rounded-2xl shadow-sm border border-gray-100 flex items-center justify-center">
                    <span class="text-2xl sm:text-3xl font-bold text-indigo-600" x-text="days">00</span>
                </div>
                <span class="mt-2 text-xs font-medium text-gray-500">Hari</span>
            </div>
            <div class="text-2xl font-bold text-gray-300 mt-4 sm:mt-5">:</div>
            
            <div class="flex flex-col items-center">
                <div class="w-16 h-16 sm:w-20 sm:h-20 bg-white rounded-2xl shadow-sm border border-gray-100 flex items-center justify-center">
                    <span class="text-2xl sm:text-3xl font-bold text-indigo-600" x-text="hours">00</span>
                </div>
                <span class="mt-2 text-xs font-medium text-gray-500">Jam</span>
            </div>
            <div class="text-2xl font-bold text-gray-300 mt-4 sm:mt-5">:</div>
            
            <div class="flex flex-col items-center">
                <div class="w-16 h-16 sm:w-20 sm:h-20 bg-white rounded-2xl shadow-sm border border-gray-100 flex items-center justify-center">
                    <span class="text-2xl sm:text-3xl font-bold text-indigo-600" x-text="minutes">00</span>
                </div>
                <span class="mt-2 text-xs font-medium text-gray-500">Menit</span>
            </div>
            <div class="text-2xl font-bold text-gray-300 mt-4 sm:mt-5">:</div>
            
            <div class="flex flex-col items-center">
                <div class="w-16 h-16 sm:w-20 sm:h-20 bg-white rounded-2xl shadow-sm border border-gray-100 flex items-center justify-center">
                    <span class="text-2xl sm:text-3xl font-bold text-indigo-600" x-text="seconds">00</span>
                </div>
                <span class="mt-2 text-xs font-medium text-gray-500">Detik</span>
            </div>
        </div>
    </div>
    <div class="mt-10 p-4 bg-green-50 rounded-lg text-green-700 font-medium" x-show="isExpired" x-cloak>
        Pemeliharaan hampir selesai. Silakan periksa kembali dalam beberapa saat!
    </div>
    @endif

    <div class="mt-12 flex items-center justify-center gap-x-6">
        <a href="mailto:{{ \App\Models\SystemSetting::getValue('site.email', 'support@sekuota.com') }}" class="text-sm font-semibold text-gray-900 border border-gray-200 bg-white px-5 py-2.5 rounded-full hover:bg-gray-50 transition-colors shadow-sm">
            Hubungi Bantuan <span aria-hidden="true">&rarr;</span>
        </a>
    </div>
</div>

<script @cspNonce>
    document.addEventListener('alpine:init', () => {
        Alpine.data('maintenanceCountdown', (endTime) => ({
            endTime: new Date(endTime).getTime(),
            now: new Date().getTime(),
            isExpired: false,
            days: '00',
            hours: '00',
            minutes: '00',
            seconds: '00',
            
            init() {
                if (!endTime) {
                    this.isExpired = true;
                    return;
                }
                
                this.updateClock();
                setInterval(() => {
                    this.updateClock();
                }, 1000);
            },
            
            updateClock() {
                this.now = new Date().getTime();
                let distance = this.endTime - this.now;
                
                if (distance < 0) {
                    this.isExpired = true;
                    return;
                }
                
                this.days = String(Math.floor(distance / (1000 * 60 * 60 * 24))).padStart(2, '0');
                this.hours = String(Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))).padStart(2, '0');
                this.minutes = String(Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
                this.seconds = String(Math.floor((distance % (1000 * 60)) / 1000)).padStart(2, '0');
            }
        }))
    })
</script>
</body>
</html>
