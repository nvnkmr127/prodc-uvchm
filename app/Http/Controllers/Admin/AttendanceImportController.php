<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AttendancesImport;

class AttendanceImportController extends Controller
{
    public function show()
    {
        return view('admin.attendance_import.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'attendance_file' => 'required|mimes:csv,txt,xls,xlsx',
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