@extends('layouts.app')
@section('title', 'Edit Employee — PayEasy+HR')
@section('header')
    <x-page-header title="Edit Employee" />
    <x-breadcrumb :items="[['Home', '/dashboard'], ['Employees', '/employees'], ['Edit', '#']]" />
@endsection

@section('content')
    <x-card title="Edit Employee: {{ $employee->first_name }} {{ $employee->last_name }}">
        @if ($errors->any())
            <div class="mb-4 p-4 border-l-4 border-red-500 bg-red-100 text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('employees.update', $employee->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">First Name</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $employee->first_name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Last Name</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $employee->last_name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Employee Number</label>
                    <input type="text" name="employee_number" value="{{ old('employee_number', $employee->employee_number) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Job Title</label>
                    <input type="text" name="job_title" value="{{ old('job_title', $employee->job_title) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Department</label>
                    <select name="department_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        <option value="">Select Department...</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Branch</label>
                    <select name="branch_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        <option value="">Select Branch...</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id', $employee->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        @foreach(array_unique(array_merge(['active', 'terminated'], [$employee->status ?? ''])) as $st)
                            @if($st)
                                <option value="{{ strtolower($st) }}" {{ old('status', strtolower($employee->status)) == strtolower($st) ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Employment Type</label>
                    <select name="employment_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        <option value="permanent" {{ old('employment_type', strtolower($employee->employment_type)) == 'permanent' ? 'selected' : '' }}>Permanent</option>
                        <option value="contract" {{ old('employment_type', strtolower($employee->employment_type)) == 'contract' ? 'selected' : '' }}>Contract</option>
                        <option value="casual" {{ old('employment_type', strtolower($employee->employment_type)) == 'casual' ? 'selected' : '' }}>Casual</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Resident Status</label>
                    <select name="resident_status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        <option value="resident" {{ old('resident_status', strtolower($employee->resident_status)) == 'resident' ? 'selected' : '' }}>Resident</option>
                        <option value="non_resident" {{ old('resident_status', strtolower($employee->resident_status)) == 'non_resident' ? 'selected' : '' }}>Non-Resident</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Hire Date</label>
                    <input type="date" name="hire_date" value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">TIN</label>
                    <input type="text" name="tin" value="{{ old('tin', $employee->tin) }}" placeholder="123-456-789" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">NSSF Number</label>
                    <input type="text" name="nssf_number" value="{{ old('nssf_number', $employee->nssf_number) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="secondary_employment_flag" value="1" {{ old('secondary_employment_flag', $employee->secondary_employment_flag) ? 'checked' : '' }} class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-sm font-medium text-gray-700">Secondary Employment</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <a href="{{ route('employees.show', $employee->id) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:w-auto">
                    Update Employee
                </button>
            </div>
        </form>
    </x-card>
@endsection