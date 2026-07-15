@extends('layouts.app')

@section('title', 'Dashboard — Payroll System')

@section('header')
    <x-page-header title="Dashboard">
        <x-slot name="actions">
            <span class="inline-flex items-center gap-1.5 text-sm text-gray-500">
                <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Active Period:
                <strong class="text-gray-900">{{ $activePeriod ? $activePeriod->name : 'None Open' }}</strong>
                @if($activePeriod)
                    <x-badge status="open" />
                @endif
            </span>
        </x-slot>
    </x-page-header>

    <x-breadcrumb :items="[['Home', '/dashboard']]" />
@endsection

@section('content')

    {{-- ──────────────────────────────────────────────────────────
         KPI Cards Row
    ────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">

        <x-stat-card
            label="Active Employees"
            :value="number_format($stats['active_employees'])"
            sub-label="in this company"
            color="blue"
        >
            <x-slot name="icon">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            label="Gross This Period"
            :value="'TZS ' . number_format($stats['gross_this_period'], 0)"
            sub-label="{{ $activePeriod ? $activePeriod->name : 'N/A' }}"
            color="green"
        >
            <x-slot name="icon">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            label="Active Loans"
            :value="number_format($stats['active_loans'])"
            sub-label="TZS {{ number_format($stats['total_loan_balance'], 0) }} outstanding"
            color="amber"
        >
            <x-slot name="icon">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            label="Payroll Runs (YTD)"
            :value="number_format($stats['runs_ytd'])"
            sub-label="{{ $stats['locked_runs_ytd'] }} locked / filed"
            color="purple"
        >
            <x-slot name="icon">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </x-slot>
        </x-stat-card>
    </div>

    {{-- ──────────────────────────────────────────────────────────
         Recent Payroll Runs + Quick Actions
    ────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Recent Payroll Runs (2/3 width) --}}
        <div class="xl:col-span-2">
            <x-card title="Recent Payroll Runs">
                <x-slot name="headerAction">
                    <a href="{{ url('/payroll-runs') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">View all →</a>
                </x-slot>

                @if($recentRuns->isEmpty())
                    <x-empty-state
                        title="No payroll runs yet"
                        description="Start a new payroll run for the current period."
                    />
                @else
                    <div class="overflow-x-auto -mx-6">
                        <table class="min-w-full divide-y divide-gray-200 text-sm" aria-label="Recent payroll runs">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Period</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Net Pay</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Employees</th>
                                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($recentRuns as $run)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-3 font-medium text-gray-900 whitespace-nowrap">
                                        {{ $run->payrollPeriod->name ?? '—' }}
                                    </td>
                                    <td class="px-6 py-3 text-gray-600 whitespace-nowrap capitalize">
                                        {{ str_replace('_', ' ', $run->run_type) }}
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        <x-badge :status="$run->status" />
                                    </td>
                                    <td class="px-6 py-3 text-right font-mono text-gray-900 whitespace-nowrap">
                                        TZS {{ number_format($run->total_net_pay ?? 0, 0) }}
                                    </td>
                                    <td class="px-6 py-3 text-gray-600 whitespace-nowrap">
                                        {{ number_format($run->payrollEntries()->count()) }}
                                    </td>
                                    <td class="px-6 py-3 text-right whitespace-nowrap">
                                        <a href="{{ url('/payroll-runs/' . $run->id) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            View →
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>

        {{-- Quick Actions + Compliance Alerts (1/3) --}}
        <div class="space-y-5">
            {{-- Quick Actions --}}
            <x-card title="Quick Actions">
                <div class="space-y-2">
                    @hasanyrole('system_administrator|payroll_officer')
                    <a href="{{ url('/payroll-runs/create') }}"
                       class="flex items-center gap-3 w-full px-4 py-3 text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Payroll Run
                    </a>
                    @endhasanyrole

                    @hasanyrole('system_administrator|hr_manager|hr_officer')
                    <a href="{{ url('/employees/create') }}"
                       class="flex items-center gap-3 w-full px-4 py-3 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        Add Employee
                    </a>
                    @endhasanyrole

                    @hasanyrole('system_administrator|hr_manager|finance_manager|payroll_officer')
                    <a href="{{ url('/loans/create') }}"
                       class="flex items-center gap-3 w-full px-4 py-3 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Register Loan
                    </a>
                    @endhasanyrole

                    @hasanyrole('system_administrator|finance_manager')
                    <a href="{{ url('/reports') }}"
                       class="flex items-center gap-3 w-full px-4 py-3 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Generate Report
                    </a>
                    @endhasanyrole
                </div>
            </x-card>

            {{-- Compliance Summary --}}
            <x-card title="Compliance Status">
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Active Period</span>
                        @if($activePeriod)
                            <x-badge status="open" />
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">None</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Pending Approvals</span>
                        <span class="font-semibold {{ $stats['pending_approvals'] > 0 ? 'text-amber-700' : 'text-gray-900' }}">
                            {{ $stats['pending_approvals'] }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Insufficient Fund Flags</span>
                        <span class="font-semibold {{ $stats['insufficient_fund_flags'] > 0 ? 'text-red-700' : 'text-gray-900' }}">
                            {{ $stats['insufficient_fund_flags'] }}
                        </span>
                    </div>
                </div>
            </x-card>
        </div>

    </div>

@endsection
