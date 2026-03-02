import './bootstrap';

// Alpine.js (CSP-compatible build — no eval() required)
import Alpine from '@alpinejs/csp';

window.Alpine = Alpine;

// ─── Alpine.data Components (CSP-safe: no inline eval) ─────────────────────

/**
 * Admin layout: sidebar toggle + dark mode with localStorage persistence.
 * Registered here instead of inline x-data to comply with CSP (no eval).
 */
Alpine.data('adminLayout', () => ({
    sidebarOpen: false,
    darkMode: localStorage.getItem('theme') === 'dark',

    init() {
        this.$watch('darkMode', val => {
            localStorage.setItem('theme', val ? 'dark' : 'light');
        });
    },
}));

/**
 * Settings page: KYC storage driver toggle + S3 connection test.
 * The async function cannot live in an HTML attribute under strict CSP.
 * The `kycDriver` initial value is injected via a data attribute on the form.
 */
Alpine.data('settingsForm', () => ({
    kycDriver: 'local',
    maintenanceMode: '0',
    testUrl: '',
    testingS3: false,
    testResult: null,

    init() {
        // Read the server-rendered initial value from the element's data attribute
        const initial = this.$el.dataset.kycDriver;
        if (initial) this.kycDriver = initial;

        const testUrl = this.$el.dataset.testUrl;
        if (typeof testUrl === 'string' && testUrl.length > 0) {
            this.testUrl = testUrl;
        }

        const maintenance = this.$el.dataset.maintenanceMode;
        if (maintenance === '1' || maintenance === '0') {
            this.maintenanceMode = maintenance;
        }
    },

    hasTestResult() {
        return this.testResult !== null;
    },

    isTestSuccess() {
        return this.testResult !== null && this.testResult.success === true;
    },

    isTestFailure() {
        return this.testResult !== null && this.testResult.success === false;
    },

    testResultMessage() {
        if (!this.testResult || typeof this.testResult.message !== 'string') {
            return '';
        }
        return this.testResult.message;
    },

    showMaintenanceEndTime() {
        return this.maintenanceMode === '1';
    },

    async testS3Connection() {
        this.testingS3 = true;
        this.testResult = null;
        try {
            const url = this.testUrl;
            if (!url) {
                throw new Error('Endpoint test S3 tidak ditemukan.');
            }
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
            });
            this.testResult = await response.json();
        } catch (error) {
            this.testResult = { success: false, message: 'Network error: ' + error.message };
        } finally {
            this.testingS3 = false;
        }
    },
}));

/**
 * Reusable OTP input component for 6-digit code fields.
 */
Alpine.data('otpInput', () => ({
    otp: '',
    loading: false,

    handleInput(e, index) {
        const value = e.target.value;
        if (!/^\d$/.test(value)) {
            e.target.value = '';
            return;
        }

        this.updateOtp();
        if (index < 5) {
            this.$refs['input' + (index + 1)].focus();
        }
    },

    handleKeydown(e, index) {
        if (e.key === 'Backspace' && !e.target.value && index > 0) {
            this.$refs['input' + (index - 1)].focus();
        }
    },

    handlePaste(e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const digits = paste.replace(/\D/g, '').slice(0, 6);

        for (let i = 0; i < digits.length; i++) {
            if (this.$refs['input' + i]) {
                this.$refs['input' + i].value = digits[i];
            }
        }

        this.updateOtp();
        const focusIndex = Math.min(digits.length, 5);
        this.$refs['input' + focusIndex]?.focus();
    },

    updateOtp() {
        let code = '';
        for (let i = 0; i < 6; i++) {
            code += this.$refs['input' + i]?.value || '';
        }
        this.otp = code;
    },
}));

/**
 * Reusable resend timer with localStorage persistence.
 */
