<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Models\UserSalaryStructure;
use Illuminate\Http\Request;

class UserSalaryController extends Controller
{
    public function show(User $user)
    {
        $salaryStructure = $user->salaryStructure()->with('salaryComponent')->get();
        $components = SalaryComponent::all();

        return view('admin.user_salary.show', compact('user', 'salaryStructure', 'components'));
    }

    public function store(Request $request, User $user)
    {
        $request->validate([
            'salary_component_id' => 'required|exists:salary_components,id',
            'amount' => 'required|numeric|min:0',
        ]);

        // Prevent adding the same component twice
        $exists = UserSalaryStructure::where('user_id', $user->id)
            ->where('salary_component_id', $request->salary_component_id)->exists();
        if ($exists) {
            return redirect()->back()->with('error', 'This component is already part of the salary structure.');
        }

        $validated = $request->validate([
            'salary_component_id' => 'required|exists:salary_components,id',
            'amount' => 'required|numeric|min:0|max:9999999.99',
        ]);

        // Prevent adding the same component twice
        $exists = $user->salaryStructure()
            ->where('salary_component_id', $validated['salary_component_id'])
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['salary_component_id' => 'This component already exists for this user.']);
        }

        $user->salaryStructure()->create($validated);

        return redirect()->route('admin.faculty.salary.show', $user)->with('success', 'Component added.');
    }

    public function destroy(UserSalaryStructure $structure)
    {
        $user = $structure->user;
        $structure->delete();

        return redirect()->route('admin.faculty.salary.show', $user)->with('success', 'Component removed.');
    }
}
