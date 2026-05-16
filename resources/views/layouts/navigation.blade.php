<nav x-data="{ open: false }" class="bg-slate-900 border-b border-slate-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="text-white font-bold text-lg tracking-tight">COSMAS <span class="text-blue-400">SENTRY</span></span>
                    </a>
                </div>
                <!-- Navigation Links -->
                <div class="hidden space-x-1 sm:-my-px sm:ms-8 sm:flex items-center">
                    <a href="{{ route('dashboard') }}"
                       class="px-3 py-2 text-sm font-medium rounded transition {{ request()->routeIs('dashboard') ? 'text-white bg-slate-700' : 'text-slate-300 hover:text-white hover:bg-slate-700' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('inspections.upload') }}"
                       class="px-3 py-2 text-sm font-medium rounded transition {{ request()->routeIs('inspections.upload') ? 'text-white bg-blue-600' : 'text-blue-400 hover:text-white hover:bg-blue-600' }}">
                        + Inspect
                    </a>
                    <a href="{{ route('inspections.audit-log') }}"
                       class="px-3 py-2 text-sm font-medium rounded transition {{ request()->routeIs('inspections.audit-log') ? 'text-white bg-slate-700' : 'text-slate-300 hover:text-white hover:bg-slate-700' }}">
                        Audit Log
                    </a>
                    <a href="{{ route('roi') }}"
                       class="px-3 py-2 text-sm font-medium rounded transition {{ request()->routeIs('roi') ? 'text-white bg-slate-700' : 'text-slate-300 hover:text-white hover:bg-slate-700' }}">
                        ROI
                    </a>
                    <a href="{{ route('pipeline') }}"
                       class="px-3 py-2 text-sm font-medium rounded transition {{ request()->routeIs('pipeline') ? 'text-white bg-slate-700' : 'text-slate-300 hover:text-white hover:bg-slate-700' }}">
                        How It Works
                    </a>
                    <a href="{{ route('model.stats') }}"
                       class="px-3 py-2 text-sm font-medium rounded transition {{ request()->routeIs('model.stats') ? 'text-white bg-slate-700' : 'text-slate-300 hover:text-white hover:bg-slate-700' }}">
                        Model Stats
                    </a>
                </div>
            </div>
            <!-- User Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-slate-600 text-sm leading-4 font-medium rounded-md text-slate-300 bg-slate-800 hover:text-white hover:border-slate-400 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-slate-400 hover:text-white hover:bg-slate-700 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-slate-800">
        <div class="pt-2 pb-3 space-y-1 px-4">
            <a href="{{ route('dashboard') }}" class="block px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-700 rounded text-sm">Dashboard</a>
            <a href="{{ route('inspections.upload') }}" class="block px-3 py-2 text-blue-400 hover:text-white hover:bg-blue-600 rounded text-sm">+ New Inspection</a>
            <a href="{{ route('inspections.audit-log') }}" class="block px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-700 rounded text-sm">Audit Log</a>
            <a href="{{ route('roi') }}" class="block px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-700 rounded text-sm">ROI Calculator</a>
            <a href="{{ route('pipeline') }}" class="block px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-700 rounded text-sm">How It Works</a>
            <a href="{{ route('model.stats') }}" class="block px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-700 rounded text-sm">Model Stats</a>
        </div>
        <div class="pt-4 pb-1 border-t border-slate-700 px-4">
            <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
            <div class="font-medium text-sm text-slate-400">{{ Auth::user()->email }}</div>
            <div class="mt-3 space-y-1">
                <a href="{{ route('profile.edit') }}" class="block px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-700 rounded text-sm">Profile</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-3 py-2 text-slate-300 hover:text-white hover:bg-slate-700 rounded text-sm">Log Out</button>
                </form>
            </div>
        </div>
    </div>
</nav>
