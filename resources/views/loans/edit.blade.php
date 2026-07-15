@extends('layouts.app')
@section('title', 'Edit Loan - PayEasy+HR')
@section('header')
    <x-page-header title="Edit Loan" />
    <x-breadcrumb :items="[['Home', '/dashboard'], ['Loans', '/loans'], ['Edit', '#']]" />
@endsection

@section('content')
    <x-card title="Edit Loan Installment">
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

        <div class="mb-6 rounded-lg bg-gray-50 p-4">
            <h3 class="mb-2 text-sm font-medium text-gray-700">Loan Details</h3>
            <div class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                <div>
                    <span class="text-gray-500">Employee:</span>
                    <span class="ml-1 font-medium">{{ $loan->employee->first_name }} {{ $loan->employee->last_name }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Principal:</span>
                    <span class="ml-1 font-medium">TZS {{ number_format($loan->principal_amount, 0) }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Repaid:</span>
                    <span class="ml-1 font-medium">TZS {{ number_format($loan->total_repaid_amount, 0) }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Currently:</span>
                    <x-badge :status="$loan->loan_status" />
                </div>
            </div>
        </div>

        <form action="{{ route('loans.update', $loan->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">New Installment Amount</label>
                    <input type="number" step="0.01" min="1" name="installment_amount" value="{{ old('installment_amount', $loan->installment_amount) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Use this to adjust the monthly deduction amount for this loan.</p>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <a href="{{ route('loans.show', $loan->id) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:w-auto">
                    Update Installment
                </button>
            </div>
        </form>
    </x-card>
@endsection