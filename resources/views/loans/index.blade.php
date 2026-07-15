@extends('layouts.app')
@section('title', 'Loans — PayEasy+HR')
@section('header')
    <x-page-header title="Loans">
        <x-slot name="actions">
            @hasanyrole('system_administrator|hr_manager|finance_manager|payroll_officer')
            <a href="{{ url('/loans/create') }}">
                <x-button variant="primary" id="btn-register-loan">
                    <svg class="w-4 h-4 mr-2 -ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Register Loan
                </x-button>
            </a>
            @endhasanyrole
        </x-slot>
    </x-page-header>
    <x-breadcrumb :items="[['Home', '/dashboard'], ['Loans', '/loans']]" />
@endsection
@section('content')
    {{-- Status filter tabs --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex gap-6" aria-label="Loan status filter">
            @foreach(['all' => 'All', 'active' => 'Active', 'completed' => 'Completed', 'defaulted' => 'Defaulted'] as $val => $lbl)
            <a href="{{ url('/loans?status=' . $val) }}"
               class="{{ $statusFilter === $val ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} border-b-2 pb-3 text-sm transition-colors"
               id="tab-loan-{{ $val }}">
                {{ $lbl }}
            </a>
            @endforeach
        </nav>
    </div>

    <x-card>
        @if($loans->isEmpty())
            <x-empty-state title="No loans found" description="No employee loans for this filter." />
        @else
            <div class="overflow-x-auto -mx-6">
                <table class="min-w-full divide-y divide-gray-200 text-sm" aria-label="Employee loans">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Employee</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Loan Type</th>
                            <th scope="col" class="hidden md:table-cell px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Principal</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Outstanding</th>
                            <th scope="col" class="hidden md:table-cell px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Monthly</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">View</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($loans as $loan)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-3 font-medium text-gray-900 whitespace-nowrap">
                                {{ $loan->employee->first_name ?? '' }} {{ $loan->employee->last_name ?? '' }}
                                <span class="block text-xs text-gray-400 font-mono">{{ $loan->employee->employee_number ?? '' }}</span>
                            </td>
                            <td class="px-6 py-3 text-gray-600 whitespace-nowrap">{{ $loan->loanType->name ?? '—' }}</td>
                            <td class="hidden md:table-cell px-6 py-3 text-right font-mono text-gray-700 whitespace-nowrap">TZS {{ number_format($loan->principal_amount ?? 0, 0) }}</td>
                            <td class="px-6 py-3 text-right font-mono font-semibold text-gray-900 whitespace-nowrap">TZS {{ number_format($loan->outstanding_balance ?? 0, 0) }}</td>
                            <td class="hidden md:table-cell px-6 py-3 text-right font-mono text-gray-700 whitespace-nowrap">TZS {{ number_format($loan->monthly_deduction ?? 0, 0) }}</td>
                            <td class="px-6 py-3 whitespace-nowrap"><x-badge :status="$loan->status" /></td>
                            <td class="px-6 py-3 text-right whitespace-nowrap">
                                <a href="{{ url('/loans/' . $loan->id) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View →</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($loans->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">{{ $loans->withQueryString()->links() }}</div>
            @endif
        @endif
    </x-card>
@endsection
