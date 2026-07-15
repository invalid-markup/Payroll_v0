@extends('layouts.app')

@section('title', 'Audit Logs — PayEasy+HR')

@section('header')
    <x-page-header title="Audit Trail">
        <x-slot name="badge">
            <x-hard-record-badge />
        </x-slot>
    </x-page-header>
    <x-breadcrumb :items="[['Home', '/dashboard'], ['Audit', '#']]" />
@endsection

@section('content')
    <div class="space-y-5">
        <x-alert type="info" message="Audit records are permanent and cannot be modified or deleted. Minimum retention: 7 years." />

        <x-card title="Audit Events">
            @if(empty($logs) || $logs->isEmpty())
                <x-empty-state
                    title="No audit logs"
                    description="Activity will appear here once staff start creating and updating records."
                />
            @else
                <div class="overflow-x-auto -mx-6">
                    <table class="min-w-full divide-y divide-gray-200 text-sm" aria-label="Audit logs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Timestamp</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Entity</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Event</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Summary</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach($logs as $log)
                                <tr>
                                    <td class="px-6 py-3 whitespace-nowrap text-gray-900">{{ $log->created_at?->format('d M Y, H:i') }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-gray-700">{{ $log->user?->name ?? 'System' }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-gray-700">{{ class_basename($log->model) ?: 'Unknown' }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap"><x-badge :status="$log->audit_event_type" /></td>
                                    <td class="px-6 py-3 text-gray-600">{{ $log->model_id ? 'Record ' . $log->model_id : 'No record id captured' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
@endsection
