<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class AlumniController extends Controller
{
    public function index()
    {
        // Fetch all students with the 'graduated' status
        $alumni = Student::where('status', 'graduated')->with('batch.course')->latest()->get();
        return view('admin.alumni.index', compact('alumni'));
    }
}