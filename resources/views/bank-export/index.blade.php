@extends('layouts.app')
@section('title', 'Bank Export — PayEasy+HR')
@section('header')
    <x-page-header title="Bank Export" />
    <x-breadcrumb :items="[['Home', '/dashboard'], ['Bank Export', '#']]" />
@endsection
@section('content')
    <x-card title="Bank Export Files">
        <x-empty-state title="No exports" description="Bank export file generator coming soon." />
    </x-card>
@endsection
