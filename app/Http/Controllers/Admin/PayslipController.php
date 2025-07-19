<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Payslip;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    public function index()
    {
        $payslips = Payslip::with('user')->latest()->get();
        return view('admin.payslips.index', compact('payslips'));
    }

    public function create()
    {
        return view('admin.payslips.create');
    }

    public function store(Request $request)
    {
        $request->validate(['month' => 'required|string', 'year' => 'required|integer']);

        $staff = User::role('staff')->with('salaryStructure.salaryComponent')->get();
        $payslipCount = 0;

        foreach ($staff as $user) {
            if ($user->salaryStructure->isEmpty()) continue; // Skip staff with no salary defined

            $gross = $user->salaryStructure->where('salaryComponent.type', 'Earning')->sum('amount');
            $deductions = $user->salaryStructure->where('salaryComponent.type', 'Deduction')->sum('amount');

            // Use updateOrCreate to prevent duplicate payslips for the same user in the same month/year
            Payslip::updateOrCreate(
                ['user_id' => $user->id, 'month' => $request->month, 'year' => $request->year],
                ['gross_salary' => $gross, 'total_deductions' => $deductions, 'net_salary' => $gross - $deductions]
            );
            $payslipCount++;
        }
        return redirect()->route('admin.payslips.index')->with('success', "Generated {$payslipCount} payslips for {$request->month}, {$request->year}.");
    }

    public function show(Payslip $payslip)
    {
        $structure = $payslip->user->salaryStructure()->with('salaryComponent')->get();
        $earnings = $structure->where('salaryComponent.type', 'Earning');
        $deductions = $structure->where('salaryComponent.type', 'Deduction');
        return view('admin.payslips.show', compact('payslip', 'earnings', 'deductions'));
    }
}