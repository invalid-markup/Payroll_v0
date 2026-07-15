@extends('layouts.app')

@section('title', 'Payroll Runs — PayEasy+HR')

@section('header')
    <x-page-header title="Payroll Runs">
        <x-slot name="actions">
            @hasanyrole('system_administrator|payroll_officer')
                <a href="{{ route('payroll-runs.create') }}">
                    <x-button variant="primary" id="btn-new-run">
                        <svg class="w-4 h-4 mr-2 -ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Payroll Run
                    </x-button>
                </a>
            @endhasanyrole
        </x-slot>
    </x-page-header>
    <x-breadcrumb :items="[['Home', '/dashboard'], ['Payroll Runs', '/payroll-runs']]" />
@endsection

@section('content')
    <div class="border-b border-gray-200 mb-6 overflow-x-auto">
        <nav class="-mb-px flex min-w-max gap-6" aria-label="Payroll run status filter">
            @foreach(['all' => 'All Runs', 'draft' => 'Draft', 'validated' => 'Validated', 'preview' => 'Preview', 'approved' => 'Approved', 'locked' => 'Locked', 'filed' => 'Filed', 'reversed' => 'Reversed'] as $value => $label)
                <a
                    href="{{ url('/payroll-runs?status=' . $value) }}"
                    class="{{ request('status', 'all') === $value ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} border-b-2 pb-3 text-sm transition-colors"
                    id="tab-{{ $value }}"
                    aria-current="{{ request('status', 'all') === $value ? 'page' : 'false' }}"
                >
                    {{ $label }}
                    @if(isset($counts[$value]))
                        <span class="ml-1.5 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                            {{ $counts[$value] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>

    <x-card>
        @if($runs->isEmpty())
            <x-empty-state
                title="No payroll runs"
                description="No payroll runs exist for this status. Start a new run above."
            />
        @else
            <div class="overflow-x-auto -mx-6">
                <table class="min-w-full divide-y divide-gray-200 text-sm" aria-label="Payroll runs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Period</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Run Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="hidden md:table-cell px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Gross Pay</th>
                            <th scope="col" class="hidden md:table-cell px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Net Pay</th>
                            <th scope="col" class="hidden lg:table-cell px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Employees</th>
                            <th scope="col" class="hidden lg:table-cell px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Processed By</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($runs as $run)
                            <tr class="hover:bg-gray-50 transition-colors" id="run-row-{{ $run->id }}">
                                <td class="px-6 py-3 font-medium text-gray-900 whitespace-nowrap">
                                    {{ $run->payrollPeriod->name ?? '—' }}
                                </td>
                                <td class="px-6 py-3 text-gray-600 whitespace-nowrap capitalize">
                                    {{ str_replace('_', ' ', $run->run_type) }}
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <x-badge :status="$run->status" />
                                </td>
                                <td class="hidden md:table-cell px-6 py-3 text-right font-mono text-gray-900 whitespace-nowrap">
                                    TZS {{ number_format($run->total_gross_pay ?? 0, 0) }}
                                </td>
                                <td class="hidden md:table-cell px-6 py-3 text-right font-mono font-semibold text-gray-900 whitespace-nowrap">
                                    TZS {{ number_format($run->total_net_pay ?? 0, 0) }}
                                </td>
                                <td class="hidden lg:table-cell px-6 py-3 text-right text-gray-600 whitespace-nowrap">
                                    {{ $run->payrollEntries->count() }}
                                </td>
                                <td class="hidden lg:table-cell px-6 py-3 text-gray-600 whitespace-nowrap">
                                    {{ $run->processedBy->name ?? '—' }}
                                </td>
                                <td class="px-6 py-3 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('payroll-runs.show', $run->id) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium" id="view-run-{{ $run->id }}">View</a>

                                        @if(in_array($run->status, ['draft', 'validated'], true))
                                            @hasanyrole('system_administrator|payroll_officer')
                                                <span class="text-gray-300">|</span>
                                                <form method="POST" action="{{ route('payroll-runs.calculate', $run->id) }}" class="inline" x-data @submit.prevent="if(confirm('Calculate this payroll run?')) $el.submit()">
                                                    @csrf
                                                    <button type="submit" class="text-green-600 hover:text-green-800 text-sm font-medium" id="calc-run-{{ $run->id }}">Calculate</button>
                                                </form>
                                            @endhasanyrole
                                        @elseif($run->status === 'preview')
                                            @hasanyrole('system_administrator|payroll_officer')
                                                <span class="text-gray-300">|</span>
                                                <form method="POST" action="{{ route('payroll-runs.submit', $run->id) }}" class="inline" x-data @submit.prevent="if(confirm('Submit this payroll run for approval?')) $el.submit()">
                                                    @csrf
                                                    <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm font-medium" id="submit-run-{{ $run->id }}">Submit</button>
                                                </form>
                                            @endhasanyrole
                                        @elseif($run->status === 'approved')
                                            @hasanyrole('system_administrator|finance_manager')
                                                @if(auth()->id() !== $run->submitted_by_user_id)
                                                    <span class="text-gray-300">|</span>
                                                    <form method="POST" action="{{ route('payroll-runs.approve', $run->id) }}" class="inline" x-data @submit.prevent="if(confirm('Approve and lock this payroll run?')) $el.submit()">
                                                        @csrf
                                                        <button type="submit" class="text-purple-600 hover:text-purple-800 text-sm font-medium" id="approve-run-{{ $run->id }}">Approve &amp; Lock</button>
                                                    </form>
                                                @else
                                                    <span class="text-red-600 text-sm font-medium">Maker cannot approve</span>
                                                @endif
                                            @endhasanyrole
                                        @elseif($run->status === 'locked')
                                            @hasanyrole('system_administrator|finance_manager')
                                                <span class="text-gray-300">|</span>
                                                <form method="POST" action="{{ route('payroll-runs.file', $run->id) }}" class="inline" x-data @submit.prevent="if(confirm('Mark this payroll run as filed?')) $el.submit()">
                                                    @csrf
                                                    <button type="submit" class="text-emerald-600 hover:text-emerald-800 text-sm font-medium" id="file-run-{{ $run->id }}">Mark Filed</button>
                                                </form>
                                            @endhasanyrole
                                        @elseif($run->status === 'filed')
                                            @hasrole('system_administrator')
                                                <span class="text-gray-300">|</span>
                                                <form method="POST" action="{{ route('payroll-runs.amend', $run->id) }}" class="inline" x-data @submit.prevent="if(confirm('Create an amended return for this filed payroll run?')) $el.submit()">
                                                    @csrf
                                                    <button type="submit" class="text-gray-700 hover:text-gray-900 text-sm font-medium" id="amend-run-{{ $run->id }}">Amend</button>
                                                </form>
                                            @endhasrole
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($runs->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        Showing {{ $runs->firstItem() }}–{{ $runs->lastItem() }} of {{ $runs->total() }} runs
                    </p>
                    {{ $runs->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </x-card>
@endsection
