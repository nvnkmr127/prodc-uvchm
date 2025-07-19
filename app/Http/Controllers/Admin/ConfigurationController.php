<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Batch;
use App\Models\Subject;
use App\Models\LeaveType;
use App\Models\FeeCategory;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    /**
     * Display the system configuration page.
     * This method gathers data for all configuration tabs.
     */
    public function index()
    {
        // Data for each tab
        $courses = Course::withCount('batches')->latest()->get();
        $batches = Batch::with('course')->latest()->get();
        $subjects = Subject::latest()->get();
        $roles = Role::withCount('users')->get();
        $leaveTypes = LeaveType::all();
        $feeCategories = FeeCategory::all();

        return view('admin.configuration.index', compact(
            'courses',
            'batches',
            'subjects',
            'roles',
            'leaveTypes',
            'feeCategories'
        ));
    }
}
