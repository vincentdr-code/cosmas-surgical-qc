<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cosmas - Surgical Instrument Defect Detection</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .font-monospace {
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold">
                    C
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Cosmas</h1>
                <span class="text-sm text-gray-500 ml-2">v1.0</span>
            </div>
            <div class="flex gap-6">
                <a href="{{ route('inspections.upload') }}" class="text-gray-700 hover:text-blue-600 font-medium transition">
                    Upload
                </a>
                <a href="{{ route('inspections.audit-log') }}" class="text-gray-700 hover:text-blue-600 font-medium transition">
                    Audit Log
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="py-8">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-16 py-8">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-400">Cosmas © 2026 • AI-Powered Surgical Instrument Defect Detection</p>
            <p class="text-gray-500 text-sm mt-2">Powered by Claude Vision API</p>
        </div>
    </footer>
</body>
</html>
