<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { background-color: #2c3e50; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
        .stats-container { display: flex; justify-content: space-between; margin: 20px 0; }
        .stat-box { padding: 15px; border-radius: 5px; flex: 1; margin: 0 5px; text-align: center; font-weight: bold; }
        .bg-light { background-color: #f8f9fc; border: 1px solid #e3e6f0; }
        .text-success { color: #1cc88a; }
        .text-warning { color: #f6c23e; }
        .text-danger { color: #e74a3b; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e3e6f0; }
        th { background-color: #f8f9fc; }
        .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; }
        .badge-present { background-color: #1cc88a; color: white; }
        .badge-late { background-color: #f6c23e; color: white; }
        .badge-absent { background-color: #e74a3b; color: white; }
        .badge-half_day { background-color: #36b9cc; color: white; }
        .badge-excused { background-color: #858796; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>UVCHM Faculty Checkout Summary</h2>
            <p>Date: {{ $dateStr }}</p>
        </div>

        <div class="stats-container">
            <div class="stat-box bg-light">
                <div>Total Faculty</div>
                <div style="font-size: 24px;">{{ $attendanceData['total_faculty'] }}</div>
            </div>
            <div class="stat-box bg-light text-success">
                <div>Present</div>
                <div style="font-size: 24px;">{{ $attendanceData['present_count'] }}</div>
            </div>
            <div class="stat-box bg-light text-warning">
                <div>Late</div>
                <div style="font-size: 24px;">{{ $attendanceData['late_count'] }}</div>
            </div>
            <div class="stat-box bg-light" style="color: #36b9cc;">
                <div>Half Day</div>
                <div style="font-size: 24px;">{{ $attendanceData['half_day_count'] }}</div>
            </div>
            <div class="stat-box bg-light text-danger">
                <div>Absent</div>
                <div style="font-size: 24px;">{{ $attendanceData['absent_count'] }}</div>
            </div>
        </div>

        <h3>Checkout Details</h3>
        <table>
            <thead>
                <tr>
                    <th>Faculty Name</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendanceData['records'] as $record)
                <tr>
                    <td><strong>{{ $record['faculty_name'] }}</strong><br><small>{{ $record['biometric_code'] }}</small></td>
                    <td>{{ $record['department'] }}</td>
                    <td>
                        @php
                            $statusClass = 'badge-' . strtolower($record['status']);
                        @endphp
                        <span class="status-badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $record['status'])) }}</span>
                    </td>
                    <td>{{ $record['check_in'] }}</td>
                    <td>{{ $record['check_out'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <p style="margin-top: 30px; font-size: 12px; color: #858796; text-align: center;">
            This is an automatically generated email.
        </p>
    </div>
</body>
</html>
