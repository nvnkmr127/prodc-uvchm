@extends('layouts.theme')
@section('title', 'Manage Academic Years')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Academic Years</h1>
    <a href="{{ route('admin.academic-years.create') }}" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Add New Academic Year</a>
</div>

@if(session('success'))
    <div class="alert alert-success border-left-success shadow">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger border-left-danger shadow">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
    </div>
@endif

<!-- Current Academic Year Selector -->
<div class="card shadow mb-4 border-left-primary">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h6 class="font-weight-bold text-primary mb-2">
                    <i class="fas fa-calendar-check"></i> Currently Viewing Academic Year
                </h6>
                <p class="mb-0 text-gray-700">
                    Select which academic year to view across the system
                </p>
            </div>
            <div class="col-md-6">
                <form action="{{ route('admin.academic-years.switch') }}" method="POST" id="quickSwitchForm">
                    @csrf
                    <div class="input-group">
                        <select name="academic_year_id" class="form-control form-control-lg" onchange="this.form.submit()">
                            @foreach($years as $year)
                                <option value="{{ $year->id }}" {{ (session('selected_academic_year_id', $years->where('is_current', true)->first()?->id) == $year->id) ? 'selected' : '' }}>
                                    {{ $year->name }}
                                    @if($year->is_current) (Current Year) @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-sync-alt"></i> Switch
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Defined Academic Years</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Year Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Batches</th>
                        <th>Students</th>
                        <th>Status</th>
                        <th style="width: 20%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($years as $year)
                        <tr>
                            <td>{{ $year->name }}</td>
                            <td>{{ \Carbon\Carbon::parse($year->start_date)->format('d M, Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($year->end_date)->format('d M, Y') }}</td>
                            <td>
                                <span class="badge badge-info shadow-sm">{{ $year->batches_count }} Batches</span>
                            </td>
                            <td>
                                <span class="badge badge-primary shadow-sm">{{ $year->students_count }} Students</span>
                            </td>
                            <td>
                                @if($year->is_current)
                                    <span class="badge badge-success shadow-sm"><i class="fas fa-check-circle me-1"></i>Active Current</span>
                                @else
                                    <span class="badge badge-secondary shadow-sm">Inactive</span>
                                @endif

                                @if($year->auto_switch_enabled)
                                    <div class="mt-1"><span class="badge badge-light border text-info shadow-sm small"><i class="fas fa-magic me-1"></i>Auto-Switch</span></div>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    @if(!$year->is_current)
                                        <form action="{{ route('admin.academic-years.set-current', $year) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-success btn-sm" title="Set as Current System Year">
                                                <i class="fas fa-check"></i> Activate
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('admin.academic-years.edit', $year) }}" class="btn btn-warning btn-sm" title="Edit Properties"><i class="fas fa-edit"></i></a>
                                    <form action="{{ route('admin.academic-years.destroy', $year) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure? All related data checks will be performed.')" title="Delete Academic Year"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No academic years found. Add one to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
