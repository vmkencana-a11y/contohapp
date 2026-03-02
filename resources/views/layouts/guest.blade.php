<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sekuota' }} - PPOB Terpercaya</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style @cspNonce>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900" x-data>
    @yield('content')
    
    {{-- Toast Notifications --}}
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    
    {{-- Flash Messages --}}
    @if(session('success'))
    <script @cspNonce>
        document.addEventListener('DOMContentLoaded', () => {
            window.Sekuota?.toast?.success('{{ session('success') }}');
        });
    </script>
    @endif
    @if(session('error'))
    <script @cspNonce>
        document.addEventListener('DOMContentLoaded', () => {
            window.Sekuota?.toast?.error('{{ session('error') }}');
        });
    </script>
    @endif
</body>
</html>
