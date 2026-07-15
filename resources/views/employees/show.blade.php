@extends('layouts.app')
@section('title', 'Employee Profile - PayEasy+HR')
@section('header')
    <x-page-header title="Employee Profile" />
    <x-breadcrumb :items="[['Home', '/dashboard'], ['Employees', '/employees'], ['Profile', '#']]" />
@endsection

@section('content')
    <div class="space-y-6" x-data="{ tab: 'profile' }">
        <x-alert type="info" message="This employee record is company-scoped. Use the tabs to review profile, payroll, and history data." />

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
            <x-card class="lg:col-span-1" title="Employee Snapshot">
                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Employee #</p>
                        <p class="mt-1 font-mono text-gray-900">{{ $employee->employee_number }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Name</p>
                        <p class="mt-1 font-medium text-gray-900">{{ $employee->first_name }} {{ $employee->last_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Status</p>
                        <x-badge :status="$employee->status" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Current Basic Salary</p>
                        <p class="mt-1 font-mono text-gray-900">TZS {{ number_format($employee->currentSalary?->basic_salary_amount ?? 0, 0) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Department</p>
                        <p class="mt-1 text-gray-900">{{ $employee->department->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Branch</p>
                        <p class="mt-1 text-gray-900">{{ $employee->branch->name ?? 'N/A' }}</p>
                    </div>
                </div>
            </x-card>

            <div class="lg:col-span-3 space-y-4">
                <div class="flex flex-wrap gap-2 rounded-xl border border-gray-200 bg-white p-2 text-sm">
                    <button type="button" @click="tab = 'profile'" class="rounded-lg px-4 py-2 font-medium" :class="tab === 'profile' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">Profile</button>
                    <button type="button" @click="tab = 'bank'" class="rounded-lg px-4 py-2 font-medium" :class="tab === 'bank' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">Banking</button>
                    <button type="button" @click="tab = 'salary'" class="rounded-lg px-4 py-2 font-medium" :class="tab === 'salary' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">Salary</button>
                    <button type="button" @click="tab = 'earnings'" class="rounded-lg px-4 py-2 font-medium" :class="tab === 'earnings' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">Earnings</button>
                    <button type="button" @click="tab = 'deductions'" class="rounded-lg px-4 py-2 font-medium" :class="tab === 'deductions' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">Deductions</button>
                    <button type="button" @click="tab = 'leave'" class="rounded-lg px-4 py-2 font-medium" :class="tab === 'leave' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">Leave</button>
                    <button type="button" @click="tab = 'loans'" class="rounded-lg px-4 py-2 font-medium" :class="tab === 'loans' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">Loans</button>
                    <button type="button" @click="tab = 'overtime'" class="rounded-lg px-4 py-2 font-medium" :class="tab === 'overtime' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'">Overtime</button>
                </div>

                <x-card x-show="tab === 'profile'" title="Personal Details" x-cloak>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 text-sm">
                        <div><span class="text-gray-500">Employee Number:</span> <strong>{{ $employee->employee_number }}</strong></div>
                        <div><span class="text-gray-500">Job Title:</span> <strong>{{ $employee->job_title ?? 'N/A' }}</strong></div>
                        <div><span class="text-gray-500">Employment Type:</span> <strong>{{ str_replace('_', ' ', $employee->employment_type) }}</strong></div>
                        <div><span class="text-gray-500">Resident Status:</span> <strong>{{ str_replace('_', ' ', $employee->resident_status) }}</strong></div>
                        <div><span class="text-gray-500">Hire Date:</span> <strong>{{ $employee->hire_date?->format('d M Y') ?? 'N/A' }}</strong></div>
                        <div><span class="text-gray-500">Termination Date:</span> <strong>{{ $employee->termination_date?->format('d M Y') ?? 'N/A' }}</strong></div>
                        <div><span class="text-gray-500">TIN:</span> <strong>{{ $employee->tin ?? 'N/A' }}</strong></div>
                        <div><span class="text-gray-500">NSSF Number:</span> <strong>{{ $employee->nssf_number ?? 'N/A' }}</strong></div>
                    </div>
                </x-card>

                <x-card x-show="tab === 'bank'" title="Banking Details" x-cloak>
                    @if($employee->bankDetails->isEmpty())
                        <x-empty-state title="No bank details" description="Bank details have not been recorded for this employee yet." />
                    @else
                        <div class="space-y-3">
                            @foreach($employee->bankDetails as $bankDetail)
                                <div class="rounded-lg border border-gray-200 p-4 text-sm">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="font-medium text-gray-900">{{ $bankDetail->bank->name ?? 'Bank' }}</p>
                                        @if($bankDetail->is_primary)
                                            <x-badge status="active" />
                                        @endif
                                    </div>
                                    <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2">
                                        <p><span class="text-gray-500">Branch Code:</span> {{ $bankDetail->branch_code ?? 'N/A' }}</p>
                                        <p><span class="text-gray-500">Account Number:</span> {{ $bankDetail->account_number }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>

                <x-card x-show="tab === 'salary'" title="Salary History" x-cloak>
                    <x-alert type="info" message="Salary history is append-only. To change salary, add a new record with the effective date." />
                    @if($employee->salaryHistories->isEmpty())
                        <x-empty-state title="No salary history" description="No salary records have been created for this employee." />
                    @else
                        <div class="space-y-3">
                            @foreach($employee->salaryHistories as $salaryHistory)
                                <div class="rounded-lg border border-gray-200 p-4 text-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="font-medium text-gray-900">TZS {{ number_format($salaryHistory->basic_salary_amount, 0) }}</p>
                                        @if($employee->currentSalary && $employee->currentSalary->id === $salaryHistory->id)
                                            <x-badge status="locked" />
                                        @endif
                                    </div>
                                    <p class="mt-2 text-gray-600">Effective from {{ $salaryHistory->effective_from?->format('d M Y') }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>

                <x-card x-show="tab === 'earnings'" title="Earnings" x-cloak>
                    @if($employee->earnings->isEmpty())
                        <x-empty-state title="No earnings" description="No recurring earnings have been configured for this employee." />
                    @else
                        <div class="space-y-3">
                            @foreach($employee->earnings as $earning)
                                <div class="rounded-lg border border-gray-200 p-4 text-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="font-medium text-gray-900">{{ $earning->earningType->name ?? 'Earning' }}</p>
                                        <p class="font-mono text-gray-900">TZS {{ number_format($earning->amount, 0) }}</p>
                                    </div>
                                    <p class="mt-1 text-gray-500">{{ $earning->is_active ? 'Active' : 'Inactive' }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>

                <x-card x-show="tab === 'deductions'" title="Deductions" x-cloak>
                    @if($employee->deductions->isEmpty())
                        <x-empty-state title="No deductions" description="No recurring deductions have been configured for this employee." />
                    @else
                        <div class="space-y-3">
                            @foreach($employee->deductions as $deduction)
                                <div class="rounded-lg border border-gray-200 p-4 text-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="font-medium text-gray-900">{{ $deduction->deductionType->name ?? 'Deduction' }}</p>
                                        <p class="font-mono text-gray-900">{{ $deduction->amount ? 'TZS '.number_format($deduction->amount, 0) : rtrim(rtrim(number_format($deduction->percentage, 2, '.', ''), '0'), '.') . '%' }}</p>
                                    </div>
                                    <p class="mt-1 text-gray-500">{{ $deduction->is_active ? 'Active' : 'Inactive' }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>

                <x-card x-show="tab === 'leave'" title="Leave Records" x-cloak>
                    @if($employee->leaveRecords->isEmpty())
                        <x-empty-state title="No leave records" description="No leave records are currently available for this employee." />
                    @else
                        <div class="overflow-x-auto -mx-6">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Type</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Start</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">End</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Days</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach($employee->leaveRecords as $leaveRecord)
                                        <tr>
                                            <td class="px-6 py-3 text-gray-900">{{ str_replace('_', ' ', $leaveRecord->leave_type) }}</td>
                                            <td class="px-6 py-3 text-gray-700">{{ $leaveRecord->start_date?->format('d M Y') }}</td>
                                            <td class="px-6 py-3 text-gray-700">{{ $leaveRecord->end_date?->format('d M Y') }}</td>
                                            <td class="px-6 py-3 text-right text-gray-900">{{ $leaveRecord->total_days }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-card>

                <x-card x-show="tab === 'loans'" title="Loans" x-cloak>
                    @if($employee->loans->isEmpty())
                        <x-empty-state title="No loans" description="This employee does not currently have any loans." />
                    @else
                        <div class="space-y-3">
                            @foreach($employee->loans as $loan)
                                <div class="rounded-lg border border-gray-200 p-4 text-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="font-medium text-gray-900">Loan</p>
                                        <x-badge :status="$loan->loan_status" />
                                    </div>
                                    <div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-3">
                                        <p><span class="text-gray-500">Principal:</span> TZS {{ number_format($loan->principal_amount, 0) }}</p>
                                        <p><span class="text-gray-500">Installment:</span> TZS {{ number_format($loan->installment_amount, 0) }}</p>
                                        <p><span class="text-gray-500">Outstanding:</span> TZS {{ number_format($loan->principal_amount - $loan->total_repaid_amount, 0) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>

                <x-card x-show="tab === 'overtime'" title="Overtime" x-cloak>
                    @if($employee->overtimeRecords->isEmpty())
                        <x-empty-state title="No overtime records" description="No overtime entries are currently available for this employee." />
                    @else
                        <div class="space-y-3">
                            @foreach($employee->overtimeRecords as $overtime)
                                <div class="rounded-lg border border-gray-200 p-4 text-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="font-medium text-gray-900">{{ str_replace('_', ' ', $overtime->overtime_type) }}</p>
                                        <p class="font-mono text-gray-900">{{ $overtime->fixed_amount ? 'TZS '.number_format($overtime->fixed_amount, 0) : $overtime->hours.' hrs' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>
        </div>
    </div>
@endsection