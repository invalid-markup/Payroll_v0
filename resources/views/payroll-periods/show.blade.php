@extends('layouts.app')
@section('title', 'Period Detail — PayEasy+HR')
@section('header')
    <x-page-header :title="$period->name" />
    <x-breadcrumb :items="[['Home', '/dashboard'], ['Payroll Periods', '/payroll-periods'], [$period->name, '#']]" />
@endsection
@section('content')
    <x-card title="Payroll Runs in This Period">
        @forelse($period->payrollRuns as $run)
            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                <div>
                    <span class="font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $run->run_type) }}</span>
                    <x-badge :status="$run->status" class="ml-2" />
                </div>
                <a href="{{ url('/payroll-runs/' . $run->id) }}" class="text-blue-600 text-sm">View →</a>
            </div>
        @empty
            <x-empty-state title="No runs" description="No payroll runs in this period." />
        @endforelse
    </x-card>
@endsection
