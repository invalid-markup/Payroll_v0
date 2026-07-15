@extends('layouts.app')

@section('title', 'Company Profile — PayEasy+HR')

@section('header')
    <x-page-header title="Company Profile" />
    <x-breadcrumb :items="[['Home', '/dashboard'], ['Company', '#']]" />
@endsection

@section('content')
    <div class="space-y-6">
        <x-alert type="info" message="Company settings are tenant-scoped and should only be edited by System Administrators." />

        @if(! $profile)
            <x-card title="Company Settings">
                <x-empty-state
                    title="No company profile found"
                    description="Create the company profile from the company settings screen or API before inviting additional users."
                />
            </x-card>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <x-card title="Company Details" class="lg:col-span-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Company name</p>
                            <p class="mt-1 font-medium text-gray-900">{{ $profile->company_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Company ID</p>
                            <p class="mt-1 font-mono text-gray-700">{{ $profile->company_id }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">TIN</p>
                            <p class="mt-1 text-gray-900">{{ $profile->tin ?: 'Not set' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Registration</p>
                            <p class="mt-1 text-gray-900">{{ $profile->registration_number ?: 'Not set' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Address</p>
                            <p class="mt-1 text-gray-900">{{ $profile->address ?: 'Not set' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Phone</p>
                            <p class="mt-1 text-gray-900">{{ $profile->phone ?: 'Not set' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Email</p>
                            <p class="mt-1 text-gray-900">{{ $profile->email ?: 'Not set' }}</p>
                        </div>
                    </div>
                </x-card>

                <div class="space-y-5">
                    <x-card title="Payroll Rules">
                        <dl class="space-y-4 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">Working days / month</dt>
                                <dd class="font-semibold text-gray-900">{{ $profile->working_days_per_month }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">Financial year start</dt>
                                <dd class="font-semibold text-gray-900">Month {{ $profile->financial_year_start_month }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">SDL enabled</dt>
                                <dd><x-badge :status="$profile->sdl_enabled ? 'sent' : 'draft'" /></dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">WCF enabled</dt>
                                <dd><x-badge :status="$profile->wcf_enabled ? 'sent' : 'draft'" /></dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500">SDL threshold</dt>
                                <dd class="font-semibold text-gray-900">{{ $profile->sdl_employee_threshold }}</dd>
                            </div>
                        </dl>
                    </x-card>
                </div>
            </div>

            <x-card title="Public Holidays">
                @if($holidays->isEmpty())
                    <x-empty-state title="No public holidays" description="Add company holidays to support payroll calendar planning." />
                @else
                    <div class="overflow-x-auto -mx-6">
                        <table class="min-w-full divide-y divide-gray-200 text-sm" aria-label="Public holidays">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($holidays as $holiday)
                                    <tr>
                                        <td class="px-6 py-3 whitespace-nowrap text-gray-900">{{ \Carbon\Carbon::parse($holiday->date)->format('d M Y') }}</td>
                                        <td class="px-6 py-3 text-gray-700">{{ $holiday->name }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        @endif
    </div>
@endsection
