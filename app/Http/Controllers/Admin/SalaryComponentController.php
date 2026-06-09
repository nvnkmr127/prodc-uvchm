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
        $components = SalaryComponent::all();
        return view('admin.salary_components.create', compact('components'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:salary_components,name',
            'type' => ['required', Rule::in(['Earning', 'Deduction'])],
            'is_taxable' => 'boolean',
            'is_mandatory' => 'boolean',
            'calculation_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'base_component_id' => 'nullable|exists:salary_components,id',
        ]);

        SalaryComponent::create($validated);

        return redirect()->route('admin.salary-components.index')->with('success', 'Salary component created.');
    }

    public function edit(SalaryComponent $salaryComponent)
    {
        $components = SalaryComponent::where('id', '!=', $salaryComponent->id)->get();
        return view('admin.salary_components.edit', compact('salaryComponent', 'components'));
    }

    public function update(Request $request, SalaryComponent $salaryComponent)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('salary_components')->ignore($salaryComponent->id)],
            'type' => ['required', Rule::in(['Earning', 'Deduction'])],
            'is_taxable' => 'boolean',
            'is_mandatory' => 'boolean',
            'calculation_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'base_component_id' => 'nullable|exists:salary_components,id',
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
