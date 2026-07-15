@extends('layouts.app')
@section('title', 'Loan Detail - PayEasy+HR')
@section('header')
    <x-page-header title="Loan Detail" />
    <x-breadcrumb :items="[['Home', '/dashboard'], ['Loans', '/loans'], ['Detail', '#']]" />
@endsection

@section('content')
    <div class="space-y-6">
        <x-card title="Loan Information">
            <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                <div><span class="text-gray-500">Employee:</span> <strong>{{ $loan->employee->first_name ?? '' }} {{ $loan->employee->last_name ?? '' }}</strong></div>
                <div><span class="text-gray-500">Department:</span> <strong>{{ $loan->employee->department->name ?? '—' }}</strong></div>
                <div><span class="text-gray-500">Principal:</span> <strong>TZS {{ number_format($loan->principal_amount ?? 0, 0) }}</strong></div>
                <div><span class="text-gray-500">Outstanding:</span> <strong>TZS {{ number_format($loan->principal_amount - $loan->total_repaid_amount ?? 0, 0) }}</strong></div>
                <div><span class="text-gray-500">Monthly:</span> <strong>TZS {{ number_format($loan->installment_amount ?? 0, 0) }}</strong></div>
                <div><span class="text-gray-500">Status:</span> <x-badge :status="$loan->loan_status" /></div>
            </div>
        </x-card>

        <x-card title="Repayment History">
            @if($loan->installments->isEmpty())
                <x-empty-state title="No installments yet" description="Installments will appear here once payroll deductions are posted." />
            @else
                <div class="overflow-x-auto -mx-6">
                    <table class="min-w-full divide-y divide-gray-200 text-sm" aria-label="Loan installments">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Payroll Period</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Amount Deducted</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Before</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">After</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach($loan->installments as $installment)
                                <tr>
                                    <td class="px-6 py-3 text-gray-900">{{ $installment->payrollPeriod->name ?? $installment->payroll_period_id }}</td>
                                    <td class="px-6 py-3 text-right font-mono">TZS {{ number_format($installment->amount_deducted, 0) }}</td>
                                    <td class="px-6 py-3 text-right font-mono">TZS {{ number_format($installment->outstanding_balance_before, 0) }}</td>
                                    <td class="px-6 py-3 text-right font-mono">TZS {{ number_format($installment->outstanding_balance_after, 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
@endsection