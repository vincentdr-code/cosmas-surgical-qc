<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>COSMAS·DAMIAN // ACCESS CONTROL</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
    *, *::before, *::after { box-sizing: border-box; border-radius: 0 !important; margin: 0; padding: 0; }

    :root {
        --bg:       #060F1E;
        --navy:     #0B1F3A;
        --card:     #0D1A2E;
        --gold:     #C9963E;
        --gold-dim: rgba(201,150,62,0.18);
        --steel:    #4A7C9E;
        --text:     #E8EDF2;
        --muted:    #8A9BAE;
        --fail:     #E74C3C;
        --font:     'JetBrains Mono', 'IBM Plex Mono', 'Courier New', monospace;
    }

    html, body {
        background: var(--bg);
        color: var(--text);
        font-family: var(--font);
        font-size: 13px;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* CRT scanlines */
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

    /* Animated grid background */
    body::before {
        content: '';
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 0;
        background-image:
            linear-gradient(rgba(201,150,62,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(201,150,62,0.03) 1px, transparent 1px);
        background-size: 40px 40px;
    }

    .login-wrap {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        padding: 40px 20px;
    }

    /* Header wordmark */
    .wordmark {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 40px;
    }
    .wordmark-icon {
        width: 36px;
        height: 36px;
        border: 2px solid var(--gold);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .wordmark-icon span {
        color: var(--gold);
        font-size: 13px;
        font-weight: 700;
        line-height: 1;
    }
    .wordmark-text {
        font-size: 16px;
        font-weight: 700;
        color: var(--text);
        letter-spacing: 0.08em;
    }
    .wordmark-text em {
        color: var(--gold);
        font-style: normal;
    }

    /* Auth card */
    .auth-card {
        width: 100%;
        max-width: 420px;
        background: var(--card);
        border: 1px solid var(--gold-dim);
        border-top: 2px solid var(--gold);
        padding: 36px 32px 32px;
    }

    .card-header {
        margin-bottom: 28px;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--gold-dim);
    }
    .card-header-label {
        font-size: 9px;
        letter-spacing: 0.2em;
        color: var(--gold);
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 6px;
    }
    .card-header-label::before { content: '[ '; opacity: 0.7; }
    .card-header-label::after  { content: ' ]'; opacity: 0.7; }
    .card-header h1 {
        font-size: 18px;
        font-weight: 700;
        color: var(--text);
        letter-spacing: 0.04em;
    }
    .card-header p {
        font-size: 11px;
        color: var(--muted);
        margin-top: 4px;
    }

    /* Error block */
    .error-block {
        background: rgba(231,76,60,0.08);
        border: 1px solid rgba(231,76,60,0.3);
        border-left: 3px solid var(--fail);
        padding: 10px 14px;
        margin-bottom: 20px;
        font-size: 11px;
        color: var(--fail);
        letter-spacing: 0.03em;
    }

    /* Session status */
    .status-block {
        background: rgba(46,204,113,0.08);
        border: 1px solid rgba(46,204,113,0.25);
        border-left: 3px solid #2ECC71;
        padding: 10px 14px;
        margin-bottom: 20px;
        font-size: 11px;
        color: #2ECC71;
    }

    /* Form fields */
    .field {
        margin-bottom: 20px;
    }
    .field label {
        display: block;
        font-size: 10px;
        letter-spacing: 0.12em;
        color: var(--muted);
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 8px;
    }
    .field input {
        width: 100%;
        padding: 10px 12px;
        background: var(--bg);
        border: 1px solid var(--gold-dim);
        color: var(--text);
        font-family: var(--font);
        font-size: 13px;
        outline: none;
        transition: border-color 0.15s;
    }
    .field input::placeholder { color: var(--muted); opacity: 0.5; }
    .field input:focus {
        border-color: var(--gold);
        box-shadow: 0 0 0 1px var(--gold-dim);
    }
    .field input.is-error { border-color: var(--fail); }

    .field-error {
        font-size: 10px;
        color: var(--fail);
        margin-top: 5px;
        letter-spacing: 0.04em;
    }

    /* Remember + forgot row */
    .form-footer-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
    }
    .remember-label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-size: 11px;
        color: var(--muted);
    }
    .remember-label input[type="checkbox"] {
        width: 14px;
        height: 14px;
        accent-color: var(--gold);
        cursor: pointer;
    }
    .forgot-link {
        font-size: 11px;
        color: var(--muted);
        text-decoration: none;
        letter-spacing: 0.04em;
    }
    .forgot-link:hover { color: var(--gold); }

    /* Submit button */
    .btn-submit {
        width: 100%;
        padding: 12px;
        background: var(--gold);
        color: var(--bg);
        font-family: var(--font);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        border: none;
        cursor: pointer;
        transition: background 0.15s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .btn-submit:hover { background: #B8852D; }
    .btn-submit:active { background: #A8751D; }

    /* Footer classification bar */
    .footer-bar {
        margin-top: 28px;
        padding-top: 14px;
        border-top: 1px solid var(--gold-dim);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .footer-bar span {
        font-size: 9px;
        letter-spacing: 0.12em;
        color: var(--muted);
        opacity: 0.5;
        text-transform: uppercase;
    }
    .status-dot {
        width: 6px;
        height: 6px;
        background: #2ECC71;
        display: inline-block;
        margin-right: 6px;
        animation: blink 2s step-end infinite;
    }
    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.2; }
    }

    /* Below-card link */
    .below-card {
        margin-top: 20px;
        font-size: 10px;
        color: var(--muted);
        opacity: 0.5;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        text-align: center;
    }
    </style>
</head>
<body>
<div class="login-wrap">

    {{-- Wordmark --}}
    <div class="wordmark">
        <div class="wordmark-icon"><span>CS</span></div>
        <div class="wordmark-text">COSMAS·<em>DAMIAN</em></div>
    </div>

    {{-- Auth Card --}}
    <div class="auth-card">

        <div class="card-header">
            <div class="card-header-label">SECURE ACCESS TERMINAL</div>
            <h1>AUTHENTICATE</h1>
            <p>Surgical QC — Quality Control Platform</p>
        </div>

        {{-- Session status --}}
        @if (session('status'))
            <div class="status-block">{{ session('status') }}</div>
        @endif

        {{-- Auth errors (for Blade components) --}}
        @if ($errors->any())
            <div class="error-block">
                ⚠ {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            {{-- Email --}}
            <div class="field">
                <label for="email">OPERATOR ID (EMAIL)</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="operator@facility.com"
                    required
                    autofocus
                    autocomplete="username"
                    class="{{ $errors->has('email') ? 'is-error' : '' }}"
                >
                @error('email')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Password --}}
            <div class="field">
                <label for="password">ACCESS CODE</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••••••"
                    required
                    autocomplete="current-password"
                    class="{{ $errors->has('password') ? 'is-error' : '' }}"
                >
                @error('password')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Remember + Forgot --}}
            <div class="form-footer-row">
                <label class="remember-label">
                    <input type="checkbox" name="remember" id="remember-me" {{ old('remember') ? 'checked' : '' }}>
                    KEEP SESSION ACTIVE
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="forgot-link">FORGOT CODE?</a>
                @endif
            </div>

            {{-- Submit --}}
            <button type="submit" class="btn-submit">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <rect x="3" y="11" width="18" height="11" rx="0"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                AUTHENTICATE &amp; ENTER
            </button>
        </form>

        {{-- Footer bar --}}
        <div class="footer-bar">
            <span><span class="status-dot"></span>SYSTEM ONLINE</span>
            <span>SIC 3841 // FDA-ALIGNED</span>
            <span>v2.1</span>
        </div>
    </div>

    <div class="below-card">COSMAS·DAMIAN &copy; {{ date('Y') }} — AUTHORIZED PERSONNEL ONLY</div>

</div>
</body>
</html>
