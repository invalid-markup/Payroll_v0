<!-- Topbar -->
<header class="sticky top-0 z-30 flex items-center justify-between gap-4 px-4 py-3 bg-white border-b border-gray-200 sm:px-6 lg:px-8">
    <div class="flex flex-1 items-center gap-4 min-w-0">
        <button
            @click="sidebarOpen = !sidebarOpen"
            class="p-2 text-gray-500 rounded-md lg:hidden hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
        >
            <span class="sr-only">Open sidebar</span>
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        @php
            $currentCompanyId = auth()->user()?->company_id;
            $activePeriod = $currentCompanyId
                ? \App\Models\PayrollPeriod::where('company_id', $currentCompanyId)
                    ->where('status', 'open')
                    ->orderByDesc('start_date')
                    ->first()
                : null;
        @endphp

        <form method="GET" action="{{ url('/employees') }}" class="hidden lg:flex flex-1 max-w-xl">
            <label for="global-employee-search" class="sr-only">Search employees</label>
            <div class="relative w-full">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    id="global-employee-search"
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search employees by name or number"
                    class="w-full rounded-lg border border-gray-300 bg-gray-50 py-2 pl-9 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-label="Search employees"
                >
            </div>
        </form>
    </div>

    <div class="flex items-center gap-3">
        <div class="hidden xl:flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-sm text-gray-700">
            <span class="font-medium text-gray-500">Active period</span>
            <span class="font-semibold text-gray-900">{{ $activePeriod?->name ?? 'No open period' }}</span>
            @if($activePeriod)
                <x-badge :status="$activePeriod->status" />
            @endif
        </div>

        <div class="hidden sm:block">
            @if(auth()->check() && auth()->user()->roles->count() > 0)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200 uppercase tracking-wider">
                    {{ str_replace('_', ' ', auth()->user()->roles->first()->name) }}
                </span>
            @endif
        </div>

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

            <div
                x-show="profileOpen"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 w-56 mt-2 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                style="display: none;"
            >
                <div class="py-1">
                    @if(Route::has('profile.show'))
                        <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Profile</a>
                    @endif

                    @if(Route::has('payslips.index'))
                        <a href="{{ route('payslips.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Payslips</a>
                    @endif

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
