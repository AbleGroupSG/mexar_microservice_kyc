<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard - KYC Microservice' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="drawer lg:drawer-open">
        <input id="drawer" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col">
            <!-- Navbar -->
            <div class="navbar bg-base-100 shadow-sm lg:hidden">
                <div class="flex-none">
                    <label for="drawer" class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-6 h-6 stroke-current">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </label>
                </div>
                <div class="flex-1">
                    <a href="{{ route('dashboard.home') }}" wire:navigate class="btn btn-ghost text-xl">KYC Dashboard</a>
                </div>
            </div>

            <!-- Page content -->
            <main class="flex-1 p-6 bg-base-200">
                {{ $slot }}
            </main>
        </div>

        <!-- Sidebar -->
        <div class="drawer-side">
            <label for="drawer" class="drawer-overlay"></label>
            <aside class="bg-base-100 w-80 min-h-screen shadow-xl">
                <!-- Logo/Title -->
                <div class="p-6 border-b border-base-200">
                    <h1 class="text-2xl font-bold text-primary">KYC Dashboard</h1>
                    <p class="text-sm text-base-content/70 mt-1">Microservice Management</p>
                </div>

                <!-- User Info -->
                <div class="p-4 border-b border-base-200">
                    <div class="flex items-center gap-3">
                        <div class="avatar placeholder">
                            <div class="bg-primary text-primary-content rounded-full w-10">
                                <span class="text-lg">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            </div>
                        </div>
                        <div>
                            <p class="font-semibold">{{ auth()->user()->name }}</p>
                            <p class="text-sm text-base-content/70">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation Menu -->
                <ul class="menu p-4 gap-2">
                    <li>
                        <a href="{{ route('dashboard.home') }}"
                           wire:navigate
                           class="{{ request()->routeIs('dashboard.home') ? 'active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.api-keys') }}"
                           wire:navigate
                           class="{{ request()->routeIs('dashboard.api-keys') ? 'active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                            API Keys
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.kyc-profiles') }}"
                           wire:navigate
                           class="{{ request()->routeIs('dashboard.kyc-profiles') ? 'active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            KYC Profiles
                        </a>
                    </li>
                </ul>

                <!-- Logout Button -->
                <div class="p-4 absolute bottom-0 w-full border-t border-base-200">
                    <form method="POST" action="{{ route('dashboard.logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-error w-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Logout
                        </button>
                    </form>
                </div>
            </aside>
        </div>
    </div>
</body>
</html>
