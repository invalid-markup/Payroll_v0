@extends('layouts.app')

@section('title', 'New Payroll Run — PayEasy+HR')

@section('header')
    <x-page-header title="New Payroll Run" />
    <x-breadcrumb :items="[['Home', '/dashboard'], ['Payroll Runs', '/payroll-runs'], ['New', '#']]" />
@endsection

@section('content')
    <x-card title="Create Payroll Run">
        @if($periods->isEmpty())
            <x-empty-state title="No open payroll periods" description="Open a payroll period before creating a new run." />
        @else
            <form method="POST" action="{{ route('payroll-runs.store') }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="payroll_period_id" class="block text-sm font-medium text-gray-700 mb-1">Payroll period</label>
                        <select id="payroll_period_id" name="payroll_period_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach($periods as $period)
                                <option value="{{ $period->id }}">{{ $period->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Run type</label>
                        <select id="type" name="type" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="standard">Standard</option>
                            <option value="supplementary">Supplementary</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ url('/payroll-runs') }}" class="inline-flex items-center rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Cancel</a>
                    <x-button type="submit" variant="primary">Create run</x-button>
                </div>
            </form>
        @endif
    </x-card>
@endsection
