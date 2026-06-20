<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4e73df; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
        .stats-container { margin: 20px 0; text-align: center; padding: 15px; background-color: #f8f9fc; border: 1px solid #e3e6f0; border-radius: 5px; font-size: 18px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e3e6f0; }
        th { background-color: #f8f9fc; }
        .text-success { color: #1cc88a; font-weight: bold; }
        .text-warning { color: #f6c23e; font-weight: bold; }
        .text-danger { color: #e74a3b; font-weight: bold; }
        .text-info { color: #36b9cc; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>UVCHM Faculty Monthly Attendance Summary</h2>
            <p>Month: {{ $monthName }}</p>
        </div>

        <div class="stats-container">
            Total Active Faculty: {{ $attendanceData['total_faculty'] }}
        </div>

        <h3>Monthly Breakdown</h3>
        <table>
            <thead>
                <tr>
                    <th>Faculty Name</th>
                    <th>Department</th>
                    <th>Days Tracked</th>
                    <th>Present</th>
                    <th>Late</th>
                    <th>Half Day</th>
                    <th>Absent</th>
                    <th>Tracked Hrs</th>
                    <th>Actual Hrs</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendanceData['records'] as $record)
                <tr>
                    <td><strong>{{ $record['faculty_name'] }}</strong><br><small>{{ $record['biometric_code'] }}</small></td>
                    <td>{{ $record['department'] }}</td>
                    <td>{{ $record['total_days_tracked'] }}</td>
                    <td class="text-success">{{ $record['present_count'] }}</td>
                    <td class="text-warning">{{ $record['late_count'] }}</td>
                    <td class="text-info">{{ $record['half_day_count'] }}</td>
                    <td class="text-danger">{{ $record['absent_count'] }}</td>
                    <td><strong>{{ $record['tracked_hours'] }}</strong></td>
                    <td><strong>{{ $record['actual_hours'] }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <p style="margin-top: 30px; font-size: 12px; color: #858796; text-align: center;">
            This is an automatically generated monthly report.
        </p>
    </div>
</body>
</html>
