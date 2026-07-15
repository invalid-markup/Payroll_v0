@extends('layouts.app')

@section('title', 'Employees — Payroll System')

@section('header')
    <x-page-header title="Employees">
        <x-slot name="actions">
            @hasanyrole('system_administrator|hr_manager|hr_officer')
            <a href="{{ url('/employees/create') }}">
                <x-button variant="primary" id="btn-add-employee">
                    <svg class="w-4 h-4 mr-2 -ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Employee
                </x-button>
            </a>
            @endhasanyrole
        </x-slot>
    </x-page-header>
    <x-breadcrumb :items="[['Home', '/dashboard'], ['Employees', '/employees']]" />
@endsection

@section('content')

    {{-- Filters Bar --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4 mb-5 flex flex-wrap gap-3 items-center">
        <form method="GET" action="{{ url('/employees') }}" class="flex flex-wrap gap-3 items-center w-full">

            {{-- Search --}}
            <div class="flex-1 min-w-[200px] relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search by name, employee number…"
                    class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    id="employee-search"
                    aria-label="Search employees"
                >
            </div>

            {{-- Status Filter --}}
            <select
                name="status"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                aria-label="Filter by status"
                id="filter-status"
            >
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="terminated" {{ request('status') === 'terminated' ? 'selected' : '' }}>Terminated</option>
            </select>

            {{-- Department Filter --}}
            <select
                name="department_id"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                aria-label="Filter by department"
                id="filter-department"
            >
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') === $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>

            <x-button type="submit" variant="secondary" id="btn-apply-filters">Apply</x-button>

            @if(request()->hasAny(['search', 'status', 'department_id']))
                <a href="{{ url('/employees') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear filters</a>
            @endif
        </form>
    </div>

    {{-- Data Table --}}
    <x-card>
        @if($employees->isEmpty())
            <x-empty-state
                title="No employees found"
                description="No employees match your current filters, or none have been added yet."
            >
                <x-slot name="action">
                    @hasanyrole('system_administrator|hr_manager|hr_officer')
                    <a href="{{ url('/employees/create') }}">
                        <x-button variant="primary" id="btn-empty-add-employee">Add first employee</x-button>
                    </a>
                    @endhasanyrole
                </x-slot>
            </x-empty-state>
        @else
            <div class="overflow-x-auto -mx-6">
                <table class="min-w-full divide-y divide-gray-200 text-sm" aria-label="Employee directory">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Employee #</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="hidden md:table-cell px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Department</th>
                            <th scope="col" class="hidden lg:table-cell px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Employment Type</th>
                            <th scope="col" class="hidden lg:table-cell px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Basic Salary</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($employees as $employee)
                        <tr class="hover:bg-gray-50 transition-colors" id="employee-row-{{ $employee->id }}">
                            <td class="px-6 py-3 font-mono text-xs text-gray-500 whitespace-nowrap">
                                {{ $employee->employee_number }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-semibold text-xs uppercase">
                                        {{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $employee->first_name }} {{ $employee->last_name }}</p>
                                        <p class="text-xs text-gray-500">{{ $employee->job_title }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden md:table-cell px-6 py-3 text-gray-600 whitespace-nowrap">
                                {{ $employee->department->name ?? '—' }}
                            </td>
                            <td class="hidden lg:table-cell px-6 py-3 text-gray-600 whitespace-nowrap capitalize">
                                {{ str_replace('_', ' ', $employee->employment_type) }}
                            </td>
                            <td class="hidden lg:table-cell px-6 py-3 text-right font-mono text-gray-900 whitespace-nowrap">
                                TZS {{ number_format($employee->currentSalary?->basic_salary_amount ?? 0, 0) }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <x-badge :status="$employee->status ?? 'active'" />
                            </td>
                            <td class="px-6 py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ url('/employees/' . $employee->id) }}"
                                       class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                       id="view-employee-{{ $employee->id }}"
                                       aria-label="View {{ $employee->first_name }} {{ $employee->last_name }}">
                                        View
                                    </a>
                                    @hasanyrole('system_administrator|hr_manager|hr_officer')
                                    <span class="text-gray-300">|</span>
                                    <a href="{{ url('/employees/' . $employee->id . '/edit') }}"
                                       class="text-gray-600 hover:text-gray-900 text-sm"
                                       id="edit-employee-{{ $employee->id }}"
                                       aria-label="Edit {{ $employee->first_name }} {{ $employee->last_name }}">
                                        Edit
                                    </a>
                                    @endhasanyrole
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($employees->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        Showing {{ $employees->firstItem() }}–{{ $employees->lastItem() }} of {{ $employees->total() }} employees
                    </p>
                    {{ $employees->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </x-card>

@endsection
