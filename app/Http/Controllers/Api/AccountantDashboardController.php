<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
// MODIFIED: Replaced Invoice with StudentFee
use App\Models\{Payment, StudentFee, Student};
use Illuminate\Http\Request;

class AccountantDashboardController extends Controller
{
    /**
     * Get financial metrics based on the new component-based system.
     */
    public function financialMetrics()
    {
        // MODIFIED: All metrics are now calculated using the StudentFee (component) model - bypass global scope
        $allFees = StudentFee::withoutGlobalScope('academic_year')->get();
        
        $metrics = [
            'total_revenue' => $allFees->sum('paid_amount'),
            'monthly_revenue' => Payment::withoutGlobalScope('academic_year')
                                        ->where('payment_type', 'component')
                                        ->whereMonth('payment_date', now()->month)
                                        ->whereYear('payment_date', now()->year)
                                        ->sum('amount'),
            'pending_payments' => $allFees->sum(fn($fee) => $fee->getRemainingAmount()),
            'total_fee_components' => $allFees->count(),
            'paid_fee_components' => $allFees->where('status', 'paid')->count(),
            'overdue_fee_components' => $allFees->filter(fn($fee) => $fee->isOverdue())->count(),
            'collection_rate' => $this->getCollectionRate(),
        ];
        
        return response()->json($metrics);
    }
    
    /**
     * Get payment trends based on the new component-based system.
     */
    public function paymentTrends()
    {
        $trends = [
            'daily_collections' => $this->getDailyCollections(),
            'monthly_collections' => $this->getMonthlyCollections(),
            'payment_methods' => $this->getPaymentMethodDistribution(),
            'top_defaulters' => $this->getTopDefaulters(),
        ];
        
        return response()->json($trends);
    }
    
    /**
     * Calculate the collection rate based on fee components.
     */
    private function getCollectionRate()
    {
        // MODIFIED: Calculates rate based on net expected amount from all components vs. amount paid.
        $totalBilled = StudentFee::withoutGlobalScope('academic_year')->sum('amount');
        $totalConcession = StudentFee::withoutGlobalScope('academic_year')->sum('concession_amount');
        $netBilled = $totalBilled - $totalConcession;
        $totalCollected = StudentFee::withoutGlobalScope('academic_year')->sum('paid_amount');
        
        if ($netBilled == 0) return 100; // Avoid division by zero if no fees are billed
        return round(($totalCollected / $netBilled) * 100, 2);
    }
    
    /**
     * Get daily collections from component-based payments.
     */
    private function getDailyCollections()
    {
        // MODIFIED: Queries only component-based payments.
        return Payment::withoutGlobalScope('academic_year')
                     ->where('payment_type', 'component')
                     ->selectRaw('DATE(payment_date) as date, SUM(amount) as total')
                     ->whereBetween('payment_date', [now()->subDays(30), now()])
                     ->groupBy('date')
                     ->orderBy('date')
                     ->pluck('total', 'date');
    }
    
    /**
     * Get monthly collections from component-based payments.
     */
    private function getMonthlyCollections()
    {
        // MODIFIED: Queries only component-based payments.
        return Payment::withoutGlobalScope('academic_year')
                     ->where('payment_type', 'component')
                     ->selectRaw('MONTH(payment_date) as month, SUM(amount) as total')
                     ->whereYear('payment_date', now()->year)
                     ->groupBy('month')
                     ->pluck('total', 'month');
    }
    
    /**
     * Get payment method distribution from component-based payments.
     */
    private function getPaymentMethodDistribution()
    {
        // MODIFIED: Queries only component-based payments.
        return Payment::withoutGlobalScope('academic_year')
                     ->where('payment_type', 'component')
                     ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
                     ->groupBy('payment_method')
                     ->get()
                     ->mapWithKeys(function($item) {
                         return [$item->payment_method => [
                             'count' => $item->count,
                             'total' => $item->total
                         ]];
                     });
    }
    
    /**
     * Get top defaulters based on outstanding fee components.
     */
    private function getTopDefaulters()
    {
        // MODIFIED: Identifies defaulters based on unpaid/partial StudentFee components.
        return Student::withoutGlobalScope('academic_year')
                 ->whereHas('studentFees', function($query) {
                     $query->whereIn('status', ['unpaid', 'partial', 'overdue']);
                 })
                 ->with('studentFees')
                 ->get()
                 ->map(function($student) {
                     return [
                         'name' => $student->name,
                         'enrollment_number' => $student->enrollment_number,
                         'total_due' => $student->studentFees->sum(fn($fee) => $fee->getRemainingAmount())
                     ];
                 })
                 ->sortByDesc('total_due')
                 ->take(10)
                 ->values();
    }
}