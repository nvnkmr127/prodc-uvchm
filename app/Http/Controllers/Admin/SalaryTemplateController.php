<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalaryComponent;
use App\Models\SalaryTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SalaryTemplateController extends Controller
{
    public function index()
    {
        $templates = SalaryTemplate::withCount('components')->latest()->get();
        return view('admin.salary_templates.index', compact('templates'));
    }

    public function create()
    {
        $components = SalaryComponent::all();
        return view('admin.salary_templates.create', compact('components'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:salary_templates,name',
            'description' => 'nullable|string',
            'components' => 'nullable|array',
            'components.*.salary_component_id' => 'required|exists:salary_components,id',
            'components.*.value' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $template = SalaryTemplate::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            if (!empty($validated['components'])) {
                foreach ($validated['components'] as $comp) {
                    $template->components()->create([
                        'salary_component_id' => $comp['salary_component_id'],
                        'value' => $comp['value']
                    ]);
                }
            }
        });

        return redirect()->route('admin.salary-templates.index')->with('success', 'Salary template created successfully.');
    }

    public function edit(SalaryTemplate $salaryTemplate)
    {
        $salaryTemplate->load('components.component');
        $components = SalaryComponent::all();
        return view('admin.salary_templates.edit', compact('salaryTemplate', 'components'));
    }

    public function update(Request $request, SalaryTemplate $salaryTemplate)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('salary_templates')->ignore($salaryTemplate->id)],
            'description' => 'nullable|string',
            'components' => 'nullable|array',
            'components.*.salary_component_id' => 'required|exists:salary_components,id',
            'components.*.value' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $salaryTemplate) {
            $salaryTemplate->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            $salaryTemplate->components()->delete();

            if (!empty($validated['components'])) {
                foreach ($validated['components'] as $comp) {
                    $salaryTemplate->components()->create([
                        'salary_component_id' => $comp['salary_component_id'],
                        'value' => $comp['value']
                    ]);
                }
            }
        });

        return redirect()->route('admin.salary-templates.index')->with('success', 'Salary template updated successfully.');
    }

    public function destroy(SalaryTemplate $salaryTemplate)
    {
        $salaryTemplate->delete();
        return redirect()->route('admin.salary-templates.index')->with('success', 'Salary template deleted successfully.');
    }
}
