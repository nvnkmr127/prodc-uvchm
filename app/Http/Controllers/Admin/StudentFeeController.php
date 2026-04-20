<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\FeeStructure;
use App\Models\Student;
use App\Models\StudentFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentFeeController extends Controller
{
    protected $feeService;

    public function __construct(\App\Services\ComponentPaymentService $feeService)
    {
        $this->feeService = $feeService;
    }
    /**
     * Display a listing of student fees
     */
    public function index(Request $request)
    {
        $query = StudentFee::with(['student', 'feeCategory', 'feeStructure']);

        // Apply filters
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        $studentFees = $query->paginate(20);

        return view('admin.student-fees.index', compact('studentFees'));
    }

    /**
     * Show the form for creating a new student fee
     */
    public function create()
    {
        $students = Student::with('batch')->get();
        $feeStructures = FeeStructure::with('feeCategories')->get();

        return view('admin.student-fees.create', compact('students', 'feeStructures'));
    }

    /**
     * Store a newly created student fee
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_structure_id' => 'required|exists:fee_structures,id',
            'fee_category_id' => 'required|exists:fee_categories,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'academic_year' => 'required|string',
        ]);

        try {
            StudentFee::create($request->all());

            return redirect()
                ->route('admin.student-fees.index')
                ->with('success', 'Student fee created successfully.');

        } catch (\Exception $e) {
            Log::error('Student fee creation failed: '.$e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Failed to create student fee.');
        }
    }

    /**
     * Display the specified student fee
     */
    public function show(StudentFee $studentFee)
    {
        $studentFee->load(['student', 'feeCategory', 'feeStructure']);

        return view('admin.student-fees.show', compact('studentFee'));
    }

    /**
     * Show the form for editing the specified student fee
     */
    public function edit(StudentFee $studentFee)
    {
        $students = Student::with('batch')->get();
        $feeStructures = FeeStructure::with('feeCategories')->get();

        return view('admin.student-fees.edit', compact('studentFee', 'students', 'feeStructures'));
    }

    /**
     * Update the specified student fee
     */
    public function update(Request $request, StudentFee $studentFee)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'status' => 'required|in:unpaid,paid,partial,overdue',
            'paid_amount' => 'nullable|numeric|min:0',
            'concession_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $studentFee->update($request->only([
                'amount',
                'due_date',
                'status',
                'paid_amount',
                'concession_amount',
            ]));

            return redirect()
                ->route('admin.student-fees.index')
                ->with('success', 'Student fee updated successfully.');

        } catch (\Exception $e) {
            Log::error('Student fee update failed: '.$e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Failed to update student fee.');
        }
    }

    /**
     * Remove the specified student fee
     */
    public function destroy(StudentFee $studentFee)
    {
        try {
            $studentFee->delete();

            return redirect()
                ->route('admin.student-fees.index')
                ->with('success', 'Student fee deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Student fee deletion failed: '.$e->getMessage());

            return back()->with('error', 'Failed to delete student fee.');
        }
    }

    /**
     * Generate fee components for an entire batch
     */
    public function generateForBatch(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'academic_year' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $batch = Batch::with(['students', 'feeStructure.feeCategories'])->find($request->batch_id);

            if (! $batch->feeStructure) {
                return back()->with('error', 'Fee structure not found for this batch.');
            }

            $result = $this->feeService->createFeeComponentsForBatch(
                $request->batch_id,
                null,
                $request->academic_year
            );

            if (! $result['success']) {
                return back()->with('error', 'Failed to generate fees: '.$result['error']);
            }

            return back()->with('success', $result['message']);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Batch fee generation failed: '.$e->getMessage());

            return back()->with('error', 'Failed to generate fees for batch.');
        }
    }
}
