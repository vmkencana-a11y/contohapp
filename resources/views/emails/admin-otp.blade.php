<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Admin OTP</title>
    <style>
        /* Reset styles */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }
        
        /* Client-specific Styles */
        #outlook a { padding: 0; }
        .ReadMsgBody { width: 100%; }
        .ExternalClass { width: 100%; }
        .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div { line-height: 100%; }
        
        /* Custom Styles */
        @media only screen and (max-width: 600px) {
            .container { width: 100% !important; }
            .content { padding: 20px !important; }
            .button { width: 100% !important; display: block !important; text-align: center !important; box-sizing: border-box !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif; background-color: #f5f5f7; color: #1d1d1f;">
    
    <!-- Wrapper Table -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f5f5f7;">
        <tr>
            <td style="padding: 40px 20px;">
                
                <!-- Container Table -->
                <table class="container" role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="padding: 30px; text-align: center; background: #0f4b80">
                            <svg width="150" height="36" viewBox="0 0 150 36" fill="none">
<path d="M46.2853 28.1124C45.016 28.1124 43.8773 27.8884 42.8693 27.4404C41.8613 26.9738 41.0586 26.3484 40.4613 25.5644C39.8826 24.7804 39.5653 23.9124 39.5093 22.9604H43.4573C43.532 23.5578 43.8213 24.0524 44.3253 24.4444C44.848 24.8364 45.492 25.0324 46.2573 25.0324C47.004 25.0324 47.5826 24.8831 47.9933 24.5844C48.4226 24.2858 48.6373 23.9031 48.6373 23.4364C48.6373 22.9324 48.376 22.5591 47.8533 22.3164C47.3493 22.0551 46.5373 21.7751 45.4173 21.4764C44.26 21.1964 43.308 20.9071 42.5613 20.6084C41.8333 20.3098 41.1986 19.8524 40.6573 19.2364C40.1346 18.6204 39.8733 17.7898 39.8733 16.7444C39.8733 15.8858 40.116 15.1018 40.6013 14.3924C41.1053 13.6831 41.8146 13.1231 42.7293 12.7124C43.6626 12.3018 44.7546 12.0964 46.0053 12.0964C47.8533 12.0964 49.328 12.5631 50.4293 13.4964C51.5306 14.4111 52.1373 15.6524 52.2493 17.2204H48.4973C48.4413 16.6044 48.18 16.1191 47.7133 15.7644C47.2653 15.3911 46.6586 15.2044 45.8933 15.2044C45.184 15.2044 44.6333 15.3351 44.2413 15.5964C43.868 15.8578 43.6813 16.2218 43.6813 16.6884C43.6813 17.2111 43.9426 17.6124 44.4653 17.8924C44.988 18.1538 45.8 18.4244 46.9013 18.7044C48.0213 18.9844 48.9453 19.2738 49.6733 19.5724C50.4013 19.8711 51.0266 20.3378 51.5493 20.9724C52.0906 21.5884 52.3706 22.4098 52.3893 23.4364C52.3893 24.3324 52.1373 25.1351 51.6333 25.8444C51.148 26.5538 50.4386 27.1138 49.5053 27.5244C48.5906 27.9164 47.5173 28.1124 46.2853 28.1124Z" fill="#FCFCFD"/>
<path d="M69.7471 19.7684C69.7471 20.3284 69.7098 20.8324 69.6351 21.2804H58.2951C58.3884 22.4004 58.7804 23.2778 59.4711 23.9124C60.1618 24.5471 61.0111 24.8644 62.0191 24.8644C63.4751 24.8644 64.5111 24.2391 65.1271 22.9884H69.3551C68.9071 24.4818 68.0484 25.7138 66.7791 26.6844C65.5098 27.6364 63.9511 28.1124 62.1031 28.1124C60.6098 28.1124 59.2658 27.7858 58.0711 27.1324C56.8951 26.4604 55.9711 25.5178 55.2991 24.3044C54.6458 23.0911 54.3191 21.6911 54.3191 20.1044C54.3191 18.4991 54.6458 17.0898 55.2991 15.8764C55.9524 14.6631 56.8671 13.7298 58.0431 13.0764C59.2191 12.4231 60.5724 12.0964 62.1031 12.0964C63.5778 12.0964 64.8938 12.4138 66.0511 13.0484C67.2271 13.6831 68.1324 14.5884 68.7671 15.7644C69.4204 16.9218 69.7471 18.2564 69.7471 19.7684ZM65.6871 18.6484C65.6684 17.6404 65.3044 16.8378 64.5951 16.2404C63.8858 15.6244 63.0178 15.3164 61.9911 15.3164C61.0204 15.3164 60.1991 15.6151 59.5271 16.2124C58.8738 16.7911 58.4724 17.6031 58.3231 18.6484H65.6871Z" fill="#FCFCFD"/>
<path d="M81.5124 27.8604L76.2484 21.2524V27.8604H72.3284V7.14042H76.2484V18.9284L81.4564 12.3484H86.5524L79.7204 20.1324L86.6084 27.8604H81.5124Z" fill="#FCFCFD"/>
<path d="M103.044 12.3484V27.8604H99.0959V25.9004C98.5919 26.5724 97.9293 27.1044 97.1079 27.4964C96.3053 27.8698 95.4279 28.0564 94.4759 28.0564C93.2626 28.0564 92.1893 27.8044 91.2559 27.3004C90.3226 26.7778 89.5852 26.0218 89.0439 25.0324C88.5212 24.0244 88.2599 22.8298 88.2599 21.4484V12.3484H92.1799V20.8884C92.1799 22.1204 92.4879 23.0724 93.1039 23.7444C93.7199 24.3978 94.5599 24.7244 95.6239 24.7244C96.7066 24.7244 97.5559 24.3978 98.1719 23.7444C98.7879 23.0724 99.0959 22.1204 99.0959 20.8884V12.3484H103.044Z" fill="#FCFCFD"/>
<path d="M113.52 28.1124C112.026 28.1124 110.682 27.7858 109.488 27.1324C108.293 26.4604 107.35 25.5178 106.66 24.3044C105.988 23.0911 105.652 21.6911 105.652 20.1044C105.652 18.5178 105.997 17.1178 106.688 15.9044C107.397 14.6911 108.358 13.7578 109.572 13.1044C110.785 12.4324 112.138 12.0964 113.632 12.0964C115.125 12.0964 116.478 12.4324 117.692 13.1044C118.905 13.7578 119.857 14.6911 120.548 15.9044C121.257 17.1178 121.612 18.5178 121.612 20.1044C121.612 21.6911 121.248 23.0911 120.52 24.3044C119.81 25.5178 118.84 26.4604 117.608 27.1324C116.394 27.7858 115.032 28.1124 113.52 28.1124ZM113.52 24.6964C114.229 24.6964 114.892 24.5284 115.508 24.1924C116.142 23.8378 116.646 23.3151 117.02 22.6244C117.393 21.9338 117.58 21.0938 117.58 20.1044C117.58 18.6298 117.188 17.5004 116.404 16.7164C115.638 15.9138 114.696 15.5124 113.576 15.5124C112.456 15.5124 111.513 15.9138 110.748 16.7164C110.001 17.5004 109.628 18.6298 109.628 20.1044C109.628 21.5791 109.992 22.7178 110.72 23.5204C111.466 24.3044 112.4 24.6964 113.52 24.6964Z" fill="#FCFCFD"/>
<path d="M128.771 15.5684V23.0724C128.771 23.5951 128.892 23.9778 129.135 24.2204C129.396 24.4444 129.826 24.5564 130.423 24.5564H132.243V27.8604H129.779C126.475 27.8604 124.823 26.2551 124.823 23.0444V15.5684H122.975V12.3484H124.823V8.51242H128.771V12.3484H132.243V15.5684H128.771Z" fill="#FCFCFD"/>
<path d="M133.775 20.0484C133.775 18.4804 134.083 17.0898 134.699 15.8764C135.333 14.6631 136.183 13.7298 137.247 13.0764C138.329 12.4231 139.533 12.0964 140.859 12.0964C142.016 12.0964 143.024 12.3298 143.883 12.7964C144.76 13.2631 145.46 13.8511 145.983 14.5604V12.3484H149.931V27.8604H145.983V25.5924C145.479 26.3204 144.779 26.9271 143.883 27.4124C143.005 27.8791 141.988 28.1124 140.831 28.1124C139.524 28.1124 138.329 27.7764 137.247 27.1044C136.183 26.4324 135.333 25.4898 134.699 24.2764C134.083 23.0444 133.775 21.6351 133.775 20.0484ZM145.983 20.1044C145.983 19.1524 145.796 18.3404 145.423 17.6684C145.049 16.9778 144.545 16.4551 143.911 16.1004C143.276 15.7271 142.595 15.5404 141.867 15.5404C141.139 15.5404 140.467 15.7178 139.851 16.0724C139.235 16.4271 138.731 16.9498 138.339 17.6404C137.965 18.3124 137.779 19.1151 137.779 20.0484C137.779 20.9818 137.965 21.8031 138.339 22.5124C138.731 23.2031 139.235 23.7351 139.851 24.1084C140.485 24.4818 141.157 24.6684 141.867 24.6684C142.595 24.6684 143.276 24.4911 143.911 24.1364C144.545 23.7631 145.049 23.2404 145.423 22.5684C145.796 21.8778 145.983 21.0564 145.983 20.1044Z" fill="#FCFCFD"/>
<path d="M31.7826 18.6788C32.446 20.2808 31.8885 22.0612 30.3062 23.3931L18.0177 33.7377C14.3963 36.7862 7.70845 35.982 6.19011 32.3154C5.5267 30.7134 6.08427 28.933 7.66654 27.6011L19.9551 17.2564C23.5764 14.208 30.2643 15.0122 31.7826 18.6788Z" fill="#F97316"/>
<path d="M25.8669 3.29797C26.5303 4.9 25.9728 6.68035 24.3905 8.01232L12.102 18.3569C8.48061 21.4054 1.79276 20.6012 0.274413 16.9346C-0.388994 15.3326 0.168573 13.5522 1.75084 12.2203L14.0394 1.87564C17.6607 -1.17285 24.3486 -0.368594 25.8669 3.29797Z" fill="#FFAE00"/>
</svg>
                            <p style="margin: 8px 0 0; font-size: 12px; color: rgba(255,255,255,0.7); letter-spacing: 1px;">ADMIN PANEL</p>
                        </td>
                    </tr>
                    
                    <!-- Main Content -->
                    <tr>
                        <td class="content" style="padding: 40px;">
                            
                            <!-- Greeting -->
                            <h2 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #1d1d1f; line-height: 1.3;">
                                Halo, {{ $name }}.
                            </h2>
                            
                            <!-- Body Text -->
                            <p style="margin: 0 0 20px; font-size: 14px; line-height: 1.4; color: #4a4a4a;">
                                Berikut adalah kode OTP untuk login admin. Gunakan kode ini untuk melanjutkan proses autentikasi.
                            </p>

                            <!-- OTP Box -->
                            <table style="margin: 0 0 20px;" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="padding: 20px; background-color: #f8f9fa; border-radius: 8px; text-align: center;">
                                        <p style="margin: 0 0 8px; font-size: 14px; color: #6b7280;">Kode OTP</p>
                                        <p style="margin: 0 0 8px; font-size: 36px; font-weight: 700; letter-spacing: 8px; color: #DC2626; font-family: 'Courier New', monospace;">
                                            {{ $otp }}
                                        </p>
                                        <p style="margin: 0; font-size: 13px; color: #6b7280;">
                                            Berlaku selama {{ $expiryMinutes }} menit
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Warning -->
                            <table style="margin: 0 0 20px;" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="padding: 16px; background-color: #FEE2E2; border-left: 4px solid #DC2626; border-radius: 0 8px 8px 0;">
                                        <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #991B1B;">
                                            🚨 <strong>PERINGATAN KEAMANAN</strong><br>
                                            Kode ini hanya untuk Anda. Jangan bagikan kepada siapapun termasuk rekan kerja.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Security Info -->
                            <table style="margin: 0;" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="padding: 16px; background-color: #f8f9fa; border-radius: 8px;">
                                        <p style="margin: 0 0 4px; font-size: 13px; font-weight: 600; color: #374151;">Informasi Keamanan:</p>
                                        <p style="margin: 0; font-size: 12px; line-height: 1.6; color: #6b7280;">
                                            • IP: {{ request()->ip() ?? 'Unknown' }}<br>
                                            • Waktu: {{ now()->format('d M Y H:i:s') }} WIB<br>
                                            • User Agent: {{ substr(request()->userAgent() ?? 'Unknown', 0, 50) }}...
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                        </td>
                    </tr>
                    
                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 40px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="border-bottom: 1px solid #e5e7eb; padding: 0;"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px; text-align: center; background-color: #fafafa;">
                            
                            <!-- Footer Text -->
                            <p style="margin: 0 0 8px; font-size: 13px; line-height: 1.5; color: #6b7280;">
                                Jika Anda tidak melakukan permintaan ini, segera hubungi administrator.
                            </p>
                            
                            <p style="margin: 0 0 8px; font-size: 13px; line-height: 1.5; color: #6b7280;">
                                <strong>PT. DIGITAL ARTHA NIAGA</strong><br>
                                Subang - Indonesia<br>
                                Email: support@sekuota.com | Whatsapp: 081000000000
                            </p>
                            
                            <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #9ca3af;">
                                {{ date('Y') }} &copy; SEKUOTA. All rights reserved.
                            </p>
                            
                        </td>
                    </tr>
                    
                </table>
                <!-- End Container Table -->
                
            </td>
        </tr>
    </table>
    <!-- End Wrapper Table -->
    
</body>
</html>
