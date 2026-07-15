<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Payroll System')</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
            <script>
                tailwind.config = {
                    theme: {
                        extend: {
                            fontFamily: {
                                sans: ['Inter', 'sans-serif'],
                            },
                        }
                    }
                }
            </script>
        @endif

        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

        @stack('head')
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
        <div class="flex h-screen overflow-hidden">
            @include('layouts.navigation')

            <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
                @include('layouts.topbar')

                @php
                    $bottomPadding = auth()->check() && auth()->user()->hasRole('employee') ? 'pb-28' : 'pb-8';
                @endphp

                <main class="w-full max-w-7xl mx-auto px-4 py-4 sm:px-6 sm:py-6 lg:px-8 lg:py-8 {{ $bottomPadding }}">
                    @hasSection('header')
                        <div class="mb-6">
                            @yield('header')
                        </div>
                    @endif

                    @if(session('success'))
                        <x-alert type="success" :message="session('success')" dismissable />
                    @endif
                    @if(session('error'))
                        <x-alert type="error" :message="session('error')" dismissable />
                    @endif
                    @if(session('warning'))
                        <x-alert type="warning" :message="session('warning')" dismissable />
                    @endif
                    @if(session('info'))
                        <x-alert type="info" :message="session('info')" dismissable />
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>

        @auth
            @if(auth()->user()->hasRole('employee'))
                <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 backdrop-blur md:hidden" aria-label="Employee quick navigation">
                    <div class="grid grid-cols-3">
                        <a href="{{ url('/dashboard') }}" class="flex flex-col items-center gap-1 py-3 text-xs font-medium {{ request()->is('dashboard') || request()->is('/') ? 'text-blue-700' : 'text-gray-500' }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                            Home
                        </a>
                        <a href="{{ url('/payslips') }}" class="flex flex-col items-center gap-1 py-3 text-xs font-medium {{ request()->is('payslips*') ? 'text-blue-700' : 'text-gray-500' }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Payslips
                        </a>
                        <a href="{{ url('/profile') }}" class="flex flex-col items-center gap-1 py-3 text-xs font-medium {{ request()->is('profile') ? 'text-blue-700' : 'text-gray-500' }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A8.963 8.963 0 0112 15c2.21 0 4.23.796 5.879 2.118M15 11a3 3 0 11-6 0 3 3 0 016 0zM12 22a10 10 0 100-20 10 10 0 000 20z" /></svg>
                            Profile
                        </a>
                    </div>
                </nav>
            @endif
        @endauth

        @stack('scripts')
    </body>
</html>
