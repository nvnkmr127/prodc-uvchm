<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentConcession;
use App\Models\StudentFee;
use App\Models\FeeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConcessionController extends Controller
{
    public function index()
    {
        $concessions = StudentConcession::with(['student', 'studentFee.feeCategory', 'requestedBy', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'pending' => StudentConcession::where('status', 'pending')->count(),
            'approved' => StudentConcession::where('status', 'approved')->count(),
            'rejected' => StudentConcession::where('status', 'rejected')->count(),
            'total_amount' => StudentConcession::where('status', 'approved')->sum('amount')
        ];

        return view('admin.concessions.index', compact('concessions', 'stats'));
    }

    public function create(Request $request)
    {
        $student = null;
        if ($request->has('student_id')) {
            $student = Student::with(['studentFees.feeCategory'])->find($request->student_id);
        }

        $students = Student::with('batch.course')->orderBy('name')->get();
        $feeCategories = FeeCategory::orderBy('name')->get();

        return view('admin.concessions.create', compact('student', 'students', 'feeCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'student_fee_id' => 'required|exists:student_fees,id',
            'concession_type' => 'required|in:scholarship,financial_aid,discount,special_case',
            'amount' => 'nullable|numeric|min:0',
            'percentage' => 'nullable|numeric|min:0|max:100',
            'reason' => 'required|string|max:1000'
        ]);

        $studentFee = StudentFee::find($request->student_fee_id);
        
        // Calculate concession amount
        $concessionAmount = $request->amount;
        if ($request->percentage) {
            $concessionAmount = ($studentFee->amount * $request->percentage) / 100;
        }

        // Validate concession doesn't exceed remaining amount
        $maxConcession = $studentFee->amount - $studentFee->concession_amount;
        if ($concessionAmount > $maxConcession) {
            return back()->with('error', 'Concession amount cannot exceed remaining fee amount.');
        }

        DB::beginTransaction();
        try {
            $concession = StudentConcession::create([
                'student_id' => $request->student_id,
                'student_fee_id' => $request->student_fee_id,
                'concession_type' => $request->concession_type,
                'amount' => $concessionAmount,
                'percentage' => $request->percentage,
                'reason' => $request->reason,
                'requested_by' => auth()->id(),
                'status' => 'pending'
            ]);

            DB::commit();

            return redirect()->route('admin.concessions.index')
                ->with('success', 'Concession request created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create concession: ' . $e->getMessage());
        }
    }

    public function approve(Request $request, StudentConcession $concession)
    {
        $request->validate([
            'approval_notes' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            // Update concession status
            $concession->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => $request->approval_notes
            ]);

            // Apply concession to student fee
            $studentFee = $concession->studentFee;
            $studentFee->update([
                'concession_amount' => $studentFee->concession_amount + $concession->amount,
                'concession_reason' => $concession->reason,
                'concession_approved_by' => auth()->id(),
                'concession_approved_at' => now()
            ]);

            // Update status if fully paid after concession
            $studentFee->updateStatus();

            DB::commit();

            return redirect()->route('admin.concessions.index')
                ->with('success', 'Concession approved and applied successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to approve concession: ' . $e->getMessage());
        }
    }
}
