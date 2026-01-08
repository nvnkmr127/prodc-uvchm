<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceSampleExport;
use App\Imports\AttendancesImport;

class AttendanceImportController extends Controller
{
    public function show()
    {
        return view('admin.attendance_import.index');
    }

    public function downloadSample()
    {
        try {
            $headers = [
                'Student ID',
                'Enrollment Number',
                'Student Name',
                'Date',
                'Status', // Present, Absent, Late
                'Time In',
                'Time Out',
                'Remarks'
            ];

            $sampleData = [
                ['1', 'STD001', 'John Doe', '2024-01-15', 'Present', '09:00', '17:00', 'On time'],
                ['2', 'STD002', 'Jane Smith', '2024-01-15', 'Late', '09:30', '17:00', 'Late arrival'],
                ['3', 'STD003', 'Bob Johnson', '2024-01-15', 'Absent', '', '', 'Sick leave'],
            ];

            $filename = 'attendance_import_sample.xlsx';

            return Excel::download(
                new AttendanceSampleExport($headers, $sampleData),
                $filename
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to download sample: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'attendance_file' => 'required|mimes:csv,txt,xls,xlsx|max:5120',
        ]);

        try {
            Excel::import(new AttendancesImport, $request->file('attendance_file'));

            return redirect()
                ->route('admin.daily-attendance.show')
                ->with('success', 'Attendance data imported successfully!');

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorString = "";
            foreach ($failures as $failure) {
                $errorString .= "Row " . $failure->row() . ": " . implode(', ', $failure->errors()) . ". ";
            }

            return redirect()
                ->route('admin.attendance.import.show')
                ->with('error', 'There were errors in your file: ' . $errorString);
        }
    }
}