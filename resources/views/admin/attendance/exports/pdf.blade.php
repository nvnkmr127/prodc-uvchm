<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #2F75B5;
        }
        
        .header h1 {
            color: #2F75B5;
            font-size: 24px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .export-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .export-info div {
            text-align: center;
        }
        
        .export-info .label {
            font-weight: bold;
            color: #2F75B5;
            display: block;
            margin-bottom: 2px;
        }
        
        .summary-section {
            margin-bottom: 25px;
        }
        
        .summary-title {
            background-color: #2F75B5;
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .summary-card {
            text-align: center;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #fff;
        }
        
        .summary-card .number {
            font-size: 20px;
            font-weight: bold;
            color: #2F75B5;
            display: block;
            margin-bottom: 5px;
        }
        
        .summary-card .label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }
        
        .summary-card.present .number { color: #28a745; }
        .summary-card.absent .number { color: #dc3545; }
        .summary-card.late .number { color: #ffc107; }
        
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 10px;
        }
        
        .attendance-table th {
            background-color: #2F75B5;
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #ddd;
            font-size: 9px;
        }
        
        .attendance-table td {
            padding: 6px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .attendance-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .attendance-table tbody tr:hover {
            background-color: #e3f2fd;
        }
        
        .status-present {
            background-color: #d4edda !important;
            color: #155724;
            font-weight: bold;
        }
        
        .status-absent {
            background-color: #f8d7da !important;
            color: #721c24;
            font-weight: bold;
        }
        
        .status-late {
            background-color: #fff3cd !important;
            color: #856404;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .batch-summary {
            margin-top: 20px;
        }
        
        .batch-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        
        .batch-table th,
        .batch-table td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        .batch-table th {
            background-color: #28a745;
            color: white;
            font-weight: bold;
        }
        
        .batch-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .stats-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .stats-row .stat-item {
            flex: 1;
            text-align: center;
            padding: 8px;
            border: 1px solid #ddd;
            margin: 0 2px;
        }
        
        .stats-row .stat-item:first-child {
            margin-left: 0;
        }
        
        .stats-row .stat-item:last-child {
            margin-right: 0;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        
        @media print {
            body {
                font-size: 10px;
            }
            
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .attendance-table {
                font-size: 8px;
            }
            
            .attendance-table th,
            .attendance-table td {
                padding: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Attendance Report</h1>
        <div class="subtitle">
            @if(isset($summary['date_range']))
                {{ \Carbon\Carbon::parse($summary['date_range']['start'])->format('M j, Y') }} - 
                {{ \Carbon\Carbon::parse($summary['date_range']['end'])->format('M j, Y') }}
            @else
                Attendance Export Report
            @endif
        </div>
    </div>

    <div class="export-info">
        <div>
            <span class="label">Export Date</span>
            <div>{{ $exportDate }}</div>
        </div>
        <div>
            <span class="label">Exported By</span>
            <div>{{ $exportedBy }}</div>
        </div>
        <div>
            <span class="label">Total Records</span>
            <div>{{ count($attendanceData) }}</div>
        </div>
        <div>
            <span class="label">Report Type</span>
            <div>Attendance Data</div>
        </div>
    </div>

    @if($summary)
        <div class="summary-section">
            <div class="summary-title">Summary Statistics</div>
            
            <div class="summary-grid">
                <div class="summary-card">
                    <span class="number">{{ $summary['total_records'] ?? 0 }}</span>
                    <span class="label">Total Records</span>
                </div>
                <div class="summary-card present">
                    <span class="number">{{ $summary['present_count'] ?? 0 }}</span>
                    <span class="label">Present</span>
                </div>
                <div class="summary-card absent">
                    <span class="number">{{ $summary['absent_count'] ?? 0 }}</span>
                    <span class="label">Absent</span>
                </div>
                <div class="summary-card late">
                    <span class="number">{{ $summary['late_count'] ?? 0 }}</span>
                    <span class="label">Late</span>
                </div>
            </div>

            @php
                $totalRecords = $summary['total_records'] ?? 0;
                $presentCount = $summary['present_count'] ?? 0;
                $lateCount = $summary['late_count'] ?? 0;
                $absentCount = $summary['absent_count'] ?? 0;
                
                $presentPercentage = $totalRecords > 0 ? round(($presentCount / $totalRecords) * 100, 1) : 0;
                $latePercentage = $totalRecords > 0 ? round(($lateCount / $totalRecords) * 100, 1) : 0;
                $absentPercentage = $totalRecords > 0 ? round(($absentCount / $totalRecords) * 100, 1) : 0;
                $attendanceRate = $presentPercentage + $latePercentage;
            @endphp

            <div class="stats-row">
                <div class="stat-item">
                    <strong>{{ $presentPercentage }}%</strong><br>
                    <small>Present Rate</small>
                </div>
                <div class="stat-item">
                    <strong>{{ $latePercentage }}%</strong><br>
                    <small>Late Rate</small>
                </div>
                <div class="stat-item">
                    <strong>{{ $absentPercentage }}%</strong><br>
                    <small>Absent Rate</small>
                </div>
                <div class="stat-item">
                    <strong>{{ $attendanceRate }}%</strong><br>
                    <small>Overall Attendance</small>
                </div>
            </div>

            @if(!empty($attendanceData))
                @php
                    $batchStats = collect($attendanceData)->groupBy('batch_name')->map(function($records, $batch) {
                        $total = $records->count();
                        $present = $records->where('status', 'Present')->count();
                        $late = $records->where('status', 'Late')->count();
                        $absent = $records->where('status', 'Absent')->count();
                        $attendanceRate = $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0;
                        
                        return [
                            'total' => $total,
                            'present' => $present,
                            'late' => $late,
                            'absent' => $absent,
                            'attendance_rate' => $attendanceRate
                        ];
                    });
                @endphp

                @if($batchStats->isNotEmpty())
                    <div class="batch-summary">
                        <h3 style="color: #28a745; margin-bottom: 10px;">Batch-wise Summary</h3>
                        <table class="batch-table">
                            <thead>
                                <tr>
                                    <th>Batch</th>
                                    <th>Total</th>
                                    <th>Present</th>
                                    <th>Late</th>
                                    <th>Absent</th>
                                    <th>Attendance Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($batchStats as $batchName => $stats)
                                    <tr>
                                        <td><strong>{{ $batchName }}</strong></td>
                                        <td>{{ $stats['total'] }}</td>
                                        <td>{{ $stats['present'] }}</td>
                                        <td>{{ $stats['late'] }}</td>
                                        <td>{{ $stats['absent'] }}</td>
                                        <td><strong>{{ $stats['attendance_rate'] }}%</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </div>
    @endif

    @if(!empty($attendanceData))
        <div class="page-break"></div>
        <div class="summary-title">Detailed Attendance Records</div>
        
        <table class="attendance-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Date</th>
                    <th style="width: 20%;">Student Name</th>
                    <th style="width: 12%;">Enrollment No.</th>
                    <th style="width: 12%;">Batch</th>
                    <th style="width: 12%;">Course</th>
                    <th style="width: 8%;">Status</th>
                    <th style="width: 8%;">Time</th>
                    <th style="width: 8%;">Device</th>
                    <th style="width: 10%;">Bio Code</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendanceData as $record)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($record['date'])->format('M j, Y') }}</td>
                        <td>{{ $record['student_name'] }}</td>
                        <td>{{ $record['enrollment_number'] }}</td>
                        <td>{{ $record['batch_name'] }}</td>
                        <td>{{ $record['course_name'] }}</td>
                        <td class="status-{{ strtolower($record['status']) }}">
                            {{ $record['status'] }}
                        </td>
                        <td>{{ $record['marked_time'] }}</td>
                        <td>{{ $record['device_id'] }}</td>
                        <td>{{ $record['biometric_code'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">
            <h3>No Attendance Data Available</h3>
            <p>No attendance records found for the selected date range.</p>
        </div>
    @endif

    <div class="footer">
        <div>
            <strong>Generated by:</strong> ETimeOffice Attendance Management System<br>
            <strong>Report ID:</strong> ATT-{{ date('Ymd-His') }}<br>
            <strong>Page:</strong> <span class="pagenum"></span> of <span class="pagecount"></span>
        </div>
        <div style="margin-top: 10px; font-size: 9px;">
            This is a computer-generated report. For any discrepancies, please contact the administration.
        </div>
    </div>
</body>
</html>