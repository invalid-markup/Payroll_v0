@extends('layouts.app')
@section('title', 'Reports — PayEasy+HR')
@section('header')
    <x-page-header title="Statutory Reports" />
    <x-breadcrumb :items="[['Home', '/dashboard'], ['Reports', '/reports']]" />
@endsection
@section('content')
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach([
            ['PAYE Deductions', 'Monthly PAYE tax deductions register for TRA filing', 'paye', 'blue'],
            ['SDL Contributions', 'Skills Development Levy employer contributions (ITX219)', 'sdl', 'amber'],
            ['WCF Contributions', "Worker's Compensation Fund contribution summary", 'wcf', 'orange'],
            ['NSSF/PPF Register', 'Pension fund contributions register', 'nssf', 'purple'],
            ['Payslips', 'Generate individual or bulk employee payslips', 'payslips', 'green'],
            ['Bank Advice', 'Bank transfer advice file for payroll disbursement', 'bank', 'gray'],
        ] as [$title, $desc, $type, $color])
        <div class="bg-white border border-gray-200 rounded-xl p-6 hover:shadow-sm transition-shadow cursor-pointer group">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-lg bg-{{ $color }}-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-{{ $color }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 mb-1">{{ $title }}</h3>
                    <p class="text-sm text-gray-500">{{ $desc }}</p>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100 flex justify-end">
                <button type="button" class="text-sm font-medium text-blue-600 hover:text-blue-700 group-hover:underline">
                    Generate Report →
                </button>
            </div>
        </div>
        @endforeach
    </div>
@endsection
