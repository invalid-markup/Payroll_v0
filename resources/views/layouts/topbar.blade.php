<!-- Topbar -->
<header class="sticky top-0 z-30 flex items-center justify-between px-4 py-3 bg-white border-b border-gray-200 sm:px-6 lg:px-8">
    <div class="flex items-center">
        <!-- Mobile hamburger menu -->
        <button 
            @click="sidebarOpen = !sidebarOpen" 
            class="p-2 text-gray-500 rounded-md lg:hidden hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
        >
            <span class="sr-only">Open sidebar</span>
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <div class="flex items-center gap-4">
        <!-- Role Indicator (Badge) -->
        <div class="hidden sm:block">
            @if(auth()->check() && auth()->user()->roles->count() > 0)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200 uppercase tracking-wider">
                    {{ str_replace('_', ' ', auth()->user()->roles->first()->name) }}
                </span>
            @endif
        </div>

        <!-- Profile dropdown -->
        <div class="relative" x-data="{ profileOpen: false }">
            <button 
                @click="profileOpen = !profileOpen"
                @click.outside="profileOpen = false"
                class="flex items-center gap-2 p-1 text-sm bg-white rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                <span class="sr-only">Open user menu</span>
                <div class="flex items-center justify-center w-8 h-8 font-semibold text-white bg-blue-600 rounded-full">
                    {{ auth()->check() ? substr(auth()->user()->name, 0, 1) : 'U' }}
                </div>
                <span class="hidden text-sm font-medium text-gray-700 sm:block">{{ auth()->user()->name ?? 'Guest' }}</span>
                <svg class="hidden w-4 h-4 text-gray-400 sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- Dropdown menu -->
            <div 
                x-show="profileOpen" 
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 w-48 mt-2 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                style="display: none;"
            >
                <div class="py-1">
                    <!-- Auth Form Submission for Logout -->
                    <form method="POST" action="{{ url('/logout') }}">
                        @csrf
                        <button type="submit" class="block w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100">
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
