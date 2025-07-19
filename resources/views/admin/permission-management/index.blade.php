@extends('layouts.theme')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Permission Management Dashboard</h3>
                </div>
                <div class="card-body">
                    <!-- Dashboard content here -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-key"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Permissions</span>
                                    <span class="info-box-number">{{ $totalPermissions }}</span>
                                </div>
                            </div>
                        </div>
                        <!-- Add more dashboard widgets -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection