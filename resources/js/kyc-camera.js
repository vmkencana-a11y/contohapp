/**
 * KYC Camera Capture Module v2
 * 
 * Camera-based document capture with:
 * - TensorFlow.js/MediaPipe liveness auto-detection
 * - Nonce-protected frame submission
 * - Fullscreen immersive UI support
 */

class KycCamera {
    constructor(options = {}) {
        this.videoElement = options.videoElement;
        this.canvasElement = options.canvasElement;

        this.sessionId = null;
        this.challenges = [];
        this.currentChallengeIndex = 0;
        this.capturedFrames = {
            selfie: null, // Used for Selfie + ID
            id_card: null,
            left_side: null,
            right_side: null,
        };

        this.stream = null;
        this.facingMode = 'user';

        // Callbacks
        this.onStatusChange = options.onStatusChange || (() => { });
        this.onChallengeChange = options.onChallengeChange || (() => { });
        this.onError = options.onError || ((err) => console.error(err));
        this.onComplete = options.onComplete || (() => { });
    }

    /**
     * Start KYC session, load model, and initialize camera.
     */
    async start() {
        try {
            this.onStatusChange('Memulai sesi KYC...');

            // Start session
            const response = await fetch('/kyc/capture/start', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message);
            }

            this.sessionId = data.session_id;
            this.challenges = data.challenges;
            this.currentChallengeIndex = 0;

            // Initialize camera
            this.onStatusChange('Mengakses kamera...');
            await this.initCamera();

            this.onStatusChange('Siap. Ikuti instruksi.');
            return true;
        } catch (error) {
            this.onError(error.message);
            return false;
        }
    }

    /**
     * Initialize camera stream.
     */
    async initCamera(facingMode = 'user') {
        if (!window.isSecureContext) {
            throw new Error('Kamera hanya tersedia melalui HTTPS atau localhost.');
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error('Browser tidak mendukung akses kamera.');
        }

        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
        }

        const constraints = {
            video: {
                facingMode: facingMode,
                width: { ideal: 1280 },
                height: { ideal: 720 },
            },
            audio: false,
        };

        try {
            this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            this.facingMode = facingMode;

            if (this.videoElement) {
                this.videoElement.srcObject = this.stream;
                await this.videoElement.play();
            }
        } catch (error) {
            if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                throw new Error('Akses kamera ditolak. Klik ikon gembok di address bar untuk mengizinkan.');
            } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                throw new Error('Kamera tidak ditemukan.');
            } else if (error.name === 'NotReadableError') {
                throw new Error('Kamera sedang digunakan aplikasi lain.');
            } else if (error.name === 'OverconstrainedError') {
                this.stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                if (this.videoElement) {
                    this.videoElement.srcObject = this.stream;
                    await this.videoElement.play();
                }
                return;
            }
            throw new Error('Gagal mengakses kamera: ' + error.message);
        }
    }

    /**
     * Switch camera (front/back).
     */
    async switchCamera() {
        const newFacing = this.facingMode === 'user' ? 'environment' : 'user';
        await this.initCamera(newFacing);
    }



    /**
     * Submit a challenge frame to server.
     */
    async _submitChallengeFrame(challenge) {
        const nonceData = await this._fetchNonce();
        const frameData = this.captureFrame();

        const response = await fetch('/kyc/capture/frame', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                session_id: this.sessionId,
                nonce: nonceData.nonce,
                frame: frameData,
                type: 'selfie',
                challenge_id: challenge.id,
            }),
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message);
        }

        return data;
    }

    /**
     * Capture Selfie + ID (uses 'selfie' type).
     */
    async captureSelfie() {
        return this.captureDocument('selfie');
    }

    /**
     * Capture Left Side Face.
     */
    async captureLeftSide() {
        return this.captureDocument('left_side');
    }

    /**
     * Capture Right Side Face.
     */
    async captureRightSide() {
        return this.captureDocument('right_side');
    }

    /**
     * Capture ID card.
     */
    async captureIdCard() {
        if (this.facingMode === 'user') {
            try {
                await this.initCamera('environment');
            } catch {
                // Fallback to front camera
            }
        }
        return this.captureDocument('id_card');
    }

    /**
     * Capture a document frame.
     */
    async captureDocument(type) {
        if (!this.sessionId) throw new Error('Sesi tidak aktif');

        const nonceData = await this._fetchNonce();
        const frameData = this.captureFrame();

        const response = await fetch('/kyc/capture/frame', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                session_id: this.sessionId,
                nonce: nonceData.nonce,
                frame: frameData,
                type: type,
            }),
        });

        const data = await response.json();
        if (!data.success) throw new Error(data.message);

        this.capturedFrames[type] = true; // Mark as captured (path stored server-side)
        return true;
    }

    /**
     * Capture frame from video to canvas.
     */
    captureFrame() {
        if (!this.videoElement || !this.canvasElement) {
            throw new Error('Video/Canvas element not set');
        }

        const video = this.videoElement;
        const canvas = this.canvasElement;
        const ctx = canvas.getContext('2d');

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0);

        return canvas.toDataURL('image/jpeg', 0.85);
    }

    /**
     * Complete KYC submission.
     */
    async complete(idType, idNumber) {
        const required = ['selfie', 'id_card', 'left_side', 'right_side'];
        if (required.some(t => !this.capturedFrames[t])) {
            throw new Error('Harap lengkapi semua foto.');
        }

        this.onStatusChange('Mengirim data KYC...');

        const response = await fetch('/kyc/capture/complete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                session_id: this.sessionId,
                id_type: idType,
                id_number: idNumber,
            }),
        });

        const data = await response.json();

        if (!data.success) throw new Error(data.message);

        this.onStatusChange('KYC berhasil dikirim!');
        this.onComplete(data);
        this.stop();

        return data;
    }

    /**
     * Fetch a fresh nonce from server.
     */
    async _fetchNonce() {
        const response = await fetch(`/kyc/capture/nonce?session_id=${this.sessionId}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.getCsrfToken(),
            },
        });

        const data = await response.json();
        if (!data.success) throw new Error(data.message);
        return data;
    }

    /**
     * Stop camera and cleanup.
     */
    stop() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }

        if (this.videoElement) {
            this.videoElement.srcObject = null;
        }
    }

    /**
     * Get CSRF token.
     */
    getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    /**
     * Check if camera is available.
     */
    static async isAvailable() {
        if (!window.isSecureContext) return false;
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return false;

        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            return devices.some(d => d.kind === 'videoinput');
        } catch {
            return false;
        }
    }

    static getUnavailableReason() {
        if (!window.isSecureContext) return 'Halaman harus diakses via HTTPS atau localhost.';
        if (!navigator.mediaDevices) return 'Browser tidak mendukung akses kamera.';
        return 'Kamera tidak tersedia pada perangkat ini.';
    }
}

if (typeof window !== 'undefined') {
    window.KycCamera = KycCamera;
}
