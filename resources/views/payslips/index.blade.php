@extends('layouts.app')

@section('title', 'My Payslips — PayEasy+HR')

@section('header')
    <x-page-header title="My Payslips" />
    <x-breadcrumb :items="[['Home', '/dashboard'], ['Payslips', '#']]" />
@endsection

@section('content')
    <div class="space-y-5">
        <x-alert type="info" message="Employee payslips appear here after a payroll run is locked and published for the period." />

        <x-card title="Available Payslips">
            @if($runs->isEmpty())
                <x-empty-state
                    title="No payslips available"
                    description="There are no locked or filed payroll runs yet for your company."
                />
            @else
                <div class="space-y-3">
                    @foreach($runs as $run)
                        <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                            <div>
                                <p class="font-medium text-gray-900">{{ $run->payrollPeriod->name ?? 'Payroll period' }}</p>
                                <p class="text-xs text-gray-500">{{ str_replace('_', ' ', $run->run_type) }} run</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <x-badge :status="$run->status" />
                                <span class="text-sm font-semibold text-gray-900">TZS {{ number_format($run->total_net_pay ?? 0, 0) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
@endsection
