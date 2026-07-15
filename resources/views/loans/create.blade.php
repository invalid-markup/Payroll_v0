@extends('layouts.app')
@section('title', 'Register Loan - PayEasy+HR')
@section('header')
    <x-page-header title="Register Loan" />
    <x-breadcrumb :items="[['Home', '/dashboard'], ['Loans', '/loans'], ['New Loan', '#']]" />
@endsection

@section('content')
    <x-card title="New Loan">
        @if ($errors->any())
            <x-alert type="error" message="Please correct the errors below." />
            <div class="mb-4 p-4 border-l-4 border-red-500 bg-red-100 text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('loans.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Employee</label>
                    <select name="employee_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        <option value="">Select Employee...</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->employee_number }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Start Payroll Period</label>
                    <select name="start_period_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        <option value="">Select Period...</option>
                        @foreach($periods as $period)
                            <option value="{{ $period->id }}" {{ old('start_period_id') == $period->id ? 'selected' : '' }}>{{ $period->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Principal Amount</label>
                    <input type="number" step="0.01" min="1" name="total_amount" value="{{ old('total_amount') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Installment Amount</label>
                    <input type="number" step="0.01" min="1" name="installment_amount" value="{{ old('installment_amount') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <a href="{{ route('loans.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:w-auto">
                    Save Loan
                </button>
            </div>
        </form>
    </x-card>
@endsection