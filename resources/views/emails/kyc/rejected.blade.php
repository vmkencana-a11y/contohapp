<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-w-xl mx-auto; padding: 20px; text-align: center; border: 1px solid #eee; border-radius: 8px; margin: 0 auto; max-width: 600px; background-color: #fcfcfc;}
        .header { margin-bottom: 20px; }
        .content { background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #eaeaea; }
        .error-text { color: #dc2626; font-size: 24px; font-weight: bold; margin-bottom: 15px;}
        .reason-box { background-color: #fef2f2; border: 1px solid #fecaca; padding: 15px; border-radius: 6px; margin: 15px 0; text-align: left;}
        .btn { display: inline-block; padding: 10px 20px; background-color: #4f46e5; color: #fff; text-decoration: none; border-radius: 5px; margin-top: 20px; font-weight: bold; }
        .footer { margin-top: 20px; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{ config('app.name') }}</h2>
        </div>
        <div class="content">
            <div class="error-text">Verifikasi Identitas Ditolak</div>
            <p>Halo <strong>{{ $kyc->user->name }}</strong>,</p>
            <p>Mohon maaf, pengajuan Verifikasi Identitas (KYC) Anda belum dapat kami setujui karena alasan berikut:</p>
            
            <div class="reason-box">
                <strong>Alasan Penolakan:</strong><br>
                {{ $kyc->rejection_reason ?? 'Dokumen tidak memenuhi kriteria validasi kami.' }}
            </div>
            
            <p>Silakan periksa kembali kelengkapan dan kejelasan dokumen Anda, lalu ajukan ulang verifikasi identitas melalui dashboard.</p>
            
            <a href="{{ url('/kyc') }}" class="btn">Ajukan Ulang KYC</a>
        </div>
        <div class="footer">
            <p>Jika Anda memiliki pertanyaan, silakan balas email ini atau hubungi tim bantuan kami.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
