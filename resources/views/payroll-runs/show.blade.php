@extends('layouts.app')

@section('title', 'Payroll Run — ' . ($run->payrollPeriod->name ?? '') . ' — PayEasy+HR')

@section('header')
    <x-page-header :title="'Payroll Run — ' . ($run->payrollPeriod->name ?? 'N/A')">
        <x-slot name="badge">
            @if(in_array($run->status, ['locked', 'filed', 'reversed'], true))
                <x-hard-record-badge />
            @endif
        </x-slot>
        <x-slot name="actions">
            <div class="flex items-center gap-2 flex-wrap justify-end">
                @if(in_array($run->status, ['draft', 'validated'], true))
                    @hasanyrole('system_administrator|payroll_officer')
                        <form method="POST" action="{{ route('payroll-runs.calculate', $run->id) }}" x-data @submit.prevent="if(confirm('Calculate this payroll run?')) $el.submit()">
                            @csrf
                            <x-button type="submit" variant="primary" id="btn-calculate-run">
                                <svg class="w-4 h-4 mr-2 -ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M15 14h.01M9 14h.01M12 14h.01M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z" />
                                </svg>
                                Calculate
                            </x-button>
                        </form>
                    @endhasanyrole
                @elseif($run->status === 'preview')
                    @hasanyrole('system_administrator|payroll_officer')
                        <form method="POST" action="{{ route('payroll-runs.submit', $run->id) }}" x-data @submit.prevent="if(confirm('Submit this payroll run for approval?')) $el.submit()">
                            @csrf
                            <x-button type="submit" variant="primary" id="btn-submit-run">Submit for Approval</x-button>
                        </form>
                    @endhasanyrole
                @elseif($run->status === 'approved')
                    @hasanyrole('system_administrator|finance_manager')
                        @if(auth()->id() !== $run->submitted_by_user_id)
                            <form method="POST" action="{{ route('payroll-runs.approve', $run->id) }}" x-data @submit.prevent="if(confirm('Approve and lock this payroll run?')) $el.submit()">
                                @csrf
                                <x-button type="submit" variant="primary" id="btn-approve-run">Approve &amp; Lock</x-button>
                            </form>
                        @else
                            <x-alert type="error" message="Action Blocked: You submitted this payroll run and cannot approve it." />
                        @endif
                    @endhasanyrole
                @elseif($run->status === 'locked')
                    @hasanyrole('system_administrator|finance_manager')
                        <form method="POST" action="{{ route('payroll-runs.file', $run->id) }}" x-data @submit.prevent="if(confirm('Mark this payroll run as filed?')) $el.submit()">
                            @csrf
                            <x-button type="submit" variant="secondary" id="btn-file-run">Mark as Filed</x-button>
                        </form>
                    @endhasanyrole
                @elseif($run->status === 'filed')
                    @hasrole('system_administrator')
                        <form method="POST" action="{{ route('payroll-runs.amend', $run->id) }}" x-data @submit.prevent="if(confirm('Create an amended return for this filed payroll run?')) $el.submit()">
                            @csrf
                            <x-button type="submit" variant="secondary" id="btn-amend-run">Initiate Amended Return</x-button>
                        </form>
                    @endhasrole
                @endif
            </div>
        </x-slot>
    </x-page-header>
    <x-breadcrumb :items="[
        ['Home', '/dashboard'],
        ['Payroll Runs', '/payroll-runs'],
        [$run->payrollPeriod->name ?? 'Detail', '#']
    ]" />
@endsection

