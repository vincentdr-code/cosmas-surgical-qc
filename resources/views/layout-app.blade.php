<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'COSMAS SENTRY') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
    /* ═══════════════════════════════════════════════════════════════
       COSMAS SENTRY — DISCOMORPHISM DESIGN SYSTEM v3
       Near-black · Chrome mirror palette · Facet tile texture
    ═══════════════════════════════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; border-radius: 0 !important; }
    :root {
        --bg:        #080808;
        --navy:      #0e0e0e;
        --card:      #121212;
        --gold:      #C9963E;
        --gold-dim:  rgba(201,150,62,0.18);
        --gold-light:#E8C97A;
        --steel:     #4A7C9E;
        --ch-hi:     #f2f2f2;
        --ch-mid:    #b4b4b4;
        --ch-lo:     #686868;
        --text:      #E8EDF2;
        --muted:     #8A9BAE;
        --pass:      #4CAF82;
        --fail:      #E05555;
        --flag:      #F39C12;
        --font:      'JetBrains Mono', 'IBM Plex Mono', 'Courier New', monospace;
    }
    html, body { background: var(--bg) !important; color: var(--text) !important; font-family: var(--font) !important; font-size: 13px; margin: 0; padding: 0; min-height: 100vh; }
    body::after {
        content: ''; position: fixed; inset: 0; pointer-events: none; z-index: 9998;
        background-image: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0,0,0,0.045) 2px, rgba(0,0,0,0.045) 4px);
    }
    /* ── CHROME GRADIENT TEXT ── */
    .ch-text {
        background: linear-gradient(135deg, #f2f2f2 0%, #c8c8c8 25%, #f0f0f0 50%, #909090 75%, #c8c8c8 100%);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    }
    /* ── TAILWIND OVERRIDES ── */
    .bg-white, .bg-gray-100, .bg-gray-50, .bg-gray-200 { background: var(--card) !important; }
    .bg-gray-800 { background: var(--navy) !important; }
    .text-gray-900, .text-gray-800, .text-gray-700 { color: var(--text) !important; }
    .text-gray-600, .text-gray-500, .text-gray-400 { color: var(--muted) !important; }
    .text-blue-600, .text-blue-500 { color: var(--gold) !important; }
    .text-green-600 { color: var(--pass) !important; }
    .text-red-600   { color: var(--fail) !important; }
    .text-yellow-600, .text-yellow-500 { color: var(--flag) !important; }
    .border-gray-100, .border-gray-200, .border-gray-300 { border-color: rgba(255,255,255,0.07) !important; }
    .divide-y > * + * { border-top: 1px solid rgba(255,255,255,0.06) !important; }
    .rounded-full, .rounded-lg, .rounded-xl, .rounded-md { border-radius: 0 !important; }
    input, select, textarea { background: var(--bg) !important; border: 1px solid rgba(255,255,255,0.08) !important; color: var(--text) !important; font-family: var(--font) !important; }
    input::placeholder, textarea::placeholder { color: var(--muted) !important; opacity: 0.6; }
    input:focus, select:focus, textarea:focus { outline: 1px solid var(--gold) !important; box-shadow: 0 0 0 1px var(--gold-dim) !important; border-color: var(--gold) !important; }
    input[type="checkbox"], input[type="radio"] { background: transparent !important; border: 2px solid rgba(201,150,62,0.55) !important; width: 16px !important; height: 16px !important; cursor: pointer !important; flex-shrink: 0 !important; accent-color: var(--gold) !important; }
    input[type="checkbox"]:hover, input[type="radio"]:hover { border-color: var(--gold) !important; }
    input[type="checkbox"]:focus, input[type="radio"]:focus { outline: none !important; box-shadow: 0 0 0 2px var(--gold-dim) !important; border-color: var(--gold) !important; }
    button, .btn, [type="submit"], [type="button"], [type="reset"] { font-family: var(--font) !important; cursor: pointer; }
    .bg-blue-600, .bg-indigo-600, .bg-indigo-700 { background: var(--gold) !important; color: var(--bg) !important; }
    .bg-blue-600:hover { background: #d4a44a !important; }
    /* ── TABLE ── */
    table { border-collapse: collapse; width: 100%; }
    th { background: var(--navy) !important; color: var(--gold) !important; font-weight: 600; letter-spacing: 0.08em; border-bottom: 1px solid rgba(255,255,255,0.07) !important; padding: 10px 16px !important; text-align: left; }
    td { padding: 10px 16px !important; border-bottom: 1px solid rgba(255,255,255,0.05) !important; }
    tbody tr:hover { background: rgba(255,255,255,0.025) !important; }
    .shadow, .shadow-sm, .shadow-md { box-shadow: 0 1px 6px rgba(0,0,0,0.6) !important; }
    /* ── TACTICAL CARD — DISCOMORPHISM ── */
    .tac-card { background: var(--card); border: 1px solid rgba(255,255,255,0.07); padding: 20px; position: relative; overflow: hidden; }
    .tac-card::before {
        content: ''; position: absolute; inset: 0; pointer-events: none; z-index: 0;
        background:
            repeating-linear-gradient(0deg,  rgba(255,255,255,0.013) 0px, rgba(255,255,255,0.013) 1px, transparent 1px, transparent 16px),
            repeating-linear-gradient(90deg, rgba(255,255,255,0.013) 0px, rgba(255,255,255,0.013) 1px, transparent 1px, transparent 16px);
    }
    .tac-card-interactive::after {
        content: ''; position: absolute; inset: 0; pointer-events: none; z-index: 2;
        background: linear-gradient(135deg, transparent 35%, rgba(255,255,255,0.035) 50%, transparent 65%);
        transform: translateX(-120%); transition: transform 0.55s ease;
    }
    .tac-card-interactive:hover::after { transform: translateX(120%); }
    .tac-card > * { position: relative; z-index: 1; }
    /* ── CARD COMPONENTS ── */
    .tac-label { font-size: 10px; letter-spacing: 0.12em; color: var(--muted); text-transform: uppercase; font-weight: 600; }
    .tac-value { font-size: 28px; font-weight: 700; color: var(--text); margin: 6px 0 4px; line-height: 1; }
    .tac-value-chrome {
        font-size: 28px; font-weight: 700; margin: 6px 0 4px; line-height: 1;
        background: linear-gradient(135deg, #f2f2f2 0%, #c8c8c8 30%, #f0f0f0 50%, #909090 100%);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    }
    .tac-sub { font-size: 11px; color: var(--muted); }
    .tac-header { font-size: 11px; letter-spacing: 0.12em; color: var(--gold); text-transform: uppercase; font-weight: 600; margin-bottom: 16px; }
    .tac-header::before { content: '[ '; opacity: 0.6; }
    .tac-header::after  { content: ' ]'; opacity: 0.6; }
    .sec-label { font-size: 9px; letter-spacing: 0.18em; color: var(--gold); font-weight: 700; text-transform: uppercase; }
    .sec-label::before { content: '[ '; opacity: 0.7; }
    .sec-label::after  { content: ' ]'; opacity: 0.7; }
    /* ── BADGES ── */
    .badge-pass { background: rgba(74,207,130,0.12);  color: var(--pass); padding: 2px 8px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; }
    .badge-fail { background: rgba(224,85,85,0.12);   color: var(--fail); padding: 2px 8px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; }
    .badge-flag { background: rgba(243,156,18,0.12);  color: var(--flag); padding: 2px 8px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; }
    .badge-grey { background: rgba(138,155,174,0.12); color: var(--muted); padding: 2px 8px; font-size: 11px; font-weight: 700; }
    /* ── DROP ZONE ── */
    .drop-zone {
        border: 2px dashed rgba(255,255,255,0.1); background: var(--navy);
        padding: 48px; text-align: center; transition: border-color 0.2s, background 0.2s;
        cursor: pointer; position: relative; overflow: hidden;
    }
    .drop-zone::before {
        content: ''; position: absolute; inset: 0; pointer-events: none;
        background:
            repeating-linear-gradient(0deg,  rgba(255,255,255,0.008) 0px, rgba(255,255,255,0.008) 1px, transparent 1px, transparent 20px),
            repeating-linear-gradient(90deg, rgba(255,255,255,0.008) 0px, rgba(255,255,255,0.008) 1px, transparent 1px, transparent 20px);
    }
    .drop-zone > * { position: relative; z-index: 1; }
    .drop-zone:hover, .drop-zone.dragover { border-color: var(--gold); background: rgba(201,150,62,0.04); }
    /* ── SCROLLBAR ── */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: var(--bg); }
    ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }
    ::-webkit-scrollbar-thumb:hover { background: rgba(201,150,62,0.5); }
    </style>
    @stack('styles')
    <link rel="stylesheet" href="/css/cosmas-premium.css">
</head>
<body>
    @include('layouts.navigation')
    @if (isset($header))
    <header style="background:var(--navy); border-bottom:1px solid rgba(255,255,255,0.06); padding:14px 32px;">
        <div style="max-width:1440px; margin:0 auto; display:flex; align-items:center; justify-content:space-between;">
            {{ $header }}
        </div>
    </header>
    @endif
    <main style="max-width:1440px; margin:0 auto; padding:28px 32px;">
        {{ $slot }}
    </main>
    @stack('scripts')
</body>
</html>
