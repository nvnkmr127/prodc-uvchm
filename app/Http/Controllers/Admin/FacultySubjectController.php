<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

class FacultySubjectController extends Controller
{
    public function edit(User $user)
    {
        // Use the Spatie method to check the role
        if (!$user->hasRole('staff')) {
            abort(404);
        }

        $allSubjects = Subject::orderBy('name')->get();
        return view('admin.faculty.manage_subjects', [
            'faculty' => $user,
            'allSubjects' => $allSubjects
        ]);
    }

    public function update(Request $request, User $user)
    {
        // Use the Spatie method to check the role
        if (!$user->hasRole('staff')) {
            abort(404);
        }

        $user->subjects()->sync($request->input('subjects', []));

        return redirect()->route('admin.faculty.index')
                         ->with('success', 'Subjects for ' . $user->name . ' updated successfully.');
    }
}