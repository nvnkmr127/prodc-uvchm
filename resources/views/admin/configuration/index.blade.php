@extends('layouts.theme')
@section('title', 'System Configuration')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">System Configuration</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-header p-0">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item"><a class="nav-link active" id="courses-tab" data-toggle="tab" href="#courses" role="tab">Courses</a></li>
            <li class="nav-item"><a class="nav-link" id="batches-tab" data-toggle="tab" href="#batches" role="tab">Batches</a></li>
            <li class="nav-item"><a class="nav-link" id="subjects-tab" data-toggle="tab" href="#subjects" role="tab">Subjects</a></li>
            <li class="nav-item"><a class="nav-link" id="fees-tab" data-toggle="tab" href="#fees" role="tab">Fee Categories</a></li>
            <li class="nav-item"><a class="nav-link" id="hr-tab" data-toggle="tab" href="#hr" role="tab">HR Settings</a></li>
            <li class="nav-item"><a class="nav-link" id="roles-tab" data-toggle="tab" href="#roles" role="tab">Roles & Permissions</a></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="myTabContent">
            <!-- Courses Tab -->
            <div class="tab-pane fade show active" id="courses" role="tabpanel">
               @include('admin.courses.index', ['courses' => $courses])

            </div>
            <!-- Batches Tab -->
            <div class="tab-pane fade" id="batches" role="tabpanel">
                @include('admin.batches.index', ['batches' => $batches])
            </div>
            <!-- Subjects Tab -->
            <div class="tab-pane fade" id="subjects" role="tabpanel">
                @include('admin.subjects.index', ['subjects' => $subjects])
            </div>
            <!-- Fee Categories Tab -->
            <div class="tab-pane fade" id="fees" role="tabpanel">
                {{-- You would create and include a partial view for fee categories here --}}
                <p>Fee Categories Management Area</p>
            </div>
            <!-- HR Settings Tab -->
            <div class="tab-pane fade" id="hr" role="tabpanel">
                 {{-- You would create and include a partial view for leave types here --}}
                <p>Leave Types & Other HR Settings Area</p>
            </div>
            <!-- Roles & Permissions Tab -->
            <div class="tab-pane fade" id="roles" role="tabpanel">
                @include('admin.roles.index', ['roles' => $roles])
            </div>
        </div>
    </div>
</div>
@endsection
