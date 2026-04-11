@extends('layouts.dashboard')

@section('title', 'Dashboard - YATIS')

@section('content')
    @php
        $userRole = auth()->user()->role;
    @endphp

    @if($userRole === 'business')
        <!-- BUSINESS ACCOUNT SECTIONS -->
        @include('dashboard.dashboard-section')
        @include('dashboard.businesses-section')
        @include('dashboard.my-business-section')
        @include('dashboard.jobs-section')
        @include('dashboard.profile-section')
        @include('dashboard.settings-section')
    @else
        <!-- REGULAR USER SECTIONS -->
        @include('dashboard.dashboard-section')
        @include('dashboard.profile-section')
        @include('dashboard.people-section')
        @include('dashboard.businesses-section')
        @include('dashboard.employers-section')
        @include('dashboard.jobs-section')
        @include('dashboard.destinations-section')
        @include('dashboard.events-section')
        @include('dashboard.groups-section')
        @include('dashboard.messages-section')
        @include('dashboard.settings-section')
        @if($userRole === 'admin')
            @include('dashboard.admin-section')
        @endif
    @endif

    <!-- People Sidebar Component -->
    @include('components.people-sidebar')

@endsection
