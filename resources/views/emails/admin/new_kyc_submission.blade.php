<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-w-xl mx-auto; padding: 20px; border: 1px solid #eee; border-radius: 8px; margin: 0 auto; max-width: 600px; background-color: #fcfcfc;}
        .header { margin-bottom: 20px; text-align: center; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }
        .content { background: #fff; padding: 25px; border-radius: 8px; border: 1px solid #eaeaea; }
        .title { color: #111827; font-size: 20px; font-weight: bold; margin-bottom: 15px;}
        .info-table { width: 100%; border-collapse: collapse; margin: 15px 0;}
        .info-table th { text-align: left; padding: 8px; border-bottom: 1px solid #f3f4f6; color: #6b7280; font-weight: normal; width: 40%;}
        .info-table td { padding: 8px; border-bottom: 1px solid #f3f4f6; color: #111827; font-weight: 500;}
        .btn { display: block; text-align: center; padding: 12px 20px; background-color: #4f46e5; color: #fff; text-decoration: none; border-radius: 5px; margin-top: 25px; font-weight: bold; }
        .footer { margin-top: 20px; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{ config('app.name') }} Admin Alert</h2>
        </div>
        <div class="content">
            <div class="title">Pengajuan KYC Baru Membutuhkan Review</div>
            <p>Halo Admin,</p>
            <p>Seorang pengguna telah menyelesaikan proses unggah dokumen identitas (KYC) dan membutuhkan review/persetujuan Anda.</p>
            
            <table class="info-table">
                <tr>
                    <th>Nama Pengguna</th>
                    <td>{{ $kyc->user->name }}</td>
                </tr>
                <tr>
                    <th>Email Pengguna</th>
                    <td>{{ $kyc->user->email }}</td>
                </tr>
                <tr>
                    <th>Waktu Pengajuan</th>
                    <td>{{ $kyc->created_at->format('d/m/Y H:i:s') }}</td>
                </tr>
                <tr>
                    <th>Jenis Identitas</th>
                    <td style="text-transform: uppercase;">{{ $kyc->id_type }}</td>
                </tr>
            </table>
            
            <a href="{{ url('/admin/kyc/' . $kyc->id) }}" class="btn">Review Dokumen Sekarang</a>
        </div>
        <div class="footer">
            <p>Pesan otomatis dari sistem Notifikasi Admin {{ config('app.name') }}.</p>
        </div>
    </div>
</body>
</html>
