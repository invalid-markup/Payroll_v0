@extends('layouts.app')
@section('title', 'Payroll Periods — PayEasy+HR')
@section('header')
    <x-page-header title="Payroll Periods" />
    <x-breadcrumb :items="[['Home', '/dashboard'], ['Payroll Periods', '/payroll-periods']]" />
@endsection
@section('content')
    <x-card>
        @if($periods->isEmpty())
            <x-empty-state title="No payroll periods" description="No payroll periods have been configured. Create one via the API or Company Settings." />
        @else
            <div class="overflow-x-auto -mx-6">
                <table class="min-w-full divide-y divide-gray-200 text-sm" aria-label="Payroll periods">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Period Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Start Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">End Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Runs</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">View</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($periods as $period)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-3 font-medium text-gray-900 whitespace-nowrap">{{ $period->name }}</td>
                            <td class="px-6 py-3 text-gray-600 whitespace-nowrap">{{ \Carbon\Carbon::parse($period->start_date)->format('d M Y') }}</td>
                            <td class="px-6 py-3 text-gray-600 whitespace-nowrap">{{ \Carbon\Carbon::parse($period->end_date)->format('d M Y') }}</td>
                            <td class="px-6 py-3 whitespace-nowrap"><x-badge :status="$period->status" /></td>
                            <td class="px-6 py-3 text-right text-gray-600 whitespace-nowrap">{{ $period->payroll_runs_count }}</td>
                            <td class="px-6 py-3 text-right whitespace-nowrap">
                                <a href="{{ url('/payroll-periods/' . $period->id) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View →</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($periods->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $periods->links() }}
                </div>
            @endif
        @endif
    </x-card>
@endsection
