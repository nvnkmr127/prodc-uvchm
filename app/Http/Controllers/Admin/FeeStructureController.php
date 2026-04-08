<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Course;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeeStructureController extends Controller
{
    public function index()
    {
        // Filter fee structures by academic year through batch relationship
        $feeStructures = FeeStructure::with('batch.course', 'feeCategories')
            ->whereHas('batch') // This filters through batch's HasAcademicYear trait
            ->get();

        return view('admin.fee_structures.index', compact('feeStructures'));
    }

    public function create()
    {
        $courses = Course::orderBy('name')->get();
        $categories = FeeCategory::orderBy('name')->get();

        return view('admin.fee_structures.create', compact('courses', 'categories'));
    }

    /**
     * Store a newly created fee structure in storage.
     * REVISED: Added validation and save logic for 'payment_terms'.
     */
    public function store(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|exists:batches,id|unique:fee_structures,batch_id',
            'payment_terms' => 'required|integer|min:1|max:12', // <-- Validation for new field
            'components' => 'required|array|min:1',
            'components.*.fee_category_id' => 'required|exists:fee_categories,id',
            'components.*.amount' => 'required|numeric|min:0',
        ], [
            'batch_id.unique' => 'A fee structure already exists for the selected batch.',
        ]);

        $totalAmount = 0;
        foreach ($request->components as $component) {
            $totalAmount += $component['amount'];
        }

        $feeStructure = FeeStructure::create([
            'batch_id' => $request->batch_id,
            'total_amount' => $totalAmount,
            'amount' => $totalAmount, // Your existing workaround field
            'payment_terms' => $request->payment_terms, // <-- Save the new field
        ]);

        foreach ($request->components as $component) {
            $feeStructure->feeCategories()->attach($component['fee_category_id'], ['amount' => $component['amount']]);
        }

        return redirect()->route('admin.fee-structures.index')->with('success', 'Fee structure created successfully.');
    }

    public function show(FeeStructure $feeStructure)
    {
        $feeStructure->load('batch.course', 'feeCategories');
        $chartLabels = $feeStructure->feeCategories->pluck('name');
        $chartData = $feeStructure->feeCategories->pluck('pivot.amount');

        return view('admin.fee_structures.show', compact('feeStructure', 'chartLabels', 'chartData'));
    }

    public function edit(FeeStructure $feeStructure)
    {
        $feeStructure->load('batch.course');
        $courses = Course::orderBy('name')->get();
        $categories = FeeCategory::orderBy('name')->get();
        $batchesForCourse = Batch::where('course_id', $feeStructure->batch->course_id)->get();

        return view('admin.fee_structures.edit', compact('feeStructure', 'courses', 'categories', 'batchesForCourse'));
    }

    /**
     * Update the specified fee structure in storage.
     * REVISED: Added validation and update logic for 'payment_terms'.
     */
    public function update(Request $request, FeeStructure $feeStructure)
    {
        $request->validate([
            'batch_id' => [
                'required',
                'exists:batches,id',
                Rule::unique('fee_structures')->ignore($feeStructure->id),
            ],
            'payment_terms' => 'required|integer|min:1|max:12', // <-- Validation for new field
            'components' => 'required|array|min:1',
            'components.*.fee_category_id' => 'required|exists:fee_categories,id',
            'components.*.amount' => 'required|numeric|min:0',
        ]);

        $totalAmount = 0;
        if ($request->has('components')) {
            foreach ($request->components as $component) {
                $totalAmount += $component['amount'];
            }
        }

        $feeStructure->update([
            'batch_id' => $request->batch_id,
            'total_amount' => $totalAmount,
            'amount' => $totalAmount, // Your existing workaround field
            'payment_terms' => $request->payment_terms, // <-- Update the new field
        ]);

        $componentsToSync = [];
        if ($request->has('components')) {
            foreach ($request->components as $component) {
                $componentsToSync[$component['fee_category_id']] = ['amount' => $component['amount']];
            }
        }
        $feeStructure->feeCategories()->sync($componentsToSync);

        return redirect()->route('admin.fee-structures.edit', $feeStructure)->with('success', 'Fee structure updated successfully.');
    }

    public function destroy(FeeStructure $feeStructure)
    {
        $feeStructure->delete();

        return redirect()->route('admin.fee-structures.index')->with('success', 'Fee structure deleted successfully.');
    }
}
