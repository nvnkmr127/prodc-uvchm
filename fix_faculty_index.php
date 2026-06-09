<?php
$content = file_get_contents('resources/views/admin/faculty/index.blade.php');

$start = strpos($content, '<div class="card-body">');
$end = strpos($content, '{{-- Bulk Actions Modal --}}');

$new_body = <<<'HTML'
    <div class="card-body">
        @if ($faculties->isEmpty())
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-gray-300 mb-3"></i>
                <h5 class="text-gray-500">No Faculty Members Found</h5>
                <p class="text-muted mb-4">No faculty members with the 'staff' role exist yet.</p>
                <a href="{{ route('admin.faculty.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-2"></i>Create Your First Faculty Member
                </a>
            </div>
        @else
            {{-- Card View Container --}}
            <div id="facultyCardsContainer">
                @foreach ($faculties as $faculty)
                    <div class="faculty-card faculty-item mb-3" 
                         data-name="{{ strtolower($faculty->name) }}" 
                         data-email="{{ strtolower($faculty->email) }}"
                         data-department="{{ strtolower($faculty->department ?? '') }}"
                         data-employee-id="{{ strtolower($faculty->employee_id ?? '') }}"
                         data-has-template="{{ $faculty->salaryTemplate ? 'true' : 'false' }}">
                        
                        <div class="card border-left-{{ $faculty->salaryTemplate ? 'success' : 'warning' }}">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-center">
                                        <div class="faculty-avatar bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="fas fa-user fa-lg"></i>
                                        </div>
                                        <div class="mt-2">
                                            <span class="badge badge-{{ $faculty->subjects->count() > 0 ? 'success' : 'warning' }}">
                                                {{ $faculty->subjects->count() }} Subject{{ $faculty->subjects->count() != 1 ? 's' : '' }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h6 class="font-weight-bold text-gray-800 mb-1">
                                            {{ $faculty->name }}
                                            @if($faculty->employee_id)
                                                <small class="text-muted">({{ $faculty->employee_id }})</small>
                                            @endif
                                        </h6>
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-envelope fa-sm mr-1"></i>{{ $faculty->email }}
                                        </p>
                                        @if($faculty->phone)
                                            <p class="text-muted mb-1">
                                                <i class="fas fa-phone fa-sm mr-1"></i>{{ $faculty->phone }}
                                            </p>
                                        @endif
                                        @if($faculty->department)
                                            <p class="text-muted mb-1">
                                                <i class="fas fa-building fa-sm mr-1"></i>{{ $faculty->department }}
                                            </p>
                                        @endif
                                    </div>
                                    
                                    <div class="col-md-4">
                                        @if($faculty->subjects->count() > 0)
                                            <div class="mb-2">
                                                <small class="text-muted font-weight-bold">Assigned Subjects:</small>
                                                <div class="mt-1">
                                                    @foreach($faculty->subjects->take(3) as $subject)
                                                        <span class="badge badge-info mr-1 mb-1">{{ $subject->name }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                        
                                        <div class="mb-2">
                                            <small class="text-muted font-weight-bold">Salary Setup:</small>
                                            <div class="mt-1">
                                                @if($faculty->salaryTemplate)
                                                    <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>{{ $faculty->salaryTemplate->name }}</span>
                                                @else
                                                    <span class="badge badge-warning"><i class="fas fa-exclamation-triangle mr-1"></i>No Template</span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <div class="btn-group btn-group-sm w-100" role="group">
                                            <a href="{{ route('admin.faculty.subjects.edit', $faculty) }}" 
                                               class="btn btn-info" title="Manage Subjects">
                                                <i class="fas fa-book mr-1"></i>Subjects
                                            </a>
                                            <a href="{{ route('admin.faculty.salary.show', $faculty) }}" 
                                               class="btn btn-success" title="Manage Salary">
                                                <i class="fas fa-dollar-sign mr-1"></i>Salary
                                            </a>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" 
                                                        data-toggle="dropdown" title="More Actions">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="{{ route('admin.faculty.edit', $faculty) }}">
                                                        <i class="fas fa-edit mr-2"></i>Edit Details
                                                    </a>
                                                    <a class="dropdown-item" href="#" onclick="viewSchedule({{ $faculty->id }})">
                                                        <i class="fas fa-calendar mr-2"></i>View Schedule
                                                    </a>
                                                    <a class="dropdown-item" href="#" onclick="viewAttendance({{ $faculty->id }})">
                                                        <i class="fas fa-clock mr-2"></i>Attendance
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-danger" href="#" 
                                                       onclick="confirmDelete({{ $faculty->id }}, '{{ $faculty->name }}')">
                                                        <i class="fas fa-trash mr-2"></i>Delete
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Table Headers (Hidden by default) --}}
            <table class="table table-bordered d-none" id="facultyTable">
                <thead class="thead-light">
                    <tr>
                        <th>Faculty</th>
                        <th>Contact</th>
                        <th>Department</th>
                        <th>Salary Template</th>
                        <th style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="facultyTableBody">
                    @foreach ($faculties as $faculty)
                        <tr class="faculty-table-row faculty-item d-none" 
                            data-name="{{ strtolower($faculty->name) }}" 
                            data-email="{{ strtolower($faculty->email) }}"
                            data-department="{{ strtolower($faculty->department ?? '') }}"
                            data-employee-id="{{ strtolower($faculty->employee_id ?? '') }}"
                            data-has-template="{{ $faculty->salaryTemplate ? 'true' : 'false' }}">
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="faculty-avatar bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mr-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold">{{ $faculty->name }}</div>
                                        @if($faculty->employee_id)
                                            <small class="text-muted">{{ $faculty->employee_id }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>{{ $faculty->email }}</div>
                                @if($faculty->phone)
                                    <small class="text-muted">{{ $faculty->phone }}</small>
                                @endif
                            </td>
                            <td>{{ $faculty->department ?? 'N/A' }}</td>
                            <td>
                                @if($faculty->salaryTemplate)
                                    <span class="badge badge-success">{{ $faculty->salaryTemplate->name }}</span>
                                @else
                                    <span class="badge badge-warning">Not Assigned</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('admin.faculty.subjects.edit', $faculty) }}" 
                                       class="btn btn-info btn-sm" title="Manage Subjects">
                                        <i class="fas fa-book"></i>
                                    </a>
                                    <a href="{{ route('admin.faculty.salary.show', $faculty) }}" 
                                       class="btn btn-success btn-sm" title="Manage Salary">
                                        <i class="fas fa-dollar-sign"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                            data-toggle="dropdown" title="More Actions">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{ route('admin.faculty.edit', $faculty) }}">
                                            <i class="fas fa-edit mr-2"></i>Edit
                                        </a>
                                        <a class="dropdown-item text-danger" href="#" 
                                           onclick="confirmDelete({{ $faculty->id }}, '{{ $faculty->name }}')">
                                            <i class="fas fa-trash mr-2"></i>Delete
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

HTML;

$final = substr($content, 0, $start) . $new_body . substr($content, $end);
file_put_contents('resources/views/admin/faculty/index.blade.php', $final);
