<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\DropoutManagementService;
use Illuminate\Http\Request;

class DropoutController extends Controller
{
    protected $dropoutService;

    public function __construct(DropoutManagementService $dropoutService)
    {
        $this->dropoutService = $dropoutService;
    }

    /**
     * Show dropout confirmation page
     */
    public function confirmDropout(Student $student)
    {
        if ($student->status === 'dropout') {
            return redirect()->route('admin.students.show', $student)
                ->with('error', 'Student is already marked as dropout');
        }

        $financialSummary = $student->getFinancialSummary();

        return view('admin.students.confirm-dropout', compact('student', 'financialSummary'));
    }

    /**
     * Process the dropout
     */
    public function processDropout(Request $request, Student $student)
    {
        $request->validate([
            'dropout_date' => 'required|date|before_or_equal:today',
            'reason' => 'required|string|max:500',
            'confirm_preservation' => 'required|accepted',
        ]);

        $result = $this->dropoutService->processDropout($student, $request->only('dropout_date', 'reason'));

        if ($result['success']) {
            return redirect()->route('admin.students.show', $student)
                ->with('success', $result['message'])
                ->with('dropout_report', $result['dropout_report']);
        } else {
            return back()->withInput()
                ->with('error', $result['message']);
        }
    }

    /**
     * Show all dropout students
     */
    public function index()
    {
        $dropouts = Student::dropout()
            ->with(['batch.course'])
            ->orderBy('dropout_date', 'desc')
            ->paginate(50);

        $statistics = $this->dropoutService->getDropoutStatistics();

        return view('admin.dropouts.index', compact('dropouts', 'statistics'));
    }

    /**
     * Show dropout details
     */
    public function show(Student $student)
    {
        if ($student->status !== 'dropout') {
            return redirect()->route('admin.students.show', $student);
        }

        return view('admin.dropouts.show', compact('student'));
    }

    /**
     * Reactivate a dropout student
     */
    public function reactivate(Request $request, Student $student)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $result = $this->dropoutService->reactivateStudent($student, $request->reason);

        if ($result['success']) {
            return redirect()->route('admin.students.show', $student)
                ->with('success', $result['message']);
        } else {
            return back()->with('error', $result['message']);
        }
    }
}
