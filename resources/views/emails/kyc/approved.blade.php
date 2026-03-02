<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-w-xl mx-auto; padding: 20px; text-align: center; border: 1px solid #eee; border-radius: 8px; margin: 0 auto; max-width: 600px; background-color: #fcfcfc;}
        .header { margin-bottom: 20px; }
        .content { background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #eaeaea; }
        .success-text { color: #16a34a; font-size: 24px; font-weight: bold; margin-bottom: 15px;}
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
            <div class="success-text">🎉 Selamat, Verifikasi Berhasil!</div>
            <p>Halo <strong>{{ $kyc->user->name }}</strong>,</p>
            <p>Verifikasi Identitas (KYC) Anda telah kami terima dan disetujui. Akun Anda sekarang telah tervalidasi sepenuhnya.</p>
            <p>Anda kini dapat menikmati semua layanan dan fitur secara maksimal di platform kami.</p>
            
            <a href="{{ url('/dashboard') }}" class="btn">Masuk ke Dashboard</a>
        </div>
        <div class="footer">
            <p>Terima kasih telah mempercayai layanan kami.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
