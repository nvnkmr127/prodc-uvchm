<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalaryComponent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalaryComponentController extends Controller
{
    public function index()
    {
        $components = SalaryComponent::latest()->get();

        return view('admin.salary_components.index', compact('components'));
    }

    public function create()
    {
        return view('admin.salary_components.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:salary_components,name',
            'type' => ['required', Rule::in(['Earning', 'Deduction'])],
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:salary_components,name',
            'type' => 'required|in:earning,deduction',
            'is_taxable' => 'boolean',
            'is_mandatory' => 'boolean',
        ]);
        SalaryComponent::create($validated);

        return redirect()->route('admin.salary-components.index')->with('success', 'Salary component created.');
    }

    public function edit(SalaryComponent $salaryComponent)
    {
        return view('admin.salary_components.edit', compact('salaryComponent'));
    }

    public function update(Request $request, SalaryComponent $salaryComponent)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('salary_components')->ignore($salaryComponent->id)],
            'type' => ['required', Rule::in(['Earning', 'Deduction'])],
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:earning,deduction',
            'is_taxable' => 'boolean',
            'is_mandatory' => 'boolean',
        ]);
        $salaryComponent->update($validated);

        return redirect()->route('admin.salary-components.index')->with('success', 'Salary component updated.');
    }

    public function destroy(SalaryComponent $salaryComponent)
    {
        $salaryComponent->delete();

        return redirect()->route('admin.salary-components.index')->with('success', 'Salary component deleted.');
    }
}
