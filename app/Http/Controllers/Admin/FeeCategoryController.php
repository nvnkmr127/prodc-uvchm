<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeeCategoryController extends Controller
{
    public function index()
    {
        $categories = FeeCategory::all();
        return view('admin.fee_categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.fee_categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:fee_categories,name',
            'category_code' => 'nullable|string|unique:fee_categories,category_code',
            'category_type' => 'nullable|string',
            'is_mandatory' => 'boolean',
            'is_recurring' => 'boolean',
            'recurrence_type' => 'nullable|string',
            'late_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'reminder_days_before' => 'nullable|integer|min:0',
            'escalation_days_after' => 'nullable|integer|min:0'
        ]);
        
        // Auto-generate category_code if not provided
        if (empty($validated['category_code'])) {
            $validated['category_code'] = $this->generateCategoryCode($validated['name']);
        }
        
        FeeCategory::create($validated);
        return redirect()->route('admin.fee-categories.index')
            ->with('success', 'Fee Category created successfully.');
    }

    public function edit(FeeCategory $feeCategory)
    {
        return view('admin.fee_categories.edit', compact('feeCategory'));
    }

    public function update(Request $request, FeeCategory $feeCategory)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', Rule::unique('fee_categories')->ignore($feeCategory->id)],
            'category_code' => ['nullable', 'string', Rule::unique('fee_categories')->ignore($feeCategory->id)],
            'category_type' => 'nullable|string',
            'is_mandatory' => 'boolean',
            'is_recurring' => 'boolean',
            'recurrence_type' => 'nullable|string',
            'late_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'reminder_days_before' => 'nullable|integer|min:0',
            'escalation_days_after' => 'nullable|integer|min:0'
        ]);
        
        $feeCategory->update($validated);
        return redirect()->route('admin.fee-categories.index')
            ->with('success', 'Fee Category updated successfully.');
    }

    public function destroy(FeeCategory $feeCategory)
    {
        $feeCategory->delete();
        return redirect()->route('admin.fee-categories.index')
            ->with('success', 'Fee Category deleted successfully.');
    }

    private function generateCategoryCode($name)
    {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 3));
        $count = FeeCategory::where('category_code', 'like', $code . '%')->count();
        return $code . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }
}