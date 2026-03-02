@extends('layouts.app')

@section('content')
<div class="relative min-h-screen bg-gray-50 dark:bg-gray-900 overflow-hidden">
    
    {{-- Non-Fullscreen Content (Intro/Status) --}}
    <div class="max-w-xl mx-auto px-4 py-8 relative z-10 transition-opacity duration-500"
         x-data="{ show: true }" x-show="show"
         @start-kyc.window="show = false"
         @start-kyc-reset.window="show = true">
        
        <div class="text-center mb-10">
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-2 tracking-tight">Verifikasi Identitas</h1>
            <p class="text-gray-500 dark:text-gray-400">Amankan akun Anda dengan verifikasi KYC terenkripsi.</p>
        </div>

        {{-- Status Card --}}
        @if($kyc)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8 border border-gray-100 dark:border-gray-700 
                    transform transition-all hover:scale-[1.02]">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 rounded-full {{ $kyc->status->badgeClass() }} flex items-center justify-center shadow-inner">
                    @if($kyc->isVerified())
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    @elseif($kyc->isRejected())
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    @elseif($kyc->status->isProcessing())
                        <svg class="w-8 h-8 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    @else
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endif
                </div>
                <div class="flex-1">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ $kyc->status->label() }}</h2>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">
                        @if($kyc->isVerified())
                            Terverifikasi pada {{ $kyc->verified_at->format('d M Y') }}
                        @elseif($kyc->isRejected())
                            <span class="text-red-500 font-medium">Alasan: {{ $kyc->rejection_reason }}</span>
                        @elseif($kyc->status->isProcessing())
                            <span data-kyc-processing>Dokumen sedang diproses oleh AI...</span>
                        @else
                            Menunggu tinjauan tim verifikasi.
                        @endif
                    </p>
                </div>
            </div>
            @if($kyc->canResubmit())
                <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                     <button id="retry-kyc-btn" type="button"
                             class="w-full py-3 bg-red-50 text-red-600 hover:bg-red-100 rounded-xl font-semibold transition-colors">
                        Ulangi Verifikasi
                    </button>
                </div>
            @endif
        </div>
        @endif

        {{-- Start Action --}}
        @if($canSubmit)
        <div x-data="kycApp" x-init="init()" class="relative">
            
            {{-- Permission Error --}}
            <div x-show="!cameraAvailable && !loading" x-cloak 
                 class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded-r-xl mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700 dark:text-red-200" x-text="unavailableReason"></p>
                        <button @click="init()" class="mt-2 text-sm font-medium text-red-700 underline">Coba Lagi</button>
                    </div>
                </div>
            </div>

            {{-- Main Start Card --}}
            <div x-show="cameraAvailable && step === 'idle' && !loading" id="start-card"
                 class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl p-8 text-center border border-gray-200 dark:border-gray-700 relative overflow-hidden group">
                
                <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500"></div>

                <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-tr from-blue-100 to-purple-100 dark:from-blue-900/40 dark:to-purple-900/40 flex items-center justify-center relative">
                    <div class="absolute inset-0 rounded-full animate-ping opacity-20 bg-blue-400"></div>
                    <svg class="w-10 h-10 text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>

                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                    Siapkan Dokumen Anda
                </h3>
                <p class="text-gray-500 dark:text-gray-400 text-sm mb-8 leading-relaxed">
                    Kami membutuhkan foto selfie dan KTP Anda. Proses ini otomatis dan hanya memakan waktu kurang dari 1 menit.
                </p>

                <div class="space-y-4">
                    <div class="flex items-center gap-3 text-left text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg">
                        <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold">1</span>
                        Foto Tampak Samping Kiri & Kanan
                    </div>
                    <div class="flex items-center gap-3 text-left text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg">
                        <span class="w-6 h-6 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-xs font-bold">2</span>
                        Foto Selfie dengan KTP
                    </div>
                    <div class="flex items-center gap-3 text-left text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg">
                        <span class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs font-bold">3</span>
                        Foto KTP (Kamera Belakang)
                    </div>
                </div>

                <button id="start-btn" @click="startCamera()" 
                        class="w-full mt-8 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-2">
                    Mulai Sekarang
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </div>

            {{-- Loading State --}}
            <div x-show="loading && step === 'idle'" x-cloak class="flex flex-col items-center justify-center py-12">
                <div class="relative w-20 h-20">
                    <div class="absolute inset-0 rounded-full border-4 border-gray-200 dark:border-gray-700"></div>
                    <div class="absolute inset-0 rounded-full border-4 border-blue-500 border-t-transparent animate-spin"></div>
                </div>
                <p class="mt-4 text-gray-500 font-medium animate-pulse" x-text="statusMessage || 'Mempersiapkan sistem...'"></p>
            </div>


            {{-- ============================================== --}}
            {{-- ULTRA FULLSCREEN OVERLAY                       --}}
            {{-- ============================================== --}}
            <template x-teleport="body">
                <div x-show="step !== 'idle'" x-cloak
                     class="fixed inset-0 z-[9999] bg-black flex flex-col font-sans"
                     x-transition:enter="transition ease-out duration-500 transform"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-300 transform"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     @keydown.window.escape="cancel()">

                    {{-- TOP BAR (Glassmorphism) --}}
                <div class="absolute top-0 left-0 right-0 z-20 px-6 py-4 flex items-center justify-between safe-area-top">
                    {{-- Progress --}}
                    <div class="flex items-center gap-1.5 flex-1">
                        <template x-for="(s, i) in steps" :key="i">
                            <div class="h-1.5 flex-1 rounded-full transition-all duration-500 ease-out"
                                 :class="getStepProgressClass(s)"
                                >
                            </div>
                        </template>
                    </div>

                    {{-- Close Button --}}
                    <button @click="cancel()" class="ml-6 w-10 h-10 rounded-full bg-white/10 backdrop-blur-md flex items-center justify-center text-white hover:bg-white/20 transition-all">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- CAMERA AREA --}}
                <div class="flex-1 relative bg-gray-900 overflow-hidden" x-show="step !== 'form'">
                    <video x-ref="cameraVideo" autoplay playsinline muted
                           class="absolute inset-0 w-full h-full object-cover transition-transform duration-500"
                           :class="facingMode === 'user' ? 'scale-x-[-1]' : 'scale-x-100'"></video>
                    <canvas x-ref="cameraCanvas" class="hidden"></canvas>



                    {{-- Dark Gradient Overlay (Bottom) --}}
                    <div class="absolute inset-x-0 bottom-0 h-48 bg-gradient-to-t from-black/90 via-black/40 to-transparent pointer-events-none"></div>

                {{-- LEFT SIDE OVERLAY --}}
                    <div x-show="step === 'left_side'" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <div class="w-64 h-80 rounded-[3rem] border-4 border-dashed border-white/40 mb-8 relative">
                            <div class="absolute top-1/2 left-0 w-8 h-8 -translate-x-4 -translate-y-1/2 bg-blue-500 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
                            </div>
                        </div>
                        <div class="bg-black/50 backdrop-blur-md px-6 py-2 rounded-full border border-white/10">
                            <p class="text-white font-medium">⬅️ Foto Tampak Samping Kiri</p>
                        </div>
                    </div>

                    {{-- RIGHT SIDE OVERLAY --}}
                    <div x-show="step === 'right_side'" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <div class="w-64 h-80 rounded-[3rem] border-4 border-dashed border-white/40 mb-8 relative">
                            <div class="absolute top-1/2 right-0 w-8 h-8 translate-x-4 -translate-y-1/2 bg-blue-500 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </div>
                        <div class="bg-black/50 backdrop-blur-md px-6 py-2 rounded-full border border-white/10">
                            <p class="text-white font-medium">➡️ Foto Tampak Samping Kanan</p>
                        </div>
                    </div>

                    {{-- SELFIE + KTP OVERLAY --}}
                    <div x-show="step === 'selfie'" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <div class="relative w-72 h-80">
                            <div class="absolute top-0 right-0 w-48 h-48 rounded-full border-4 border-dashed border-white/40 translate-x-4"></div>
                            <div class="absolute bottom-0 left-0 w-48 h-32 rounded-xl border-4 border-dashed border-yellow-400/50 bg-yellow-400/10 -translate-x-4 flex items-center justify-center">
                                <span class="text-yellow-400 font-bold opacity-50">KTP</span>
                            </div>
                        </div>
                        <div class="mt-8 bg-black/50 backdrop-blur-md px-6 py-2 rounded-full border border-white/10">
                            <p class="text-white font-medium">🤳 Selfie sambil memegang KTP</p>
                        </div>
                    </div>

                    {{-- ID CARD OVERLAY --}}
                    <div x-show="step === 'id_card'" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <div class="w-[85%] aspect-[1.58] rounded-xl border-2 border-white/80 bg-white/5 relative overflow-hidden">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="w-full h-px bg-red-500/50"></div>
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="h-full w-px bg-red-500/50"></div>
                            </div>
                        </div>
                        <div class="mt-8 bg-black/50 backdrop-blur-md px-6 py-2 rounded-full border border-white/10">
                            <p class="text-white font-medium">🪪 Foto KTP (Kamera Belakang)</p>
                        </div>
                    </div>
                </div>

                {{-- FORM PANEL (Slide Up) --}}
                <div x-show="step === 'form'" class="flex-1 bg-gray-900 overflow-y-auto"
                     x-transition:enter="transition ease-out duration-300 transform"
                     x-transition:enter-start="translate-y-full"
                     x-transition:enter-end="translate-y-0">
                    
                    <div class="max-w-md mx-auto px-6 py-8 space-y-8">
                        <div class="text-center">
                            <h2 class="text-2xl font-bold text-white mb-2">Konfirmasi Data</h2>
                            <p class="text-gray-400 text-sm">Review foto dan lengkapi data identitas.</p>
                        </div>

                        {{-- Photo Review --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-gray-800 p-2 rounded-xl border border-gray-700">
                                <img :src="capturedLeftPreview" class="w-full h-24 object-cover rounded-lg mb-1 bg-black">
                                <p class="text-center text-xs text-green-400 font-medium">✓ Kiri</p>
                            </div>
                            <div class="bg-gray-800 p-2 rounded-xl border border-gray-700">
                                <img :src="capturedRightPreview" class="w-full h-24 object-cover rounded-lg mb-1 bg-black">
                                <p class="text-center text-xs text-green-400 font-medium">✓ Kanan</p>
                            </div>
                            <div class="bg-gray-800 p-2 rounded-xl border border-gray-700">
                                <img :src="capturedSelfiePreview" class="w-full h-24 object-cover rounded-lg mb-1 bg-black">
                                <p class="text-center text-xs text-green-400 font-medium">✓ Selfie</p>
                            </div>
                            <div class="bg-gray-800 p-2 rounded-xl border border-gray-700">
                                <img :src="capturedIdPreview" class="w-full h-24 object-cover rounded-lg mb-1 bg-black">
                                <p class="text-center text-xs text-green-400 font-medium">✓ KTP/ID</p>
                            </div>
                        </div>

                        {{-- Inputs --}}
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-3 uppercase tracking-wider">Jenis Identitas</label>
                                <div class="grid grid-cols-3 gap-3">
                                    <template x-for="type in ['ktp', 'sim', 'passport']">
                                        <button @click="idType = type"
                                            class="py-3 px-2 rounded-xl text-sm font-semibold transition-all border-2"
                                            :class="idType === type 
                                                ? 'bg-blue-600 border-blue-600 text-white shadow-lg shadow-blue-900/50' 
                                                : 'bg-gray-800 border-gray-800 text-gray-400 hover:bg-gray-700'">
                                            <span x-text="type.toUpperCase()"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-2 uppercase tracking-wider">Nomor Identitas</label>
                                <div class="relative">
                                    <input type="text" x-model="idNumber"
                                           class="w-full bg-gray-800 border-gray-700 rounded-xl pl-4 pr-4 py-4 text-white text-lg placeholder-gray-600 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                           placeholder="Cth: 3201234567890001">
                                    <div class="absolute right-4 top-4 text-gray-500">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CONTROLS BAR (Bottom) --}}
                <div class="relative z-30 bg-black/40 backdrop-blur-xl border-t border-white/10 px-6 py-6 safe-area-bottom">
                    
                    {{-- Status/Error Text --}}
                    {{-- Status/Error Text --}}
                    <div x-show="statusMessage || errorMessage" 
                         class="absolute -top-12 left-0 right-0 flex justify-center px-4">
                        <span class="px-4 py-2 rounded-full text-xs font-semibold backdrop-blur-md shadow-lg"
                              :class="errorMessage ? 'bg-red-500 text-white' : 'bg-white/10 text-white border border-white/10'"
                              x-text="errorMessage || statusMessage"></span>
                    </div>

                    <div class="flex items-center justify-between max-w-lg mx-auto">
                        
                        {{-- Switch Cam --}}
                        <div class="w-12 flex justify-start">
                            <button @click="switchCamera()" class="p-3 rounded-full bg-white/5 hover:bg-white/10 text-white transition-colors" x-show="step !== 'form'">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </button>
                        </div>

                        {{-- CENTER ACTION BUTTON --}}
                        <div class="flex-1 flex justify-center">
                            <template x-if="['left_side', 'right_side', 'selfie', 'id_card'].includes(step)">
                                <button @click="captureCurrentStep()"
                                        :disabled="loading"
                                        class="group relative w-20 h-20 flex items-center justify-center transition-all active:scale-95 z-50 cursor-pointer">
                                    <div class="absolute inset-0 bg-white/20 rounded-full group-hover:scale-110 transition-transform duration-300"></div>
                                    <div class="w-16 h-16 bg-white rounded-full border-[3px] border-black shadow-[0_0_0_2px_rgba(255,255,255,1)]"></div>
                                </button>
                            </template>

                            {{-- Submit Button --}}
                            <template x-if="step === 'form'">
                                <button @click="submitKyc()" 
                                        :disabled="loading || !idNumber || idNumber.length < 5"
                                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-700 disabled:text-gray-500 text-white font-bold py-4 rounded-2xl shadow-xl transition-all active:scale-95 flex items-center justify-center gap-2">
                                    <span x-show="!loading">Kirim Data</span>
                                    <svg x-show="!loading" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span x-show="loading" class="animate-spin w-5 h-5 border-2 border-white/30 border-t-white rounded-full"></span>
                                </button>
                            </template>
                        </div>

                        {{-- Spacer --}}
                        <div class="w-12"></div>
                    </div>
                </div>

                </div>
            </template>
        </div>
        @endif

    </div>
