@extends('layouts.theme')
@section('title', 'Alumni Network')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Alumni Network</h1>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">All Graduated Students</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            {{-- The id="dataTable" is what activates all the interactive features --}}
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>Name</th>
                        <th>Course / Batch</th>
                        <th>Current Employer</th>
                        <th>Job Title</th>
                        <th style="width: 15%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alumni as $alumnus)
                    <tr>
                        <td>
                            {{-- Name is now a clickable link to the student's main profile --}}
                            <a href="{{ route('admin.students.show', $alumnus) }}">
                                <strong>{{ $alumnus->name }}</strong>
                            </a>
                        </td>
                        <td>{{ $alumnus->batch->course->name ?? 'N/A' }} - {{ $alumnus->batch->name ?? 'N/A' }}</td>
                        <td>{{ $alumnus->current_employer ?? 'N/A' }}</td>
                        <td>{{ $alumnus->job_title ?? 'N/A' }}</td>
                        <td>
                            <a href="{{ route('admin.students.edit', $alumnus) }}" class="btn btn-warning btn-sm" title="Update Alumni Info">
                                <i class="fas fa-edit"></i> Update Info
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center">No alumni found. Students marked as 'graduated' will appear here.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- This script activates the interactive table features --}}
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            // You can customize options here, for example, default sorting
            "order": [[ 1, "asc" ]] // Sort by the second column (Course / Batch) initially
        });
    });
</script>
@endpush