@section('content')
    <div class="space-y-5">
        @if(in_array($run->status, ['locked', 'filed', 'reversed'], true))
            <x-alert type="info" message="This record is locked. Historical payroll data cannot be edited and any correction must use the formal amendment workflow." />
        @elseif($run->status === 'approved')
            <x-alert type="info" message="This payroll run is awaiting checker approval." />
        @elseif($run->status === 'preview')
            <x-alert type="warning" message="This is a preview run. Review the calculations before submitting for approval." />
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-7">
            <div class="bg-white border border-gray-200 rounded-xl px-5 py-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Status</p>
                <x-badge :status="$run->status" class="text-sm" />
            </div>

            <div class="bg-white border border-gray-200 rounded-xl px-5 py-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Gross Pay</p>
                <p class="font-mono font-semibold text-gray-900 text-sm">TZS {{ number_format($run->total_gross_pay ?? 0, 0) }}</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl px-5 py-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Total Tax (PAYE)</p>
                <p class="font-mono font-semibold text-gray-900 text-sm">TZS {{ number_format($run->total_paye ?? 0, 0) }}</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl px-5 py-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Total Deductions</p>
                <p class="font-mono font-semibold text-gray-900 text-sm">TZS {{ number_format($run->total_deductions ?? 0, 0) }}</p>
            </div>

            <div class="bg-blue-600 rounded-xl px-5 py-4">
                <p class="text-xs font-semibold text-blue-200 uppercase tracking-wider mb-1">Net Pay</p>
                <p class="font-mono font-bold text-white text-sm">TZS {{ number_format($run->total_net_pay ?? 0, 0) }}</p>
            </div>
        </div>

        <x-card title="Payroll Entries" id="payroll-entries-section">
            <x-slot name="headerAction">
                <span class="text-sm text-gray-500">{{ $run->payrollEntries->count() }} employees</span>
            </x-slot>

            @if($run->payrollEntries->isEmpty())
                <x-empty-state
                    title="No entries yet"
                    description="Run the calculation to generate payroll entries for this period."
                />
            @else
                <div class="overflow-x-auto -mx-6">
                    <table class="min-w-full divide-y divide-gray-200 text-sm" aria-label="Payroll entries">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Employee</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Basic</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Gross</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">PAYE</th>
                                <th scope="col" class="hidden md:table-cell px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">NSSF</th>
                                <th scope="col" class="hidden md:table-cell px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Other Ded.</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold text-gray-900 uppercase tracking-wider">Net Pay</th>
                                <th scope="col" class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Flag</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach($run->payrollEntries as $entry)
                                @php
                                    $otherDeductions = max(((float) $entry->total_deductions_amount) - ((float) $entry->nssf_deduction_amount) - ((float) $entry->paye_tax_amount), 0);
                                    $allowances = max(((float) $entry->gross_salary_amount) - ((float) $entry->basic_salary_amount), 0);
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors {{ $entry->processing_status === 'failed' ? 'bg-red-50' : '' }}" id="entry-{{ $entry->id }}">
                                    <td class="px-5 py-3 whitespace-nowrap">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $entry->employee->first_name ?? '' }} {{ $entry->employee->last_name ?? '' }}</p>
                                            <p class="text-xs text-gray-400 font-mono">{{ $entry->employee->employee_number ?? '' }}</p>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono text-gray-700 whitespace-nowrap">{{ number_format($entry->basic_salary_amount ?? 0, 0) }}</td>
                                    <td class="px-5 py-3 text-right font-mono text-gray-700 whitespace-nowrap">{{ number_format($entry->gross_salary_amount ?? 0, 0) }}</td>
                                    <td class="px-5 py-3 text-right font-mono text-gray-700 whitespace-nowrap">{{ number_format($entry->paye_tax_amount ?? 0, 0) }}</td>
                                    <td class="hidden md:table-cell px-5 py-3 text-right font-mono text-gray-700 whitespace-nowrap">{{ number_format($entry->nssf_deduction_amount ?? 0, 0) }}</td>
                                    <td class="hidden md:table-cell px-5 py-3 text-right font-mono text-gray-700 whitespace-nowrap">{{ number_format($otherDeductions, 0) }}</td>
                                    <td class="px-5 py-3 text-right font-mono font-semibold text-gray-900 whitespace-nowrap">{{ number_format($entry->net_salary_amount ?? 0, 0) }}</td>
                                    <td class="px-5 py-3 text-center whitespace-nowrap">
                                        @if($entry->processing_status === 'failed')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Failed</span>
                                        @elseif($allowances > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Calculated</span>
                                        @else
                                            <span class="text-green-600">
                                                <svg class="w-4 h-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                            <tr>
                                <td class="px-5 py-3 text-sm font-semibold text-gray-700">Totals</td>
                                <td class="px-5 py-3 text-right font-mono font-semibold text-gray-900"></td>
                                <td class="px-5 py-3 text-right font-mono font-semibold text-gray-900">{{ number_format($run->total_gross_pay ?? 0, 0) }}</td>
                                <td class="px-5 py-3 text-right font-mono font-semibold text-gray-900">{{ number_format($run->total_paye ?? 0, 0) }}</td>
                                <td class="hidden md:table-cell px-5 py-3 text-right font-mono font-semibold text-gray-900"></td>
                                <td class="hidden md:table-cell px-5 py-3 text-right font-mono font-semibold text-gray-900"></td>
                                <td class="px-5 py-3 text-right font-mono font-bold text-blue-700 text-base">{{ number_format($run->total_net_pay ?? 0, 0) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </x-card>

        <div class="mt-5 grid grid-cols-1 lg:grid-cols-3 gap-4 text-sm">
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Run type</p>
                <p class="mt-1 font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $run->run_type) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Created by</p>
                <p class="mt-1 font-medium text-gray-900">{{ $run->processedBy->name ?? '—' }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $run->created_at->format('d M Y, H:i') }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Approval trail</p>
                <p class="mt-1 text-gray-900">Submitted: {{ data_get($run->metadata, 'submitted_at') ? \Carbon\Carbon::parse(data_get($run->metadata, 'submitted_at'))->format('d M Y, H:i') : '—' }}</p>
                <p class="text-gray-900">Approved: {{ data_get($run->metadata, 'approved_at') ? \Carbon\Carbon::parse(data_get($run->metadata, 'approved_at'))->format('d M Y, H:i') : '—' }}</p>
                <p class="text-gray-900">Filed: {{ data_get($run->metadata, 'filed_at') ? \Carbon\Carbon::parse(data_get($run->metadata, 'filed_at'))->format('d M Y, H:i') : '—' }}</p>
            </div>
        </div>
    </div>
@endsection
