<!-- Sidebar Navigation -->
<div
    class="fixed inset-0 z-40 bg-gray-900 bg-opacity-50 transition-opacity lg:hidden"
    x-show="sidebarOpen"
    x-transition:enter="transition-opacity ease-linear duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    style="display: none;"
    @click="sidebarOpen = false"
></div>

<aside
    class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 shadow-sm transition-transform duration-300 ease-in-out lg:static lg:translate-x-0 overflow-y-auto"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>
    <div class="flex items-center justify-between h-16 border-b border-gray-200 px-4">
        <a href="{{ url('/dashboard') }}" class="flex items-center gap-2">
            <svg class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-xl font-bold text-gray-900">PayEasy+HR</span>
        </a>

        <button type="button" class="lg:hidden inline-flex items-center justify-center rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900" @click="sidebarOpen = false" aria-label="Close sidebar">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav class="p-4 space-y-1">
        <x-nav-link href="{{ url('/dashboard') }}" :active="request()->is('dashboard') || request()->is('/')">
            <x-slot name="icon">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
            </x-slot>
            Dashboard
        </x-nav-link>

        @hasanyrole('system_administrator|hr_manager|hr_officer|payroll_officer|finance_manager|auditor|department_manager')
        <div class="pt-4 pb-1">
            <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">HR</p>
        </div>
        <x-nav-link href="{{ url('/employees') }}" :active="request()->is('employees*')">
            <x-slot name="icon">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </x-slot>
            Employees
        </x-nav-link>
        @endhasanyrole

        @hasanyrole('system_administrator|payroll_officer|finance_manager|auditor')
        <div class="pt-4 pb-1">
            <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Payroll</p>
        </div>
        <x-nav-link href="{{ url('/payroll-periods') }}" :active="request()->is('payroll-periods*')">
            <x-slot name="icon">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </x-slot>
            Payroll Periods
        </x-nav-link>
        <x-nav-link href="{{ url('/payroll-runs') }}" :active="request()->is('payroll-runs*')">
            <x-slot name="icon">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            </x-slot>
            Payroll Runs
        </x-nav-link>
        @endhasanyrole

        @hasanyrole('system_administrator|hr_manager|finance_manager|payroll_officer')
        <x-nav-link href="{{ url('/loans') }}" :active="request()->is('loans*')">
            <x-slot name="icon">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </x-slot>
            Loans
        </x-nav-link>
        @endhasanyrole

        @hasanyrole('system_administrator|finance_manager')
        <div class="pt-4 pb-1">
            <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Finance</p>
        </div>
        <x-nav-link href="{{ url('/bank-export') }}" :active="request()->is('bank-export*')">
            <x-slot name="icon">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
            </x-slot>
            Bank Export
        </x-nav-link>
        @endhasanyrole

        @hasanyrole('system_administrator|auditor')
        <div class="pt-4 pb-1">
            <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">System</p>
        </div>
        <x-nav-link href="{{ url('/audit-logs') }}" :active="request()->is('audit-logs*')">
            <x-slot name="icon">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
            </x-slot>
            Audit Logs
        </x-nav-link>
        @endhasanyrole

        @hasrole('system_administrator')
        <x-nav-link href="{{ url('/company') }}" :active="request()->is('company*')">
            <x-slot name="icon">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            </x-slot>
            Company Profile
        </x-nav-link>
        @endhasrole
    </nav>
</aside>
