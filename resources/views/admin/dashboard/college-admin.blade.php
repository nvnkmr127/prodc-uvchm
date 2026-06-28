@extends('layouts.theme')

@section('title', 'Academic Dashboard')

@include('admin.dashboard.partials.styles')

@section('content')
    <div class="container-fluid">
        @include('admin.dashboard.partials.header')
        @include('admin.dashboard.partials.quick-stats')

        <!-- Dashboard Main Content Grid -->
        <div class="row">
            <!-- Left Column: Primary Analytics -->
            <div class="col-xl-8 col-lg-7">
                @include('admin.dashboard.partials.enquiry-funnel')
                @include('admin.dashboard.partials.payment-collections')
                @include('admin.dashboard.partials.attendance-analytics')
            </div>

            <!-- Right Column: Activity and Quick Actions -->
            <div class="col-xl-4 col-lg-5">
                @include('admin.dashboard.partials.activity-log')
                @include('admin.dashboard.partials.quick-actions')
                @include('admin.dashboard.partials.payment-mode')
                @include('admin.dashboard.partials.pending-collections')
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    @include('admin.dashboard.partials.scripts')
@endpush