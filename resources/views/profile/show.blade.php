@extends('layouts.app')

@section('title', 'My Profile — PayEasy+HR')

@section('header')
    <x-page-header title="My Profile" />
    <x-breadcrumb :items="[['Home', '/dashboard'], ['Profile', '#']]" />
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <x-card title="Account Details" class="lg:col-span-2">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Name</p>
                    <p class="mt-1 font-medium text-gray-900">{{ $user->name }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Email</p>
                    <p class="mt-1 text-gray-900">{{ $user->email }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Role</p>
                    <p class="mt-1 text-gray-900">{{ $user->roles->first()?->name ? str_replace('_', ' ', $user->roles->first()->name) : 'Unassigned' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Company ID</p>
                    <p class="mt-1 font-mono text-gray-700">{{ $user->company_id }}</p>
                </div>
            </div>
        </x-card>

        <x-card title="Quick Links">
            <div class="space-y-3">
                <a href="{{ url('/dashboard') }}" class="block rounded-lg bg-gray-50 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100">Return to dashboard</a>
                <a href="{{ url('/payslips') }}" class="block rounded-lg bg-blue-50 px-4 py-3 text-sm font-medium text-blue-700 hover:bg-blue-100">View payslips</a>
                <form method="POST" action="{{ url('/logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg bg-red-50 px-4 py-3 text-left text-sm font-medium text-red-700 hover:bg-red-100">Sign out</button>
                </form>
            </div>
        </x-card>
    </div>
@endsection
