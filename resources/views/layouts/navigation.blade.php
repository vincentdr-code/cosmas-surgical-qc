<nav style="background:#0B1F3A; border-bottom:1px solid rgba(201,150,62,0.25); position:sticky; top:0; z-index:100;">
    <div style="max-width:1440px; margin:0 auto; padding:0 32px; display:flex; align-items:center; justify-content:space-between; height:52px;">

        {{-- Logo --}}
        <a href="{{ route('dashboard') }}" style="text-decoration:none; display:flex; align-items:center; gap:10px;">
            <div style="width:28px; height:28px; border:2px solid #C9963E; display:flex; align-items:center; justify-content:center;">
                <span style="color:#C9963E; font-size:12px; font-weight:700; line-height:1;">CS</span>
            </div>
            <span style="font-family:'JetBrains Mono',monospace; font-size:13px; font-weight:700; color:#E8EDF2; letter-spacing:0.06em;">
                COSMAS·<span style="color:#C9963E;">DAMIAN</span>
            </span>
        </a>

        {{-- Nav Links --}}
        <div style="display:flex; align-items:center; gap:4px;">

            <a href="{{ route('home') }}"
               style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:600; color:{{ request()->routeIs('home') ? '#C9963E' : '#8A9BAE' }}; text-decoration:none; padding:6px 14px; letter-spacing:0.08em; text-transform:uppercase; border-bottom: {{ request()->routeIs('home') ? '2px solid #C9963E' : '2px solid transparent' }};">
                HOME
            </a>

            <a href="{{ route('dashboard') }}"
               style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:600; color:{{ request()->routeIs('dashboard') ? '#C9963E' : '#8A9BAE' }}; text-decoration:none; padding:6px 14px; letter-spacing:0.08em; text-transform:uppercase; border-bottom: {{ request()->routeIs('dashboard') ? '2px solid #C9963E' : '2px solid transparent' }};">
                DASHBOARD
            </a>

            @if(Route::has('inspections.upload'))
            <a href="{{ route('inspections.upload') }}"
               style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:600; color:{{ request()->routeIs('inspections.upload') ? '#C9963E' : '#8A9BAE' }}; text-decoration:none; padding:6px 14px; letter-spacing:0.08em; text-transform:uppercase; border-bottom: {{ request()->routeIs('inspections.upload') ? '2px solid #C9963E' : '2px solid transparent' }};">
                INSPECT
            </a>
            @endif

            @if(Route::has('inspections.audit-log'))
            <a href="{{ route('inspections.audit-log') }}"
               style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:600; color:{{ request()->routeIs('inspections.audit-log') ? '#C9963E' : '#8A9BAE' }}; text-decoration:none; padding:6px 14px; letter-spacing:0.08em; text-transform:uppercase; border-bottom: {{ request()->routeIs('inspections.audit-log') ? '2px solid #C9963E' : '2px solid transparent' }};">
                AUDIT LOG
            </a>
            @endif

            @if(Route::has('intel.index'))
            <a href="{{ route('intel.index') }}"
               style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:600; color:{{ request()->routeIs('intel.*') ? '#C9963E' : '#8A9BAE' }}; text-decoration:none; padding:6px 14px; letter-spacing:0.08em; text-transform:uppercase; border-bottom: {{ request()->routeIs('intel.*') ? '2px solid #C9963E' : '2px solid transparent' }};">
                DAMIAN
            </a>
            @endif
            @if(Route::has('settings.threshold'))
            <a href="{{ route('settings.threshold') }}"
               style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:600; color:{{ request()->routeIs('settings.*') ? '#C9963E' : '#8A9BAE' }}; text-decoration:none; padding:6px 14px; letter-spacing:0.08em; text-transform:uppercase; border-bottom: {{ request()->routeIs('settings.*') ? '2px solid #C9963E' : '2px solid transparent' }};">
                SETTINGS
            </a>
            @endif
        </div>

        {{-- User / Logout --}}
        <div style="display:flex; align-items:center; gap:16px;">
            @auth
            <span style="font-family:'JetBrains Mono',monospace; font-size:11px; color:#8A9BAE; letter-spacing:0.06em;">
                {{ Auth::user()->name }}
            </span>
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit"
                    style="background:transparent; border:1px solid rgba(201,150,62,0.3); color:#C9963E; font-family:'JetBrains Mono',monospace; font-size:10px; font-weight:700; letter-spacing:0.1em; padding:5px 12px; cursor:pointer; text-transform:uppercase;">
                    LOGOUT
                </button>
            </form>
            @endauth
        </div>

    </div>
</nav>
