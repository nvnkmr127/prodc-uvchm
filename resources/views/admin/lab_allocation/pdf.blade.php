<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportTitle }} - {{ $academicYear->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .college-logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 10px;
        }
        
        .college-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .college-details {
            font-size: 11px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .report-title {
            font-size: 18px;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 5px;
        }
        
        .academic-year {
            font-size: 14px;
            color: #3498db;
            font-weight: bold;
        }
        
        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
            padding: 10px 0;
        }
        
        .statistics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            display: block;
        }
        
        .stat-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }
        
        .allocation-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        
        .batch-container {
            margin-bottom: 25px;
            break-inside: avoid;
        }
        
        .batch-header {
            background: #3498db;
            color: white;
            padding: 10px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .batch-info {
            background: #ecf0f1;
            padding: 8px 10px;
            font-size: 11px;
            border-left: 4px solid #3498db;
        }
        
        .group-container {
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .group-header {
            background: #27ae60;
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 12px;
        }
        
        .group-details {
            background: #d5f4e6;
            padding: 6px 12px;
            font-size: 10px;
        }
        
        .students-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 5px;
            padding: 10px;
            background: white;
        }
        
        .student-item {
            padding: 5px;
            border: 1px solid #eee;
            border-radius: 3px;
            font-size: 10px;
        }
        
        .student-name {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .student-enrollment {
            color: #666;
            font-size: 9px;
        }
        
        .lab-utilization {
            margin-bottom: 20px;
        }
        
        .utilization-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .utilization-table th,
        .utilization-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        
        .utilization-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .utilization-bar {
            background: #ecf0f1;
            height: 15px;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .utilization-fill {
            height: 100%;
            border-radius: 3px;
        }
        
        .util-low { background: #2ecc71; }
        .util-medium { background: #f39c12; }
        .util-high { background: #e74c3c; }
        
        .unassigned-section {
            margin-top: 20px;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
        }
        
        .unassigned-title {
            color: #856404;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .unassigned-list {
            font-size: 10px;
            color: #856404;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #f8f9fa;
            border-top: 1px solid #ddd;
            padding: 10px;
            font-size: 9px;
            text-align: center;
            color: #666;
        }
        
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .summary-table th,
        .summary-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            font-size: 11px;
        }
        
        .summary-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-success { color: #27ae60; }
        .text-warning { color: #f39c12; }
        .text-danger { color: #e74c3c; }
        
        @media print {
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    {{-- Header Section --}}
    <div class="header">
        @if($collegeInfo['logo'])
            <div class="college-logo">
                <img src="{{ public_path('storage/' . $collegeInfo['logo']) }}" alt="College Logo" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
        @endif
        
        <div class="college-name">{{ $collegeInfo['name'] }}</div>
        
        <div class="college-details">
            {{ $collegeInfo['address'] }}<br>
            Phone: {{ $collegeInfo['phone'] }} | Email: {{ $collegeInfo['email'] }}<br>
            Website: {{ $collegeInfo['website'] }} | Code: {{ $collegeInfo['code'] }}<br>
            <strong>Affiliated to: {{ $collegeInfo['affiliation'] }}</strong>
        </div>
        
        <div class="report-title">{{ $reportTitle }}</div>
        <div class="academic-year">Academic Year: {{ $academicYear->name }}</div>
    </div>
    
    {{-- Meta Information --}}
    <div class="meta-info">
        <div>
            <strong>Generated On:</strong> {{ $generatedAt->format('d M Y, h:i A') }}<br>
            <strong>Generated By:</strong> {{ $generatedBy->name }}
        </div>
        <div class="text-right">
            <strong>Report Period:</strong> 
            {{ \Carbon\Carbon::parse($academicYear->start_date)->format('d M Y') }} to 
            {{ \Carbon\Carbon::parse($academicYear->end_date)->format('d M Y') }}<br>
            <strong>Total Batches:</strong> {{ $batches->count() }}
        </div>
    </div>
    
    {{-- Statistics Section --}}
    <div class="allocation-section">
        <div class="section-title">📊 Allocation Summary</div>
        
        <div class="statistics-grid">
            <div class="stat-card">
                <span class="stat-number">{{ $statistics['total_students'] }}</span>
                <span class="stat-label">Total Students</span>
            </div>
            <div class="stat-card">
                <span class="stat-number text-success">{{ $statistics['allocated_students'] }}</span>
                <span class="stat-label">Allocated Students</span>
            </div>
            <div class="stat-card">
                <span class="stat-number {{ $statistics['unassigned_students'] > 0 ? 'text-warning' : 'text-success' }}">{{ $statistics['unassigned_students'] }}</span>
                <span class="stat-label">Unassigned Students</span>
            </div>
            <div class="stat-card">
                <span class="stat-number text-success">{{ $statistics['allocation_percentage'] }}%</span>
                <span class="stat-label">Allocation Rate</span>
            </div>
        </div>
        
        <div class="statistics-grid">
            <div class="stat-card">
                <span class="stat-number">{{ $statistics['total_groups'] }}</span>
                <span class="stat-label">Lab Groups</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">{{ $statistics['total_labs'] }}</span>
                <span class="stat-label">Labs Used</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">{{ $statistics['average_group_size'] }}</span>
                <span class="stat-label">Avg Group Size</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">{{ count($statistics['course_breakdown']) }}</span>
                <span class="stat-label">Courses</span>
            </div>
        </div>
    </div>
    
    {{-- Lab Utilization --}}
    <div class="lab-utilization">
        <div class="section-title">🧪 Lab Utilization Report</div>
        <table class="utilization-table">
            <thead>
                <tr>
                    <th>Lab Name</th>
                    <th>Capacity</th>
                    <th>Allocated</th>
                    <th>Groups</th>
                    <th>Utilization</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statistics['lab_utilization'] as $labName => $data)
                    @php
                        $utilization = $data['capacity'] > 0 ? round(($data['used'] / $data['capacity']) * 100, 1) : 0;
                        $status = $utilization >= 90 ? 'High' : ($utilization >= 70 ? 'Medium' : 'Low');
                        $statusClass = $utilization >= 90 ? 'text-danger' : ($utilization >= 70 ? 'text-warning' : 'text-success');
                    @endphp
                    <tr>
                        <td class="font-bold">{{ $labName }}</td>
                        <td class="text-center">{{ $data['capacity'] }}</td>
                        <td class="text-center">{{ $data['used'] }}</td>
                        <td class="text-center">{{ $data['groups'] }}</td>
                        <td>
                            <div class="utilization-bar">
                                <div class="utilization-fill {{ $utilization >= 90 ? 'util-high' : ($utilization >= 70 ? 'util-medium' : 'util-low') }}" 
                                     style="width: {{ $utilization }}%"></div>
                            </div>
                            <small>{{ $utilization }}%</small>
                        </td>
                        <td class="text-center {{ $statusClass }}">{{ $status }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    {{-- Course Breakdown --}}
    @if($format === 'detailed')
    <div class="allocation-section page-break">
        <div class="section-title">📚 Course-wise Breakdown</div>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Course Name</th>
                    <th>Batches</th>
                    <th>Total Students</th>
                    <th>Allocated Students</th>
                    <th>Groups Created</th>
                    <th>Allocation %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statistics['course_breakdown'] as $courseName => $data)
                    @php
                        $courseAllocationRate = $data['total_students'] > 0 ? round(($data['allocated_students'] / $data['total_students']) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td class="font-bold">{{ $courseName }}</td>
                        <td class="text-center">{{ $data['batches'] }}</td>
                        <td class="text-center">{{ $data['total_students'] }}</td>
                        <td class="text-center">{{ $data['allocated_students'] }}</td>
                        <td class="text-center">{{ $data['groups'] }}</td>
                        <td class="text-center {{ $courseAllocationRate == 100 ? 'text-success' : ($courseAllocationRate >= 80 ? 'text-warning' : 'text-danger') }}">
                            {{ $courseAllocationRate }}%
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    {{-- Detailed Allocation --}}
    <div class="allocation-section page-break">
        <div class="section-title">👥 Detailed Lab Group Allocation</div>
        
        @foreach($batches as $batch)
            <div class="batch-container">
                <div class="batch-header">
                    {{ $batch->course->name }} - {{ $batch->name }}
                </div>
                
                <div class="batch-info">
                    <strong>Total Students:</strong> {{ $batch->students()->where('status', 'active')->count() }} | 
                    <strong>Groups:</strong> {{ $batch->practicalGroups->count() }} | 
                    <strong>Allocated:</strong> {{ $batch->practicalGroups->sum(function($g) { return $g->students->count(); }) }} students
                </div>
                
                @forelse($batch->practicalGroups as $group)
                    <div class="group-container">
                        <div class="group-header">
                            🧪 {{ $group->name }}
                        </div>
                        
                        <div class="group-details">
                            <strong>Lab:</strong> {{ $group->classroom->name }} | 
                            <strong>Capacity:</strong> {{ $group->classroom->capacity }} | 
                            <strong>Students:</strong> {{ $group->students->count() }} | 
                            <strong>Utilization:</strong> {{ $group->classroom->capacity > 0 ? round(($group->students->count() / $group->classroom->capacity) * 100, 1) : 0 }}%
                        </div>
                        
                        @if($group->students->count() > 0)
                            <div class="students-table">
                                <table style="width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 11px;">
                                    <thead>
                                        <tr style="background: #f8f9fa; border: 1px solid #ddd;">
                                            <th style="border: 1px solid #ddd; padding: 10px; text-align: left; font-weight: bold; width: 8%;">S.No</th>
                                            <th style="border: 1px solid #ddd; padding: 10px; text-align: left; font-weight: bold; width: 45%;">Student Name</th>
                                            <th style="border: 1px solid #ddd; padding: 10px; text-align: left; font-weight: bold; width: 20%;">Roll Number</th>
                                            <th style="border: 1px solid #ddd; padding: 10px; text-align: left; font-weight: bold; width: 27%;">Father's Name</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($group->students as $index => $student)
                                            <tr style="{{ $index % 2 == 0 ? 'background: #f9f9f9;' : 'background: white;' }}">
                                                <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">{{ $index + 1 }}</td>
                                                <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold; color: #2c3e50;">
                                                    {{ $student->name }}
                                                </td>
                                                <td style="border: 1px solid #ddd; padding: 8px; text-align: center; color: #666;">
                                                    {{ $student->enrollment_number ?? 'Not Assigned' }}
                                                </td>
                                                <td style="border: 1px solid #ddd; padding: 8px; color: #666;">
                                                    {{ $student->father_name ?? 'Not Available' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div style="padding: 15px; text-align: center; color: #666; font-style: italic;">
                                No students assigned to this group
                            </div>
                        @endif
                    </div>
                @empty
                    <div style="padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; margin: 10px 0; border-radius: 5px;">
                        <strong>⚠️ No lab groups created for this batch</strong>
                    </div>
                @endforelse
            </div>
        @endforeach
    </div>
    
    {{-- Complete Student List by Batch --}}
    @if($format === 'detailed')
    <div class="allocation-section page-break">
        <div class="section-title">📋 Complete Student Directory by Batch</div>
        
        @foreach($batches as $batch)
            <div style="margin-bottom: 30px;">
                <h3 style="background: #2c3e50; color: white; padding: 12px; margin: 0; font-size: 14px;">
                    {{ $batch->course->name }} - {{ $batch->name }}
                    <span style="float: right; font-size: 12px;">
                        Total: {{ $batch->students()->where('status', 'active')->count() }} students
                    </span>
                </h3>
                
                @php
                    $allStudents = $batch->students()->where('status', 'active')->orderBy('name')->get();
                @endphp
                
                @if($allStudents->count() > 0)
                    <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
                        <thead>
                            <tr style="background: #ecf0f1; border: 1px solid #bdc3c7;">
                                <th style="border: 1px solid #bdc3c7; padding: 8px; text-align: left; font-weight: bold;">S.No</th>
                                <th style="border: 1px solid #bdc3c7; padding: 8px; text-align: left; font-weight: bold;">Student Name</th>
                                <th style="border: 1px solid #bdc3c7; padding: 8px; text-align: left; font-weight: bold;">Roll Number</th>
                                <th style="border: 1px solid #bdc3c7; padding: 8px; text-align: left; font-weight: bold;">Father's Name</th>
                                <th style="border: 1px solid #bdc3c7; padding: 8px; text-align: left; font-weight: bold;">Mobile</th>
                                <th style="border: 1px solid #bdc3c7; padding: 8px; text-align: left; font-weight: bold;">Lab Group</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allStudents as $index => $student)
                                @php
                                    // Find which group this student belongs to
                                    $studentGroup = null;
                                    foreach($batch->practicalGroups as $group) {
                                        if($group->students->contains('id', $student->id)) {
                                            $studentGroup = $group;
                                            break;
                                        }
                                    }
                                @endphp
                                <tr style="{{ $index % 2 == 0 ? 'background: #f8f9fa;' : 'background: white;' }}">
                                    <td style="border: 1px solid #bdc3c7; padding: 6px; text-align: center;">{{ $index + 1 }}</td>
                                    <td style="border: 1px solid #bdc3c7; padding: 6px; font-weight: bold; color: #2c3e50;">
                                        {{ $student->name }}
                                    </td>
                                    <td style="border: 1px solid #bdc3c7; padding: 6px; text-align: center; color: #666;">
                                        {{ $student->enrollment_number ?? 'Not Assigned' }}
                                    </td>
                                    <td style="border: 1px solid #bdc3c7; padding: 6px; color: #666;">
                                        {{ $student->father_name ?? 'Not Available' }}
                                    </td>
                                    <td style="border: 1px solid #bdc3c7; padding: 6px; text-align: center; color: #666;">
                                        {{ $student->student_mobile ?? 'N/A' }}
                                    </td>
                                    <td style="border: 1px solid #bdc3c7; padding: 6px; color: {{ $studentGroup ? '#27ae60' : '#e74c3c' }}; font-weight: bold;">
                                        {{ $studentGroup ? $studentGroup->name : 'Not Assigned' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="padding: 20px; text-align: center; color: #666; border: 1px solid #ddd;">
                        No students found in this batch
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    @endif
    
    {{-- Unassigned Students --}}
    @if(count($unassignedStudents) > 0)
        <div class="unassigned-section page-break">
            <div class="unassigned-title">⚠️ Unassigned Students</div>
            @foreach($unassignedStudents as $batchName => $students)
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #856404; margin-bottom: 10px; font-size: 12px;">{{ $batchName }}</h4>
                    <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                        <thead>
                            <tr style="background: #fff3cd; border: 1px solid #ffeaa7;">
                                <th style="border: 1px solid #ffeaa7; padding: 8px; text-align: left; font-weight: bold; width: 8%;">S.No</th>
                                <th style="border: 1px solid #ffeaa7; padding: 8px; text-align: left; font-weight: bold; width: 45%;">Student Name</th>
                                <th style="border: 1px solid #ffeaa7; padding: 8px; text-align: left; font-weight: bold; width: 20%;">Roll Number</th>
                                <th style="border: 1px solid #ffeaa7; padding: 8px; text-align: left; font-weight: bold; width: 27%;">Father's Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $index => $student)
                                <tr style="{{ $index % 2 == 0 ? 'background: #fffbf0;' : 'background: #fff8e1;' }}">
                                    <td style="border: 1px solid #ffeaa7; padding: 6px; text-align: center;">{{ $index + 1 }}</td>
                                    <td style="border: 1px solid #ffeaa7; padding: 6px; font-weight: bold; color: #856404;">
                                        {{ $student->name }}
                                    </td>
                                    <td style="border: 1px solid #ffeaa7; padding: 6px; text-align: center; color: #856404;">
                                        {{ $student->enrollment_number ?? 'Not Assigned' }}
                                    </td>
                                    <td style="border: 1px solid #ffeaa7; padding: 6px; color: #856404;">
                                        {{ $student->father_name ?? 'Not Available' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    @endif
    
    {{-- Footer --}}
    <div class="footer">
        <div>
            <strong>{{ $collegeInfo['name'] }}</strong> - Lab Allocation Report<br>
            Generated on {{ $generatedAt->format('d M Y, h:i A') }} by {{ $generatedBy->name }}<br>
            This is a computer-generated document and does not require a signature.
        </div>
    </div>
</body>
</html>