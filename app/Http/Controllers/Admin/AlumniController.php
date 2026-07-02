<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;

class AlumniController extends Controller
{
    public function index()
    {
        // Fetch all students with the 'graduated' status.
        // Eager-load studentFees so the fee-paid summary in the view avoids N+1 queries.
        $alumni = Student::where('status', 'graduated')
            ->with(['batch.course', 'studentFees'])
            ->latest()
            ->get();

        return view('admin.alumni.index', compact('alumni'));
    }
}
