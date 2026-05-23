<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'COSMAS DAMIAN') }}</title>

    {{-- Google Fonts: JetBrains Mono --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
    /* ═══════════════════════════════════════════════════
       TACTICAL TELEMETRY DESIGN SYSTEM — COSMAS DAMIAN
       Navy #0B1F3A · Gold #C9963E · Steel #4A7C9E
    ═══════════════════════════════════════════════════ */

    *, *::before, *::after {
        box-sizing: border-box;
        border-radius: 0 !important;
    }

    :root {
        --bg:      #060F1E;
        --navy:    #0B1F3A;
        --card:    #0D1A2E;
        --gold:    #C9963E;
        --gold-dim: rgba(201,150,62,0.18);
        --steel:   #4A7C9E;
        --text:    #E8EDF2;
        --muted:   #8A9BAE;
        --pass:    #2ECC71;
        --fail:    #E74C3C;
        --flag:    #F39C12;
        --font:    'JetBrains Mono', 'IBM Plex Mono', 'Courier New', monospace;
    }

    html, body {
        background: var(--bg) !important;
        color: var(--text) !important;
        font-family: var(--font) !important;
        font-size: 13px;
        margin: 0; padding: 0;
        min-height: 100vh;
    }

    /* CRT scanline overlay */
    body::after {
        content: '';
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 9998;
        background-image: repeating-linear-gradient(
            0deg, transparent, transparent 2px,
            rgba(0,0,0,0.04) 2px, rgba(0,0,0,0.04) 4px
        );
    }

    /* Override Tailwind white/grey backgrounds */
    .bg-white, .bg-gray-100, .bg-gray-50, .bg-gray-200 {
        background: var(--card) !important;
    }
    .bg-gray-800 { background: var(--navy) !important; }

    /* Text color overrides */
    .text-gray-900, .text-gray-800, .text-gray-700 { color: var(--text) !important; }
    .text-gray-600, .text-gray-500, .text-gray-400 { color: var(--muted) !important; }
    .text-blue-600, .text-blue-500 { color: var(--gold) !important; }
    .text-green-600 { color: var(--pass) !important; }
    .text-red-600   { color: var(--fail) !important; }
    .text-yellow-600, .text-yellow-500 { color: var(--flag) !important; }

    /* Border overrides */
    .border-gray-100, .border-gray-200, .border-gray-300 {
        border-color: var(--gold-dim) !important;
    }
    .divide-y > * + * { border-top: 1px solid var(--gold-dim) !important; }
    .divide-gray-50 > * + * { border-color: var(--gold-dim) !important; }

    /* Rounded pill badges → square */
    .rounded-full, .rounded-lg, .rounded-xl, .rounded-md {
        border-radius: 0 !important;
    }

    /* Inputs & textareas */
    input, select, textarea {
        background: var(--bg) !important;
        border: 1px solid var(--gold-dim) !important;
        color: var(--text) !important;
        font-family: var(--font) !important;
    }
    input::placeholder, textarea::placeholder { color: var(--muted) !important; }
    input:focus, select:focus, textarea:focus {
        outline: 1px solid var(--gold) !important;
        box-shadow: none !important;
        border-color: var(--gold) !important;
    }

    /* Buttons */
    button, .btn, [type="submit"], [type="button"], [type="reset"] {
        font-family: var(--font) !important;
        cursor: pointer;
    }
    .bg-blue-600, .bg-indigo-600, .bg-indigo-700 {
        background: var(--gold) !important;
        color: var(--bg) !important;
    }
    .bg-blue-600:hover {
        background: #B8852D !important;
    }

    /* Table */
    table { border-collapse: collapse; width: 100%; }
    th {
        background: var(--navy) !important;
        color: var(--gold) !important;
        font-weight: 600;
        letter-spacing: 0.08em;
        border-bottom: 1px solid var(--gold-dim) !important;
        padding: 10px 16px !important;
        text-align: left;
    }
    td { padding: 10px 16px !important; border-bottom: 1px solid var(--gold-dim) !important; }
    tbody tr:hover { background: rgba(201,150,62,0.04) !important; }

    /* Shadow overrides */
    .shadow, .shadow-sm, .shadow-md {
        box-shadow: 0 1px 4px rgba(0,0,0,0.4) !important;
    }

    /* Tactical card style */
    .tac-card {
        background: var(--card);
        border-top: 2px solid var(--gold-dim);
        border: 1px solid var(--gold-dim);
        padding: 20px;
    }
    .tac-label {
        font-size: 10px;
        letter-spacing: 0.12em;
        color: var(--muted);
        text-transform: uppercase;
        font-weight: 600;
    }
    .tac-value {
        font-size: 28px;
        font-weight: 700;
        color: var(--text);
        margin: 6px 0 4px;
        line-height: 1;
    }
    .tac-sub { font-size: 11px; color: var(--muted); }
    .tac-header {
        font-size: 11px;
        letter-spacing: 0.12em;
        color: var(--gold);
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 16px;
    }
    .tac-header::before { content: '[ '; opacity: 0.6; }
    .tac-header::after  { content: ' ]'; opacity: 0.6; }

    /* Badge variants */
    .badge-pass { background: rgba(46,204,113,0.15); color: var(--pass); padding: 2px 8px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; }
    .badge-fail { background: rgba(231,76,60,0.15);  color: var(--fail); padding: 2px 8px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; }
    .badge-flag { background: rgba(243,156,18,0.15); color: var(--flag); padding: 2px 8px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; }
    .badge-grey { background: rgba(138,155,174,0.15); color: var(--muted); padding: 2px 8px; font-size: 11px; font-weight: 700; }

    /* Upload drop zone */
    .drop-zone {
        border: 2px dashed var(--gold-dim);
        background: var(--navy);
        padding: 48px;
        text-align: center;
        transition: border-color 0.2s, background 0.2s;
        cursor: pointer;
    }
    .drop-zone:hover, .drop-zone.dragover {
        border-color: var(--gold);
        background: rgba(201,150,62,0.05);
    }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: var(--bg); }
    ::-webkit-scrollbar-thumb { background: var(--gold-dim); }
    ::-webkit-scrollbar-thumb:hover { background: var(--gold); }
    </style>

    @stack('styles')
    <link rel="stylesheet" href="/css/cosmas-premium.css">
</head>

<body>
    {{-- Navigation --}}
    @include('layouts.navigation')

    {{-- Page Heading --}}
    @if (isset($header))
    <header style="background:var(--navy); border-bottom:1px solid var(--gold-dim); padding:14px 32px;">
        <div style="max-width:1440px; margin:0 auto; display:flex; align-items:center; justify-content:space-between;">
            {{ $header }}
        </div>
    </header>
    @endif

    {{-- Main Content --}}
    <main style="max-width:1440px; margin:0 auto; padding:28px 32px;">
        {{ $slot }}
    </main>

    @stack('scripts')
</body>
</html>
