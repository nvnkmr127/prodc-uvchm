@extends('layouts.theme')

@section('title', 'Address Report')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Address Report</h1>
        <div>
            <button onclick="window.print()" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-print fa-sm text-white-50"></i> Print Report
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters & Grouping</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.reports.address.index') }}" method="GET" class="row">
                <div class="col-md-3 mb-3">
                    <label>Course</label>
                    <select name="course_id" class="form-control select2">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ $courseId == $course->id ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label>Type</label>
                    <select name="type" class="form-control">
                        <option value="">All Types</option>
                        <option value="Student" {{ $type == 'Student' ? 'selected' : '' }}>Student</option>
                        <option value="Enquiry" {{ $type == 'Enquiry' ? 'selected' : '' }}>Enquiry</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label>Group By</label>
                    <select name="group_by" class="form-control">
                        <option value="none" {{ $groupBy == 'none' ? 'selected' : '' }}>None</option>
                        <option value="address" {{ $groupBy == 'address' ? 'selected' : '' }}>Address / Village</option>
                        <option value="course" {{ $groupBy == 'course' ? 'selected' : '' }}>Course</option>
                        <option value="type" {{ $groupBy == 'type' ? 'selected' : '' }}>Type (Student/Enquiry)</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label>Search Address/Name</label>
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ $search }}">
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                    <a href="{{ route('admin.reports.address.index') }}" class="btn btn-secondary ml-2">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="addressTable" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Mobile</th>
                            <th>Address / Village</th>
                            <th>Course</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($groupBy !== 'none')
                            @php $count = 1; @endphp
                            @forelse($results as $group => $items)
                                <tr class="bg-gray-100">
                                    <td colspan="7" class="font-weight-bold text-primary italic">
                                        <i class="fas fa-layer-group mr-2"></i> {{ $group }} ({{ count($items) }})
                                    </td>
                                </tr>
                                @foreach($items as $item)
                                    <tr>
                                        <td>{{ $count++ }}</td>
                                        <td>{{ $item->name }}</td>
                                        <td>
                                            <span class="badge {{ $item->entity_type == 'Student' ? 'badge-success' : 'badge-info' }}">
                                                {{ $item->entity_type }}
                                            </span>
                                        </td>
                                        <td>{{ $item->phone }}</td>
                                        <td>{{ $item->address ?: 'N/A' }}</td>
                                        <td>{{ $item->course_name ?: 'N/A' }}</td>
                                        <td>
                                            <span class="badge badge-light border">{{ ucfirst($item->status) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No data found</td>
                                </tr>
                            @endforelse
                        @else
                            @forelse($results as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->name }}</td>
                                    <td>
                                        <span class="badge {{ $item->entity_type == 'Student' ? 'badge-success' : 'badge-info' }}">
                                            {{ $item->entity_type }}
                                        </span>
                                    </td>
                                    <td>{{ $item->phone }}</td>
                                    <td>{{ $item->address ?: 'N/A' }}</td>
                                    <td>{{ $item->course_name ?: 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-light border">{{ ucfirst($item->status) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No data found</td>
                                </tr>
                            @endforelse
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .navbar, .sidebar, .card-header, .btn, form {
            display: none !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .container-fluid {
            padding: 0 !important;
        }
        .table-responsive {
            overflow: visible !important;
        }
    }
</style>
@endsection
