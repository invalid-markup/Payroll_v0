<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Payroll System')</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts & Styles -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <!-- Fallback for Tailwind CSS (CDN for dev if Vite is not running) -->
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

        <!-- Alpine.js (via CDN) -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

        <!-- Custom Head Scripts -->
        @stack('head')
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900" x-data="{ sidebarOpen: false }">
        
        <div class="flex h-screen overflow-hidden">
            
            <!-- Sidebar Navigation -->
            @include('layouts.navigation')

            <!-- Main Content Area -->
            <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
                
                <!-- Topbar -->
                @include('layouts.topbar')

                <main class="w-full max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
                    <!-- Page Header & Breadcrumbs -->
                    @hasSection('header')
                        <div class="mb-6">
                            @yield('header')
                        </div>
                    @endif

                    <!-- Flash Alerts -->
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

                    <!-- Content Slot -->
                    @yield('content')
                </main>
            </div>
        </div>

        <!-- Custom Body Scripts -->
        @stack('scripts')
    </body>
</html>
