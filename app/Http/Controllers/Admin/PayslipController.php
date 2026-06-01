<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use App\Models\User;
use Carbon\Carbon;
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
            if ($user->salaryStructure->isEmpty()) {
                continue;
            } // Skip staff with no salary defined

            $baseGross = $user->salaryStructure->where('salaryComponent.type', 'Earning')->sum('amount');
            $baseDeductions = $user->salaryStructure->where('salaryComponent.type', 'Deduction')->sum('amount');

            // Calculate biometric attendance stats and payment multiplier
            $biometricStats = $this->calculateBiometricSalaryStats($user, $request->month, $request->year);
            $multiplier = $biometricStats['payment_multiplier'];

            // Calculate proportional gross salary
            $gross = round($baseGross * $multiplier, 2);
            $deductions = $baseDeductions;
            $net = max(0.00, $gross - $deductions);

            // Use updateOrCreate to prevent duplicate payslips
            Payslip::updateOrCreate(
                ['user_id' => $user->id, 'month' => $request->month, 'year' => $request->year],
                [
                    'gross_salary' => $gross,
                    'total_deductions' => $deductions,
                    'net_salary' => $net,
                    'working_days' => $biometricStats['working_days'],
                    'days_present' => $biometricStats['days_present'],
                    'leave_days' => $biometricStats['leave_days'],
                    'payment_multiplier' => $multiplier,
                ]
            );
            $payslipCount++;
        }

        return redirect()->route('admin.payslips.index')->with('success', "Generated {$payslipCount} biometric-based payslips for {$request->month}, {$request->year}.");
    }

    public function show(Payslip $payslip)
    {
        $structure = $payslip->user->salaryStructure()->with('salaryComponent')->get();
        $earnings = $structure->where('salaryComponent.type', 'Earning');
        $deductions = $structure->where('salaryComponent.type', 'Deduction');

        return view('admin.payslips.show', compact('payslip', 'earnings', 'deductions'));
    }

    /**
     * Calculate biometric salary stats for a faculty user
     */
    private function calculateBiometricSalaryStats(User $user, string $month, int $year): array
    {
        // 1. Convert month name to number
        try {
            $monthNum = Carbon::parse("1 {$month} {$year}")->month;
        } catch (\Exception $e) {
            $monthNum = Carbon::now()->month;
        }

        $startDate = Carbon::create($year, $monthNum, 1)->startOfMonth();
        $endDate = Carbon::create($year, $monthNum, 1)->endOfMonth();
        $totalDaysInMonth = $startDate->daysInMonth;

        // 2. Count weekends (Saturdays and Sundays)
        $weekends = 0;
        $tempDate = $startDate->copy();
        while ($tempDate->lte($endDate)) {
            if ($tempDate->isWeekend()) {
                $weekends++;
            }
            $tempDate->addDay();
        }

        // 3. Count holidays (excluding weekends)
        $holidaysCount = 0;
        if (class_exists(\App\Models\Holiday::class)) {
            $holidays = \App\Models\Holiday::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])->get();
            foreach ($holidays as $holiday) {
                $hDate = Carbon::parse($holiday->date);
                if (!$hDate->isWeekend()) {
                    $holidaysCount++;
                }
            }
        }

        // 4. Calculate working days
        $workingDays = $totalDaysInMonth - $weekends - $holidaysCount;
        if ($workingDays <= 0) {
            $workingDays = 22; // Fallback
        }

        // 5. Count days present using scan dates in biometric_logs (single punch = 0.5, multiple punches >= 4 hrs apart = 1.0)
        $daysPresent = 0.00;
        if (!empty($user->biometric_employee_code)) {
            $scansByDate = \App\Models\Attendance\BiometricLog::where('employee_code', $user->biometric_employee_code)
                ->whereBetween('scan_datetime', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->orderBy('scan_datetime')
                ->get()
                ->groupBy(function($log) {
                    return $log->scan_datetime->toDateString();
                });

            foreach ($scansByDate as $date => $logs) {
                if ($logs->count() < 2) {
                    // Single punch (forgot punch) = Half Day (0.5)
                    $daysPresent += 0.5;
                } else {
                    // Calculate hours between first and last scan on this day
                    $firstScan = $logs->first()->scan_datetime;
                    $lastScan = $logs->last()->scan_datetime;
                    $hoursDiff = $firstScan->diffInHours($lastScan);

                    if ($hoursDiff < 4) {
                        // Double punch or short duration = Half Day (0.5)
                        $daysPresent += 0.5;
                    } else {
                        // Full Day (1.0)
                        $daysPresent += 1.0;
                    }
                }
            }
        }

        // 6. Count approved leave days that fall on weekdays (half-day leaves = 0.5)
        $leaveDays = 0.00;
        if (class_exists(\App\Models\LeaveApplication::class)) {
            $leaves = \App\Models\LeaveApplication::where('user_id', $user->id)
                ->where('status', 'approved')
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                        ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->where('start_date', '<=', $startDate->toDateString())
                              ->where('end_date', '>=', $endDate->toDateString());
                        });
                })
                ->get();

            $countedLeaveDates = [];
            foreach ($leaves as $leave) {
                $leaveStart = Carbon::parse($leave->start_date);
                $leaveEnd = Carbon::parse($leave->end_date);
                
                $start = $leaveStart->max($startDate);
                $end = $leaveEnd->min($endDate);
                
                $curr = $start->copy();
                while ($curr->lte($end)) {
                    $dateStr = $curr->toDateString();
                    if (!$curr->isWeekend()) {
                        $isHoliday = false;
                        if (class_exists(\App\Models\Holiday::class)) {
                            $isHoliday = \App\Models\Holiday::where('date', $dateStr)->exists();
                        }
                        if (!$isHoliday) {
                            $weight = $leave->is_half_day ? 0.5 : 1.0;
                            if (isset($countedLeaveDates[$dateStr])) {
                                $countedLeaveDates[$dateStr] = max($countedLeaveDates[$dateStr], $weight);
                            } else {
                                $countedLeaveDates[$dateStr] = $weight;
                            }
                        }
                    }
                    $curr->addDay();
                }
            }
            $leaveDays = array_sum($countedLeaveDates);
        }

        // 7. Calculate payment multiplier
        if (empty($user->biometric_employee_code)) {
            $multiplier = 1.0000;
        } else {
            $totalPaidDays = $daysPresent + $leaveDays;
            $multiplier = $workingDays > 0 ? min(1.0000, $totalPaidDays / $workingDays) : 1.0000;
        }

        return [
            'working_days' => $workingDays,
            'days_present' => $daysPresent,
            'leave_days' => $leaveDays,
            'payment_multiplier' => round($multiplier, 4),
        ];
    }
}
