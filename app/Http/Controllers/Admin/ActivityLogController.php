<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index()
    {
        // Get the latest 100 activities
        $activities = Activity::latest()->with('causer')->limit(100)->get();
        return view('admin.activity_log.index', compact('activities'));
    }
}