</div>

{{-- Scripts --}}
@vite(['resources/js/kyc-camera.js'])
<script @cspNonce>
function kycApp() {
    return {
        // App State
        cameraAvailable: false,
        step: 'idle', 
        loading: true,
        statusMessage: '',
        errorMessage: '',
        unavailableReason: '',
        
        // Data
        steps: [
            { id: 'left_side', label: 'Kiri' },
            { id: 'right_side', label: 'Kanan' },
            { id: 'selfie', label: 'Selfie+ID' },
            { id: 'id_card', label: 'KTP' },
            { id: 'form', label: 'Data' }
        ],
        completedSteps: [],
        
        // Camera
        kycCamera: null,
        challenges: [],
        facingMode: 'user',

        // Form
        capturedSelfiePreview: null,
        capturedIdPreview: null,
        capturedLeftPreview: null,
        capturedRightPreview: null,
        idType: 'ktp',
        idNumber: '',

        async init() {
            this.loading = true;
            this.errorMessage = '';
            
            // Allow time for scripts to load if needed
            await new Promise(r => setTimeout(r, 500));

            if (typeof KycCamera !== 'undefined') {
                this.cameraAvailable = await KycCamera.isAvailable();
                if (!this.cameraAvailable) {
                    this.unavailableReason = KycCamera.getUnavailableReason();
                }
            } else {
                this.errorMessage = 'Module kamera tidak termuat.';
                this.cameraAvailable = false;
            }
            this.loading = false;
        },

        getStepProgressClass(s) {
            const currentIdx = this.steps.findIndex(step => step.id === this.step);
            const stepIdx = this.steps.findIndex(step => step.id === s.id);
            
            if (this.completedSteps.includes(s.id)) return 'bg-green-500';
            if (stepIdx === currentIdx) return 'bg-white shadow-[0_0_10px_rgba(255,255,255,0.8)]';
            return 'bg-white/20';
        },

        async startCamera() {
            // Haptic feedback
            this._vibrate(50);
            
            this.$dispatch('start-kyc'); // Hide intro
            this.step = 'liveness';
            this.loading = true;
            this.errorMessage = '';
            
            this.kycCamera = new KycCamera({
                videoElement: this.$refs.cameraVideo,
                canvasElement: this.$refs.cameraCanvas,
                onStatusChange: (msg) => this.statusMessage = msg,
                onChallengeChange: (c) => {
                    this.challengeText = c.instruction;
                    this.challengeIcon = this._getIcon(c.id);
                },
                onChallengeDetected: () => {
                    this.challengeDetected = true;
                    this._vibrate([50, 50, 50]);
                },
                onError: (err) => {
                    console.error('KYC Camera Error:', err);
                    this.errorMessage = err;
                },
                onComplete: () => window.location.reload(),
            });

            const success = await this.kycCamera.start();
            if (!success) {
                console.warn('Camera start failed, resetting UI');
                this.step = 'idle';
                this.loading = false;
                this.$dispatch('start-kyc-reset');
                return;
            }

            this.step = 'left_side';
            this.statusMessage = 'Foto Tampak Samping Kiri';
            this._vibrate(100);
            this.loading = false; // Enable UI
        },

        async captureCurrentStep() {
            if (this.loading) return;

            this._vibrate(50);
            this.loading = true;
            this.errorMessage = '';
            this.statusMessage = 'Memproses...'; // Visual feedback

            try {
                // Check camera readiness
                if (!this.kycCamera || !this.kycCamera.stream) {
                    throw new Error('Kamera tidak aktif. Coba reload.');
                }

                console.log('Capturing step:', this.step);

                if (this.step === 'left_side') {
                    this.statusMessage = 'Mengambil foto kiri...';
                    this.capturedLeftPreview = this.kycCamera.captureFrame();
                    await this.kycCamera.captureLeftSide();
                    this.completedSteps.push('left_side');
                    
                    this.statusMessage = 'Berhasil! Lanjut ke kanan...';
                    setTimeout(() => {
                        this.step = 'right_side';
                        this.statusMessage = 'Foto Tampak Samping Kanan';
                        this.loading = false;
                    }, 500);
                }
                else if (this.step === 'right_side') {
                    this.statusMessage = 'Mengambil foto kanan...';
                    this.capturedRightPreview = this.kycCamera.captureFrame();
                    await this.kycCamera.captureRightSide();
                    this.completedSteps.push('right_side');

                    this.statusMessage = 'Berhasil! Siapkan selfie...';
                    setTimeout(() => {
                        this.step = 'selfie';
                        this.statusMessage = 'Foto Selfie dengan KTP';
                        this.loading = false;
                    }, 500);
                }
                else if (this.step === 'selfie') {
                    this.statusMessage = 'Mengambil selfie...';
                    this.capturedSelfiePreview = this.kycCamera.captureFrame();
                    await this.kycCamera.captureSelfie();
                    this.completedSteps.push('selfie');

                    this.step = 'id_card';
                    this.statusMessage = 'Foto KTP (Kamera Belakang)';
                    
                    try { 
                        await this.switchCamera(); 
                    } catch (e) {
                        console.warn('Switch camera failed', e);
                    }
                    this.loading = false;
                }
                else if (this.step === 'id_card') {
                    this.statusMessage = 'Mengambil foto KTP...';
                    this.capturedIdPreview = this.kycCamera.captureFrame();
                    await this.kycCamera.captureIdCard();
                    this.completedSteps.push('id_card');

                    this.step = 'form';
                     this.loading = false;
                }
            } catch (e) {
                console.error('Capture error:', e);
                this.errorMessage = e.message || 'Gagal mengambil gambar. Coba lagi.';
                this.loading = false;
            }
        },

        async submitKyc() {
            this.loading = true;
            try {
                await this.kycCamera.complete(this.idType, this.idNumber);
                if (this.kycCamera) this.kycCamera.stop();
            } catch (e) {
                this.errorMessage = e.message;
            }
            this.loading = false;
        },

        async switchCamera() {
            if (!this.kycCamera) return;
            await this.kycCamera.switchCamera();
            this.facingMode = this.facingMode === 'user' ? 'environment' : 'user';
        },

        cancel() {
            if (this.kycCamera) this.kycCamera.stop();
            this.step = 'idle';
            this.$dispatch('start-kyc-reset'); // Show intro again if needed
            window.location.reload(); // Cleanest reset
        },

        _getIcon(id) {
            const icons = { 
                blink: '👀', 
                turn_left: '⬅️', 
                turn_right: '➡️', 
                smile: '😁', 
                nod: '⬇️' 
            };
            return icons[id] || '🎯';
        },
        
        _vibrate(pattern) {
            if (navigator && navigator.vibrate) {
                navigator.vibrate(pattern);
            }
        }
    };
}

document.addEventListener('alpine:init', () => {
    Alpine.data('kycApp', kycApp);
});

document.addEventListener('DOMContentLoaded', () => {
    const retryBtn = document.getElementById('retry-kyc-btn');
    if (retryBtn) {
        retryBtn.addEventListener('click', () => {
            const startBtn = document.getElementById('start-btn');
            if (startBtn) startBtn.click();
        });
    }
});
</script>

<style @cspNonce>
.safe-area-top { padding-top: max(1rem, env(safe-area-inset-top)); }
.safe-area-bottom { padding-bottom: max(1rem, env(safe-area-inset-bottom)); }
[x-cloak] { display: none !important; }
</style>

{{-- Auto-poll when status is PROCESSING --}}
@if($kyc && $kyc->status->isProcessing())
<script @cspNonce>
(function() {
    const poll = setInterval(async () => {
        try {
            const res = await fetch('{{ route("kyc.status") }}', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (data.status && data.status !== 'processing') {
                clearInterval(poll);
                window.location.reload();
            }
        } catch (e) {
            console.warn('Polling error:', e);
        }
    }, 3000);
    // Stop polling after 5 minutes
    setTimeout(() => clearInterval(poll), 300000);
})();
</script>
@endif
@endsection