Alpine.data('otpResendTimer', (storageKey = 'otp_countdown', initialSeconds = 60, autoStart = false) => ({
    countdown: 0,
    timerId: null,
    storageKey,
    initialSeconds,
    autoStart,

    init() {
        this.restoreCountdown();

        if (this.autoStart && this.countdown <= 0) {
            this.startCountdown();
        }
    },

    restoreCountdown() {
        const storedDeadline = localStorage.getItem(this.storageKey);
        if (!storedDeadline) {
            return;
        }

        const remaining = Math.ceil((parseInt(storedDeadline, 10) - Date.now()) / 1000);
        if (remaining > 0) {
            this.countdown = remaining;
            this.startTicking();
            return;
        }

        localStorage.removeItem(this.storageKey);
    },

    startCountdown(seconds = null) {
        this.countdown = Number.isInteger(seconds) ? seconds : this.initialSeconds;
        localStorage.setItem(this.storageKey, String(Date.now() + (this.countdown * 1000)));
        this.startTicking();
    },

    startTicking() {
        if (this.timerId) {
            clearInterval(this.timerId);
        }

        this.timerId = setInterval(() => {
            const storedDeadline = localStorage.getItem(this.storageKey);
            if (!storedDeadline) {
                this.stopCountdown();
                return;
            }

            const remaining = Math.ceil((parseInt(storedDeadline, 10) - Date.now()) / 1000);
            this.countdown = Math.max(0, remaining);

            if (this.countdown <= 0) {
                this.stopCountdown();
            }
        }, 1000);
    },

    stopCountdown() {
        this.countdown = 0;
        localStorage.removeItem(this.storageKey);

        if (this.timerId) {
            clearInterval(this.timerId);
            this.timerId = null;
        }
    },
}));


// Sekuota PPOB Global JavaScript

// OTP Input Handler
window.initOtpInput = function () {
    const inputs = document.querySelectorAll('.otp-input');

    inputs.forEach((input, index) => {
        // Auto-focus next input
        input.addEventListener('input', (e) => {
            const value = e.target.value;
            if (value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        // Handle backspace
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                inputs[index - 1].focus();
            }
        });

        // Handle paste
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').slice(0, 6);

            pastedData.split('').forEach((char, i) => {
                if (inputs[i]) {
                    inputs[i].value = char;
                }
            });

            // Focus last filled input or first empty
            const lastIndex = Math.min(pastedData.length - 1, inputs.length - 1);
            inputs[lastIndex].focus();
        });
    });
};

// Toast Notification
window.showToast = function (message, type = 'info', duration = 5000) {
    const container = document.getElementById('toast-container') || createToastContainer();

    const toast = document.createElement('div');
    toast.className = `toast toast-${type} transform translate-x-full`;
    const content = document.createElement('div');
    content.className = `flex items-center gap-3 p-4 rounded-lg shadow-lg ${getToastClass(type)}`;

    const text = document.createElement('div');
    text.className = 'flex-1';
    text.textContent = message;

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'text-current opacity-70 hover:opacity-100';
    closeBtn.setAttribute('aria-label', 'Close notification');
    closeBtn.innerHTML = `
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    `;
    closeBtn.addEventListener('click', () => toast.remove());

    content.appendChild(text);
    content.appendChild(closeBtn);
    toast.appendChild(content);

    container.appendChild(toast);

    // Animate in
    requestAnimationFrame(() => {
        toast.classList.remove('translate-x-full');
        toast.classList.add('translate-x-0');
    });

    // Auto remove
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, duration);
};

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'fixed top-4 right-4 z-50 flex flex-col gap-2';
    document.body.appendChild(container);
    return container;
}

function getToastClass(type) {
    const classes = {
        success: 'bg-success-500 text-white',
        error: 'bg-danger-500 text-white',
        warning: 'bg-warning-500 text-white',
        info: 'bg-primary-500 text-white',
    };
    return classes[type] || classes.info;
}

// Countdown Timer for OTP
window.initCountdown = function (elementId, seconds, onComplete) {
    const element = document.getElementById(elementId);
    let remaining = seconds;

    const timer = setInterval(() => {
        remaining--;
        const mins = Math.floor(remaining / 60);
        const secs = remaining % 60;
        element.textContent = `${mins}:${secs.toString().padStart(2, '0')}`;

        if (remaining <= 0) {
            clearInterval(timer);
            if (onComplete) onComplete();
        }
    }, 1000);

    return timer;
};

// Format currency (Indonesian Rupiah)
window.formatRupiah = function (amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
};

// Copy to clipboard
window.copyToClipboard = function (text, successMessage = 'Berhasil disalin!') {
    navigator.clipboard.writeText(text).then(() => {
        showToast(successMessage, 'success', 2000);
    }).catch(() => {
        showToast('Gagal menyalin', 'error', 2000);
    });
};

Alpine.start();
