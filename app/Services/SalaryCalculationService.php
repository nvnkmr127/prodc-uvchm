<?php

namespace App\Services;

use App\Models\Payslip;
use App\Models\User;
use App\Models\SalaryComponent;
use App\Models\SalaryTemplate;

class SalaryCalculationService
{
    /**
     * Calculate salary for a user for a specific month and year.
     * Returns an array with breakdown of earnings and deductions.
     */
    public function calculateSalary(User $user, $workingDays, $daysPresent, $leaveDays)
    {
        $components = [];
        $grossSalary = 0;
        $totalDeductions = 0;

        // Payment multiplier logic (basic prorating based on days present)
        if (empty($user->biometric_employee_code)) {
            $paymentMultiplier = 1.0000;
        } else {
            $totalPaidDays = $daysPresent + $leaveDays;
            $paymentMultiplier = $workingDays > 0 ? min(1.0000, $totalPaidDays / $workingDays) : 1.0000;
        }

        // Ensure user has a salary template or overrides
        $template = $user->salaryTemplate;
        $overrides = $user->salaryStructure()->with('salaryComponent')->get()->keyBy('salary_component_id');

        $activeComponents = []; // Component ID => Value

        // 1. Gather all applicable components and their base values
        if ($template) {
            foreach ($template->components as $tc) {
                $activeComponents[$tc->salary_component_id] = $tc->value;
            }
        }

        // 2. Apply overrides
        foreach ($overrides as $override) {
            $activeComponents[$override->salary_component_id] = $override->amount;
        }

        // We need all full component details
        $allComponents = SalaryComponent::whereIn('id', array_keys($activeComponents))->get()->keyBy('id');

        // 3. Evaluate fixed components first
        $evaluatedValues = []; // Component ID => Calculated Value

        foreach ($activeComponents as $compId => $value) {
            $comp = $allComponents[$compId] ?? null;
            if (!$comp) continue;

            if ($comp->calculation_type === 'fixed') {
                $evaluatedValues[$compId] = $value;
            }
        }

        // 4. Evaluate percentage components (assuming base components are already evaluated, e.g. Basic Pay)
        foreach ($activeComponents as $compId => $value) {
            $comp = $allComponents[$compId] ?? null;
            if (!$comp) continue;

            if ($comp->calculation_type === 'percentage') {
                $baseId = $comp->base_component_id;
                $baseValue = $evaluatedValues[$baseId] ?? 0;
                $evaluatedValues[$compId] = ($value / 100) * $baseValue;
            }
        }

        // 5. Build final list and apply attendance prorating
        foreach ($evaluatedValues as $compId => $calculatedValue) {
            $comp = $allComponents[$compId];
            
            // Prorate based on attendance (simplistic approach: all are prorated)
            $finalAmount = round($calculatedValue * $paymentMultiplier, 2);

            $components[] = [
                'salary_component_id' => $comp->id,
                'name' => $comp->name,
                'type' => $comp->type,
                'original_amount' => $calculatedValue,
                'amount' => $finalAmount,
            ];

            if ($comp->type === 'Earning') {
                $grossSalary += $finalAmount;
            } else {
                $totalDeductions += $finalAmount;
            }
        }

        $netSalary = $grossSalary - $totalDeductions;

        return [
            'gross_salary' => $grossSalary,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'payment_multiplier' => $paymentMultiplier,
            'components' => $components,
        ];
    }
}
