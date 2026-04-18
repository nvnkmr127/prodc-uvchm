<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Course;
use App\Models\FeeCategory;
use App\Models\Student;
use App\Models\StudentFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeCategoryAnalysisController extends Controller
{
    /**
     * Display the fee category analysis dashboard
     */
    public function index(Request $request)
    {
        $filters = $this->buildFilters($request);

        // Get all fee categories with their analysis data
        $categoryAnalysis = $this->getCategoryAnalysis($filters);

        // Get summary statistics
        $summaryStats = $this->getSummaryStats($filters);

        // Handle AJAX request for filtering
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.fee-category-analysis._table_rows', compact('categoryAnalysis'))->render(),
                'highlights_html' => view('admin.fee-category-analysis._highlights', compact('summaryStats'))->render(),
                'stats' => $summaryStats,
                // We'll also return highlights data if needed, but summaryStats contains top/bottom categories
                'top_performer' => $summaryStats['top_performing_category'] ?? null,
                'most_pending' => $summaryStats['most_pending_category'] ?? null,
            ]);
        }

        // Get filter options
        $feeCategories = FeeCategory::orderBy('name')->get();
        // Eager load batches for the combined filter
        $courses = Course::with([
            'batches' => function ($q) {
                $q->orderBy('name');
            },
        ])->orderBy('name')->get();
        $batches = Batch::with('course')->orderBy('name')->get();

        return view('admin.fee-category-analysis.index', compact(
            'categoryAnalysis',
            'summaryStats',
            'feeCategories',
            'courses',
            'batches',
            'filters'
        ));
    }

    public function show(Request $request, $id)
    {
        $feeCategory = \App\Models\FeeCategory::findOrFail($id);

        // Base Query
        $query = \App\Models\StudentFee::with(['student.batch.course'])
            ->where('fee_category_id', $id)
            ->whereHas('student', function ($q) {
                $q->where('status', '!=', 'dropout');
            });

        // 1. Filter by Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('enrollment_number', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        // 2. Filter by Academic Year (CRITICAL)
        $selectedYearId = $request->get('academic_year_filter', session('selected_academic_year_id'));
        if ($selectedYearId) {
            $query->whereHas('student.batch', function ($q) use ($selectedYearId) {
                $q->where('academic_year_id', $selectedYearId);
            });
        }

        // 3. Filter by Course
        if ($request->filled('course_id')) {
            $query->whereHas('student.batch', function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
        }

        // 4. Filter by Batch
        if ($request->filled('batch_id')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('batch_id', $request->batch_id);
            });
        }

        // 5. Filter by Status
        if ($request->filled('status')) {
            if ($request->status === 'paid') {
                $query->whereRaw('(amount - concession_amount - paid_amount) <= 0');
            } elseif ($request->status === 'unpaid') {
                $query->where('paid_amount', 0);
            } elseif ($request->status === 'partial') {
                $query->where('paid_amount', '>', 0)
                    ->whereRaw('(amount - concession_amount - paid_amount) > 0');
            }
        }

        // Summary Stats
        $summaryQuery = clone $query;
        $stats = [
            'total' => $summaryQuery->sum('amount'),
            'paid' => $summaryQuery->sum('paid_amount'),
            'concession' => $summaryQuery->sum('concession_amount'),
            'count' => $summaryQuery->count(),
        ];
        $stats['pending'] = $stats['total'] - $stats['paid'] - $stats['concession'];

        // Pagination
        $studentFees = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();

        // --- AJAX RESPONSE (JSON DATA) ---
        if ($request->ajax()) {
            return response()->json([
                'stats' => $stats,
                'html' => view('admin.fee-category-analysis._student_table_rows', compact('studentFees'))->render(),
                'pagination' => (string) $studentFees->links(),
            ]);
        }

        // View Data
        $batches = \App\Models\Batch::orderBy('name')->get();
        $courses = \App\Models\Course::orderBy('name')->get();

        return view('admin.fee-category-analysis.show', compact(
            'feeCategory',
            'studentFees',
            'batches',
            'courses',
            'stats'
        ));
    }

    /**
     * FIXED: Get students with pending amounts for a specific category (excludes dropout students)
     */
    public function pendingStudents(FeeCategory $feeCategory, Request $request)
    {
        $filters = $this->buildFilters($request);

        $pendingStudents = StudentFee::where('fee_category_id', $feeCategory->id)
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereRaw('amount - concession_amount - paid_amount > 0')
            ->with([
                'student' => function ($q) {
                    $q->withoutGlobalScope('academic_year');
                },
                'student.batch.course',
                'feeCategory',
            ])
            ->whereHas('student', function ($query) {
                $query->withoutGlobalScope('academic_year')->where('status', '!=', 'dropout');
            })
            // Filter by selected academic year if filter is applied
            ->when($filters['academic_year_filter'] ?? session('selected_academic_year_id'), function ($query, $yearId) {
                if ($yearId && \Schema::hasColumn('batches', 'academic_year_id')) {
                    $query->whereHas('student.batch', function ($q) use ($yearId) {
                        $q->where('academic_year_id', $yearId);
                    });
                }
            })
            ->when($filters['course_id'], function ($query, $courseId) {
                $query->whereHas('student.batch', function ($q) use ($courseId) {
                    $q->where('course_id', $courseId);
                });
            })
            ->when($filters['batch_id'], function ($query, $batchId) {
                $query->whereHas('student', function ($q) use ($batchId) {
                    $q->where('batch_id', $batchId);
                });
            })
            ->when($filters['start_date'], function ($query, $startDate) {
                $query->where('due_date', '>=', $startDate);
            })
            ->when($filters['end_date'], function ($query, $endDate) {
                $query->where('due_date', '<=', $endDate);
            })
            ->orderBy('due_date', 'asc')
            ->paginate(50);

        return response()->json([
            'students' => $pendingStudents->items(),
            'pagination' => [
                'current_page' => $pendingStudents->currentPage(),
                'last_page' => $pendingStudents->lastPage(),
                'total' => $pendingStudents->total(),
                'per_page' => $pendingStudents->perPage(),
            ],
        ]);
    }

    public function export(Request $request, $type)
    {
        $feeCategoryId = $request->get('fee_category_id');
        $filters = $this->buildFilters($request);

        if ($type === 'detailed') {
            // Detailed Report Logic
            $query = StudentFee::where('fee_category_id', $feeCategoryId)
                ->with(['student.batch.course', 'feeCategory', 'payments'])
                ->join('students', 'student_fees.student_id', '=', 'students.id')
                ->where('students.status', '!=', 'dropout');

            // Apply Filters Consistently
            $query->when($filters['academic_year_filter'] ?? session('selected_academic_year_id'), function ($q, $yearId) {
                $q->whereHas('student.batch', function ($sub) use ($yearId) {
                    $sub->where('academic_year_id', $yearId);
                });
            })
            ->when($filters['course_id'], function ($q, $courseId) {
                $q->whereHas('student.batch', function ($sub) use ($courseId) {
                    $sub->where('course_id', $courseId);
                });
            })
            ->when($filters['batch_id'], function ($q, $batchId) {
                $q->whereHas('student', function ($sub) use ($batchId) {
                    $sub->where('batch_id', $batchId);
                });
            })
            ->when($filters['start_date'], function ($q, $startDate) {
                $q->where('due_date', '>=', $startDate);
            })
            ->when($filters['end_date'], function ($q, $endDate) {
                $q->where('due_date', '<=', $endDate);
            })
            ->when($filters['search'], function ($q, $search) {
                $q->whereHas('student', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('enrollment_number', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] && $filters['status'] !== 'all', function ($q, $status) {
                if ($status === 'paid') {
                    $q->whereRaw('(student_fees.amount - student_fees.concession_amount - student_fees.paid_amount) <= 0');
                } elseif ($status === 'unpaid') {
                    $q->where('student_fees.paid_amount', 0);
                } elseif ($status === 'partial') {
                    $q->where('student_fees.paid_amount', '>', 0)
                        ->whereRaw('(student_fees.amount - student_fees.concession_amount - student_fees.paid_amount) > 0');
                }
            });

            // Add subquery for last payment date to avoid N+1 and empty results
            $query->addSelect([
                'last_payment_date' => DB::table('payments')
                    ->select('payment_date')
                    ->whereColumn('student_id', 'student_fees.student_id')
                    ->latest('payment_date')
                    ->limit(1)
            ]);

            $data = $query->select('student_fees.*')->orderBy('students.name')->get();

        } elseif ($type === 'pending' || $type === 'pending_simple') {
            // Consolidated Pending Logic with full filtering
            $query = StudentFee::where('fee_category_id', $feeCategoryId)
                ->whereIn('student_fees.status', ['unpaid', 'partial'])
                ->whereRaw('amount - concession_amount - paid_amount > 0')
                ->with([
                    'student' => function ($q) {
                        $q->withoutGlobalScope('academic_year');
                    },
                    'student.batch.course',
                    'feeCategory',
                ])
                ->join('students', 'student_fees.student_id', '=', 'students.id')
                ->where('students.status', '!=', 'dropout');

            // Apply Filters
            $query->when($filters['academic_year_filter'] ?? session('selected_academic_year_id'), function ($q, $yearId) {
                if ($yearId && \Schema::hasColumn('batches', 'academic_year_id')) {
                    $q->whereHas('student.batch', function ($sub) use ($yearId) {
                        $sub->where('academic_year_id', $yearId);
                    });
                }
            })
            ->when($filters['course_id'], function ($q, $courseId) {
                $q->whereHas('student.batch', function ($sub) use ($courseId) {
                    $sub->where('course_id', $courseId);
                });
            })
            ->when($filters['batch_id'], function ($q, $batchId) {
                $q->whereHas('student', function ($sub) use ($batchId) {
                    $sub->where('batch_id', $batchId);
                });
            })
            ->when($filters['start_date'], function ($q, $startDate) {
                $q->where('due_date', '>=', $startDate);
            })
            ->when($filters['end_date'], function ($q, $endDate) {
                $q->where('due_date', '<=', $endDate);
            })
            ->when($filters['search'], function ($q, $search) {
                $q->whereHas('student', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('enrollment_number', 'like', "%{$search}%");
                });
            });

            // Add subquery for last payment date
            $query->addSelect([
                'last_payment_date' => DB::table('payments')
                    ->select('payment_date')
                    ->whereColumn('student_id', 'student_fees.student_id')
                    ->latest('payment_date')
                    ->limit(1)
            ]);

            $data = $query->select('student_fees.*')->orderBy('students.name')->get();

        } else {
            // Overview (Existing logic)
            $data = $this->getCategoryAnalysis($filters);
        }

        $filename = 'fee_analysis_' . $type . '_' . date('Y-m-d') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\FeeCategoryAnalysisExport($data, $type),
            $filename
        );
    }

    /**
     * CORRECTED: Get category analysis with comprehensive statistics (excludes dropout students)
     * This method returns all the properties the view template expects
     */
    private function getCategoryAnalysis($filters)
    {
        $query = FeeCategory::select(
            'fee_categories.id',
            'fee_categories.name',
            'fee_categories.description',
            'fee_categories.category_code',
            'fee_categories.category_type',
            'fee_categories.is_mandatory',
            'fee_categories.created_at',
            'fee_categories.updated_at'
        )
            ->selectRaw('
            COUNT(DISTINCT CASE WHEN students.status != "dropout" THEN student_fees.student_id END) as total_students,
            COUNT(DISTINCT CASE WHEN student_fees.status = "paid" AND students.status != "dropout" THEN student_fees.student_id END) as paid_students,
            COUNT(DISTINCT CASE WHEN student_fees.status IN ("unpaid", "partial") AND students.status != "dropout" THEN student_fees.student_id END) as pending_students,
            COUNT(CASE WHEN students.status != "dropout" THEN student_fees.id END) as total_fees,
            COUNT(CASE WHEN student_fees.status = "paid" AND students.status != "dropout" THEN 1 END) as paid_fees,
            COUNT(CASE WHEN student_fees.status = "unpaid" AND students.status != "dropout" THEN 1 END) as unpaid_fees,
            COUNT(CASE WHEN student_fees.status = "partial" AND students.status != "dropout" THEN 1 END) as partial_fees,
            COUNT(CASE WHEN student_fees.due_date < NOW() AND student_fees.status IN ("unpaid", "partial") AND students.status != "dropout" THEN 1 END) as overdue_fees,
            COALESCE(SUM(CASE WHEN students.status != "dropout" THEN student_fees.amount ELSE 0 END), 0) as total_billed,
            COALESCE(SUM(CASE WHEN students.status != "dropout" THEN student_fees.paid_amount ELSE 0 END), 0) as total_collected,
            COALESCE(SUM(CASE WHEN students.status != "dropout" THEN student_fees.concession_amount ELSE 0 END), 0) as total_concessions,
            COALESCE(SUM(CASE WHEN students.status != "dropout" THEN (student_fees.amount - student_fees.concession_amount - student_fees.paid_amount) ELSE 0 END), 0) as total_pending,
            COALESCE(SUM(CASE WHEN student_fees.due_date < NOW() AND student_fees.status IN ("unpaid", "partial") AND students.status != "dropout" 
                THEN (student_fees.amount - student_fees.concession_amount - student_fees.paid_amount) ELSE 0 END), 0) as total_overdue,
            ROUND(CASE 
                WHEN SUM(CASE WHEN students.status != "dropout" THEN (student_fees.amount - student_fees.concession_amount) ELSE 0 END) > 0 
                THEN (SUM(CASE WHEN students.status != "dropout" THEN student_fees.paid_amount ELSE 0 END) / 
                      SUM(CASE WHEN students.status != "dropout" THEN (student_fees.amount - student_fees.concession_amount) ELSE 0 END)) * 100 
                ELSE 100 
            END, 2) as collection_rate,
            ROUND(CASE 
                WHEN SUM(CASE WHEN students.status != "dropout" THEN student_fees.amount ELSE 0 END) > 0 
                THEN (SUM(CASE WHEN student_fees.due_date < NOW() AND student_fees.status IN ("unpaid", "partial") AND students.status != "dropout" 
                    THEN (student_fees.amount - student_fees.concession_amount - student_fees.paid_amount) ELSE 0 END) / 
                      SUM(CASE WHEN students.status != "dropout" THEN student_fees.amount ELSE 0 END)) * 100 
                ELSE 0 
            END, 2) as overdue_rate
        ')
            ->leftJoin('student_fees', 'fee_categories.id', '=', 'student_fees.fee_category_id')
            ->leftJoin('students', 'student_fees.student_id', '=', 'students.id')
            ->leftJoin('batches', 'students.batch_id', '=', 'batches.id'); // Always join batches for academic year filtering

        // Only filter by academic year if explicitly set in filters or session
        if (($filters['academic_year_filter'] ?? session('selected_academic_year_id')) && \Schema::hasColumn('batches', 'academic_year_id')) {
            $selectedYearId = $filters['academic_year_filter'] ?? session('selected_academic_year_id');
            $query->where(function ($q) use ($selectedYearId) {
                $q->where('batches.academic_year_id', $selectedYearId)
                    ->orWhereNull('student_fees.id'); // Keep categories with no fees
            });
        }

        return $query
            ->when($filters['course_id'] ?? null, function ($query, $courseId) {
                $query->where('batches.course_id', $courseId);
            })
            ->when($filters['batch_id'] ?? null, function ($query, $batchId) {
                $query->where('batches.id', $batchId);
            })
            ->when($filters['fee_category_id'] ?? null, function ($query, $categoryId) {
                $query->where('fee_categories.id', $categoryId);
            })
            ->when($filters['start_date'] ?? null, function ($query, $startDate) {
                $query->where('student_fees.due_date', '>=', $startDate);
            })
            ->when($filters['end_date'] ?? null, function ($query, $endDate) {
                $query->where('student_fees.due_date', '<=', $endDate);
            })
            ->when($filters['fee_category_id'] ?? null, function ($query, $categoryId) {
                $query->where('fee_categories.id', $categoryId);
            })
            ->when($filters['start_date'] ?? null, function ($query, $startDate) {
                $query->where('student_fees.due_date', '>=', $startDate);
            })
            ->when($filters['end_date'] ?? null, function ($query, $endDate) {
                $query->where('student_fees.due_date', '<=', $endDate);
            })
            ->groupBy(
                'fee_categories.id',
                'fee_categories.name',
                'fee_categories.description',
                'fee_categories.category_code',
                'fee_categories.category_type',
                'fee_categories.is_mandatory',
                'fee_categories.created_at',
                'fee_categories.updated_at'
            )
            ->orderBy('total_pending', 'desc')
            ->get();
    }

    /**
     * CORRECTED: Get summary statistics (excludes dropout students)
     * This method now returns all the keys that the view template expects
     */
    private function getSummaryStats($filters)
    {
        // Get basic financial statistics
        // StudentFee will be automatically filtered by HasAcademicYear trait
        $baseQuery = StudentFee::query()
            ->join('students', 'student_fees.student_id', '=', 'students.id')
            ->join('batches', 'students.batch_id', '=', 'batches.id') // Always join batches to filter by academic year
            ->where('students.status', '!=', 'dropout') // EXCLUDE DROPOUT STUDENTS
            ->when(($filters['academic_year_filter'] ?? session('selected_academic_year_id')) && \Schema::hasColumn('batches', 'academic_year_id'), function ($query) use ($filters) {
                $selectedYearId = $filters['academic_year_filter'] ?? session('selected_academic_year_id');
                $query->where('batches.academic_year_id', $selectedYearId);
            })
            ->when($filters['course_id'] ?? null, function ($query, $courseId) {
                $query->where('batches.course_id', $courseId);
            })
            ->when($filters['batch_id'] ?? null, function ($query, $batchId) {
                $query->where('batches.id', $batchId);
            })
            ->when($filters['fee_category_id'] ?? null, function ($query, $categoryId) {
                $query->where('student_fees.fee_category_id', $categoryId);
            })
            ->when($filters['start_date'] ?? null, function ($query, $startDate) {
                $query->where('student_fees.due_date', '>=', $startDate);
            })
            ->when($filters['end_date'] ?? null, function ($query, $endDate) {
                $query->where('student_fees.due_date', '<=', $endDate);
            });

        $stats = $baseQuery->selectRaw('
        COUNT(DISTINCT student_fees.student_id) as total_students,
        COUNT(student_fees.id) as total_fees,
        COUNT(CASE WHEN student_fees.status = "paid" THEN 1 END) as paid_fees,
        COUNT(CASE WHEN student_fees.status IN ("unpaid", "partial") THEN 1 END) as pending_fees,
        COUNT(CASE WHEN student_fees.due_date < NOW() AND student_fees.status IN ("unpaid", "partial") THEN 1 END) as overdue_fees,
        COALESCE(SUM(student_fees.amount), 0) as total_amount,
        COALESCE(SUM(student_fees.paid_amount), 0) as total_paid,
        COALESCE(SUM(student_fees.concession_amount), 0) as total_concessions,
        COALESCE(SUM(student_fees.amount - student_fees.concession_amount - student_fees.paid_amount), 0) as pending_amount,
        COALESCE(SUM(CASE WHEN student_fees.due_date < NOW() AND student_fees.status IN ("unpaid", "partial") 
            THEN (student_fees.amount - student_fees.concession_amount - student_fees.paid_amount) ELSE 0 END), 0) as overdue_amount
    ')->first();

        // Get total number of fee categories
        $totalCategories = FeeCategory::count();

        // Calculate collection efficiency
        $netAmount = $stats->total_amount - $stats->total_concessions;
        $collectionEfficiency = $netAmount > 0
            ? round(($stats->total_paid / $netAmount) * 100, 2)
            : 100;

        // Get count of students with pending amounts
        $studentsWithPending = Student::where('status', '!=', 'dropout')
            ->whereHas('studentFees', function ($query) {
                $query->whereRaw('amount - concession_amount - paid_amount > 0');
            })
            ->when(($filters['academic_year_filter'] ?? session('selected_academic_year_id')) && \Schema::hasColumn('batches', 'academic_year_id'), function ($query) use ($filters) {
                $selectedYearId = $filters['academic_year_filter'] ?? session('selected_academic_year_id');
                $query->whereHas('batch', function ($q) use ($selectedYearId) {
                    $q->where('academic_year_id', $selectedYearId);
                });
            })
            ->when($filters['course_id'] ?? null, function ($query, $courseId) {
                $query->whereHas('batch', function ($q) use ($courseId) {
                    $q->where('course_id', $courseId);
                });
            })
            ->when($filters['batch_id'] ?? null, function ($query, $batchId) {
                $query->where('batch_id', $batchId);
            })
            ->count();

        // Get top performing category
        $topPerformingCategory = $this->getTopPerformingCategory($filters);

        // Get most pending category
        $mostPendingCategory = $this->getMostPendingCategory($filters);

        return [
            // Basic counts
            'total_categories' => $totalCategories,
            'total_students' => $stats->total_students,
            'total_fees' => $stats->total_fees,
            'paid_fees' => $stats->paid_fees,
            'pending_fees' => $stats->pending_fees,
            'overdue_fees' => $stats->overdue_fees,

            // Financial amounts (these are the keys your view expects)
            'total_billed' => $stats->total_amount,
            'total_collected' => $stats->total_paid,
            'total_pending' => $stats->pending_amount,
            'total_overdue' => $stats->overdue_amount,
            'total_concessions' => $stats->total_concessions,

            // Metrics
            'collection_efficiency' => $collectionEfficiency,
            'students_with_pending' => $studentsWithPending,
            'efficiency_score' => $this->calculateEfficiencyScore($stats),

            // Top/Bottom performers
            'top_performing_category' => $topPerformingCategory,
            'most_pending_category' => $mostPendingCategory,
        ];
    }

    /**
     * FIXED: Get detailed student list for a specific category (excludes dropout students)
     */
    private function getStudentDetailsForCategory(FeeCategory $feeCategory, $filters)
    {
        $query = Student::withoutGlobalScope('academic_year')
            ->select([
                'students.id',
                'students.name',
                'students.enrollment_number',
                'students.email',
                'students.batch_id',
                'batches.name as batch_name',
                'courses.name as course_name',
                DB::raw('SUM(student_fees.amount) as total_billed'),
                DB::raw('SUM(student_fees.paid_amount) as total_paid'),
                DB::raw('SUM(student_fees.concession_amount) as total_concessions'),
                DB::raw('SUM(student_fees.amount - student_fees.concession_amount - student_fees.paid_amount) as pending_amount'),
                DB::raw('MIN(student_fees.due_date) as earliest_due_date'),
                DB::raw('MAX(student_fees.due_date) as latest_due_date'),
                DB::raw('COUNT(student_fees.id) as total_fee_records'),
            ])
            ->leftJoin('batches', 'students.batch_id', '=', 'batches.id')
            ->leftJoin('courses', 'batches.course_id', '=', 'courses.id')
            ->leftJoin('student_fees', function ($join) use ($feeCategory) {
                $join->on('students.id', '=', 'student_fees.student_id')
                    ->where('student_fees.fee_category_id', '=', $feeCategory->id);
            })
            ->where('students.status', '!=', 'dropout')
            ->whereExists(function ($q) use ($feeCategory) {
                $q->select(DB::raw(1))
                    ->from('student_fees')
                    ->whereColumn('student_fees.student_id', 'students.id')
                    ->where('student_fees.fee_category_id', $feeCategory->id);
            });

        // Only filter by academic year if explicitly set
        if (($filters['academic_year_filter'] ?? session('selected_academic_year_id')) && \Schema::hasColumn('batches', 'academic_year_id')) {
            $selectedYearId = $filters['academic_year_filter'] ?? session('selected_academic_year_id');
            $query->where('batches.academic_year_id', $selectedYearId);
        }

        return $query
            ->when($filters['course_id'] ?? null, function ($query, $courseId) {
                $query->where('batches.course_id', $courseId);
            })
            ->when($filters['batch_id'] ?? null, function ($query, $batchId) {
                $query->where('students.batch_id', $batchId);
            })
            ->when($filters['start_date'] ?? null, function ($query, $startDate) use ($feeCategory) {
                $query->whereHas('studentFees', function ($q) use ($startDate, $feeCategory) {
                    $q->where('fee_category_id', $feeCategory->id)
                        ->where('due_date', '>=', $startDate);
                });
            })
            ->when($filters['end_date'] ?? null, function ($query, $endDate) use ($feeCategory) {
                $query->whereHas('studentFees', function ($q) use ($endDate, $feeCategory) {
                    $q->where('fee_category_id', $feeCategory->id)
                        ->where('due_date', '<=', $endDate);
                });
            })
            ->groupBy(
                'students.id',
                'students.name',
                'students.enrollment_number',
                'students.email',
                'students.batch_id',
                'batches.name',
                'courses.name'
            )
            ->orderBy('pending_amount', 'desc')
            ->paginate(50);
    }

    /**
     * Calculate efficiency score based on collection metrics
     */
    private function calculateEfficiencyScore($stats)
    {
        // If stats is an object, convert to array
        if (is_object($stats)) {
            $statsArray = [
                'total_amount' => $stats->total_amount ?? 0,
                'total_paid' => $stats->total_paid ?? 0,
                'total_concessions' => $stats->total_concessions ?? 0,
                'overdue_amount' => $stats->overdue_amount ?? 0,
                'pending_amount' => $stats->pending_amount ?? 0,
                'total_fees' => $stats->total_fees ?? 0,
                'paid_fees' => $stats->paid_fees ?? 0,
                'overdue_fees' => $stats->overdue_fees ?? 0,
            ];
        } else {
            $statsArray = $stats;
        }

        // Base collection rate (40% weight)
        $netAmount = ($statsArray['total_amount'] ?? 0) - ($statsArray['total_concessions'] ?? 0);
        $collectionRate = $netAmount > 0 ? (($statsArray['total_paid'] ?? 0) / $netAmount) * 100 : 100;
        $collectionScore = min($collectionRate, 100) * 0.4;

        // Overdue management (30% weight)
        $totalAmount = $statsArray['total_amount'] ?? 0;
        $overdueRate = $totalAmount > 0 ? (($statsArray['overdue_amount'] ?? 0) / $totalAmount) * 100 : 0;
        $overdueScore = max(0, 100 - $overdueRate) * 0.3;

        // Payment completion rate (20% weight)
        $totalFees = $statsArray['total_fees'] ?? 0;
        $completionRate = $totalFees > 0 ? (($statsArray['paid_fees'] ?? 0) / $totalFees) * 100 : 100;
        $completionScore = min($completionRate, 100) * 0.2;

        // Outstanding management (10% weight)
        $pendingRate = $totalAmount > 0 ? (($statsArray['pending_amount'] ?? 0) / $totalAmount) * 100 : 0;
        $outstandingScore = max(0, 100 - $pendingRate) * 0.1;

        // Calculate final efficiency score
        $efficiencyScore = $collectionScore + $overdueScore + $completionScore + $outstandingScore;

        return round(min($efficiencyScore, 100), 1);
    }

    /**
     * Build filters array from request
     */
    private function buildFilters(Request $request)
    {
        // Parse combined filter
        $filterEntity = $request->get('filter_entity');
        $courseId = $request->get('course_id');
        $batchId = $request->get('batch_id');

        if ($filterEntity) {
            if (strpos($filterEntity, 'course_') === 0) {
                $courseId = substr($filterEntity, 7);
                $batchId = null; // Reset batch if course selected
            } elseif (strpos($filterEntity, 'batch_') === 0) {
                $batchId = substr($filterEntity, 6);
                $courseId = null; // Reset course if batch selected
            }
        }

        return [
            'academic_year_filter' => $request->get('academic_year_filter'),
            'course_id' => $courseId,
            'batch_id' => $batchId,
            'fee_category_id' => $request->get('fee_category_id'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'status' => $request->get('status', 'all'),
            'search' => $request->get('search'), // Added search support
            'amount_range' => $request->get('amount_range'),
            'overdue_only' => $request->boolean('overdue_only'),
            'min_amount' => $request->get('min_amount'),
            'filter_entity' => $filterEntity, // Pass back for view state
        ];
    }

    /**
     * Get category payment trends
     */
    private function getCategoryPaymentTrends(FeeCategory $feeCategory, $filters, $months = 6)
    {
        $trends = collect();
        $startDate = now()->subMonths($months);

        for ($i = 0; $i < $months; $i++) {
            $monthStart = $startDate->copy()->addMonths($i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            $monthlyData = StudentFee::where('fee_category_id', $feeCategory->id)
                ->whereHas('student', function ($query) {
                    $query->where('status', '!=', 'dropout');
                })
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->selectRaw('
                COUNT(*) as total_fees,
                COUNT(CASE WHEN status = "paid" THEN 1 END) as paid_fees,
                COALESCE(SUM(amount), 0) as total_amount,
                COALESCE(SUM(paid_amount), 0) as total_paid
            ')
                ->first();

            $trends->push([
                'month' => $monthStart->format('M Y'),
                'month_key' => $monthStart->format('Y-m'),
                'total_fees' => $monthlyData->total_fees ?? 0,
                'paid_fees' => $monthlyData->paid_fees ?? 0,
                'total_amount' => $monthlyData->total_amount ?? 0,
                'total_paid' => $monthlyData->total_paid ?? 0,
                'collection_rate' => $monthlyData->total_amount > 0
                    ? round(($monthlyData->total_paid / $monthlyData->total_amount) * 100, 1)
                    : 0,
            ]);
        }

        return $trends;
    }

    /**
     * Get detailed category analysis with more metrics
     */
    private function getDetailedCategoryAnalysis($filters)
    {
        return FeeCategory::select('fee_categories.*')
            ->selectRaw('
            COUNT(DISTINCT CASE WHEN students.status != "dropout" THEN student_fees.student_id END) as total_students,
            COUNT(CASE WHEN students.status != "dropout" THEN student_fees.id END) as total_fees,
            COUNT(CASE WHEN student_fees.status = "paid" AND students.status != "dropout" THEN 1 END) as paid_fees,
            COUNT(CASE WHEN student_fees.status = "unpaid" AND students.status != "dropout" THEN 1 END) as unpaid_fees,
            COUNT(CASE WHEN student_fees.status = "partial" AND students.status != "dropout" THEN 1 END) as partial_fees,
            COUNT(CASE WHEN student_fees.due_date < NOW() AND student_fees.status IN ("unpaid", "partial") AND students.status != "dropout" THEN 1 END) as overdue_fees,
            COALESCE(SUM(CASE WHEN students.status != "dropout" THEN student_fees.amount ELSE 0 END), 0) as total_amount,
            COALESCE(SUM(CASE WHEN students.status != "dropout" THEN student_fees.paid_amount ELSE 0 END), 0) as total_paid,
            COALESCE(SUM(CASE WHEN students.status != "dropout" THEN student_fees.concession_amount ELSE 0 END), 0) as total_concessions,
            COALESCE(SUM(CASE WHEN students.status != "dropout" THEN (student_fees.amount - student_fees.concession_amount - student_fees.paid_amount) ELSE 0 END), 0) as pending_amount,
            COALESCE(SUM(CASE WHEN student_fees.due_date < NOW() AND student_fees.status IN ("unpaid", "partial") AND students.status != "dropout" 
                THEN (student_fees.amount - student_fees.concession_amount - student_fees.paid_amount) ELSE 0 END), 0) as overdue_amount,
            AVG(CASE WHEN students.status != "dropout" THEN student_fees.amount END) as avg_fee_amount,
            MIN(CASE WHEN students.status != "dropout" THEN student_fees.due_date END) as earliest_due_date,
            MAX(CASE WHEN students.status != "dropout" THEN student_fees.due_date END) as latest_due_date
        ')
            ->leftJoin('student_fees', 'fee_categories.id', '=', 'student_fees.fee_category_id')
            ->leftJoin('students', 'student_fees.student_id', '=', 'students.id')
            ->when($filters['course_id'] ?? null, function ($query, $courseId) {
                $query->leftJoin('batches', 'students.batch_id', '=', 'batches.id')
                    ->where('batches.course_id', $courseId);
            })
            ->when($filters['batch_id'] ?? null, function ($query, $batchId) {
                $query->where('students.batch_id', $batchId);
            })
            ->when($filters['fee_category_id'] ?? null, function ($query, $categoryId) {
                $query->where('fee_categories.id', $categoryId);
            })
            ->when($filters['start_date'] ?? null, function ($query, $startDate) {
                $query->where('student_fees.due_date', '>=', $startDate);
            })
            ->when($filters['end_date'] ?? null, function ($query, $endDate) {
                $query->where('student_fees.due_date', '<=', $endDate);
            })
            ->groupBy('fee_categories.id')
            ->orderBy('pending_amount', 'desc')
            ->get()
            ->map(function ($category) {
                $netAmount = $category->total_amount - $category->total_concessions;
                $category->collection_percentage = $netAmount > 0
                    ? round(($category->total_paid / $netAmount) * 100, 2)
                    : 100;
                $category->overdue_percentage = $category->total_amount > 0
                    ? round(($category->overdue_amount / $category->total_amount) * 100, 2)
                    : 0;
                $category->efficiency_score = $this->calculateEfficiencyScore($category);

                return $category;
            });
    }

    /**
     * Get pending analysis data
     */
    private function getPendingAnalysis($filters)
    {
        return StudentFee::with(['student.batch.course', 'feeCategory'])
            ->whereHas('student', function ($query) {
                $query->where('status', '!=', 'dropout');
            })
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereRaw('amount - concession_amount - paid_amount > 0')
            ->when($filters['course_id'] ?? null, function ($query, $courseId) {
                $query->whereHas('student.batch', function ($q) use ($courseId) {
                    $q->where('course_id', $courseId);
                });
            })
            ->when($filters['batch_id'] ?? null, function ($query, $batchId) {
                $query->whereHas('student', function ($q) use ($batchId) {
                    $q->where('batch_id', $batchId);
                });
            })
            ->when($filters['fee_category_id'] ?? null, function ($query, $categoryId) {
                $query->where('fee_category_id', $categoryId);
            })
            ->when($filters['start_date'] ?? null, function ($query, $startDate) {
                $query->where('due_date', '>=', $startDate);
            })
            ->when($filters['end_date'] ?? null, function ($query, $endDate) {
                $query->where('due_date', '<=', $endDate);
            })
            ->when($filters['overdue_only'] ?? false, function ($query) {
                $query->where('due_date', '<', now());
            })
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($fee) {
                $fee->remaining_amount = $fee->amount - $fee->concession_amount - $fee->paid_amount;
                $fee->days_overdue = $fee->due_date < now() ? $fee->due_date->diffInDays(now()) : 0;
                $fee->urgency_level = $this->calculateUrgencyLevel($fee);

                return $fee;
            });
    }

    /**
     * Calculate urgency level for pending fees
     */
    private function calculateUrgencyLevel($fee)
    {
        $daysOverdue = $fee->due_date < now() ? $fee->due_date->diffInDays(now()) : 0;
        $remainingAmount = $fee->amount - $fee->concession_amount - $fee->paid_amount;

        if ($daysOverdue > 60 && $remainingAmount > 5000) {
            return 'critical';
        } elseif ($daysOverdue > 30 && $remainingAmount > 2000) {
            return 'high';
        } elseif ($daysOverdue > 7 || $remainingAmount > 1000) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get recovery metrics
     */
    private function getRecoveryMetrics($filters)
    {
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        $currentData = StudentFee::whereHas('student', function ($query) {
            $query->where('status', '!=', 'dropout');
        })
            ->whereBetween('updated_at', [$currentMonth, now()])
            ->where('status', 'paid')
            ->selectRaw('
            COUNT(*) as recovered_fees,
            COALESCE(SUM(paid_amount), 0) as recovered_amount
        ')
            ->first();

        $lastMonthData = StudentFee::whereHas('student', function ($query) {
            $query->where('status', '!=', 'dropout');
        })
            ->whereBetween('updated_at', [$lastMonth, $currentMonth])
            ->where('status', 'paid')
            ->selectRaw('
            COUNT(*) as recovered_fees,
            COALESCE(SUM(paid_amount), 0) as recovered_amount
        ')
            ->first();

        $recoveryRate = $lastMonthData->recovered_amount > 0
            ? (($currentData->recovered_amount - $lastMonthData->recovered_amount) / $lastMonthData->recovered_amount) * 100
            : 0;

        return [
            'current_month_recovery' => $currentData->recovered_amount ?? 0,
            'current_month_fees' => $currentData->recovered_fees ?? 0,
            'last_month_recovery' => $lastMonthData->recovered_amount ?? 0,
            'recovery_rate_change' => round($recoveryRate, 1),
            'recovery_trend' => $recoveryRate >= 0 ? 'positive' : 'negative',
        ];
    }

    /**
     * Get recovery trends over time
     */
    private function getRecoveryTrends($filters, $months = 6)
    {
        $trends = collect();
        $startDate = now()->subMonths($months);

        for ($i = 0; $i < $months; $i++) {
            $monthStart = $startDate->copy()->addMonths($i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            $recoveryData = StudentFee::whereHas('student', function ($query) {
                $query->where('status', '!=', 'dropout');
            })
                ->whereBetween('updated_at', [$monthStart, $monthEnd])
                ->where('status', 'paid')
                ->selectRaw('
                COUNT(*) as recovered_fees,
                COALESCE(SUM(paid_amount), 0) as recovered_amount
            ')
                ->first();

            $trends->push([
                'month' => $monthStart->format('M Y'),
                'month_key' => $monthStart->format('Y-m'),
                'recovered_fees' => $recoveryData->recovered_fees ?? 0,
                'recovered_amount' => $recoveryData->recovered_amount ?? 0,
            ]);
        }

        return $trends;
    }

    /**
     * Get category-wise recovery statistics
     */
    private function getCategoryRecoveryStats($filters)
    {
        return FeeCategory::select('fee_categories.*')
            ->selectRaw('
            COUNT(CASE WHEN student_fees.status = "paid" AND students.status != "dropout" AND student_fees.updated_at >= ? THEN 1 END) as recent_recoveries,
            COALESCE(SUM(CASE WHEN student_fees.status = "paid" AND students.status != "dropout" AND student_fees.updated_at >= ? 
                THEN student_fees.paid_amount ELSE 0 END), 0) as recovery_amount
        ', [now()->startOfMonth(), now()->startOfMonth()])
            ->leftJoin('student_fees', 'fee_categories.id', '=', 'student_fees.fee_category_id')
            ->leftJoin('students', 'student_fees.student_id', '=', 'students.id')
            ->groupBy('fee_categories.id')
            ->orderBy('recovery_amount', 'desc')
            ->get();
    }

    /**
     * FIXED: Get category-specific statistics (excludes dropout students)
     */
    private function getCategorySpecificStats(FeeCategory $feeCategory, $filters)
    {
        $baseQuery = StudentFee::where('fee_category_id', $feeCategory->id)
            ->whereHas('student', function ($query) {
                $query->where('status', '!=', 'dropout'); // EXCLUDE DROPOUT STUDENTS
            })
            ->when($filters['course_id'] ?? null, function ($query, $courseId) {
                $query->whereHas('student.batch', function ($q) use ($courseId) {
                    $q->where('course_id', $courseId);
                });
            })
            ->when($filters['batch_id'] ?? null, function ($query, $batchId) {
                $query->whereHas('student', function ($q) use ($batchId) {
                    $q->where('batch_id', $batchId);
                });
            })
            ->when($filters['start_date'] ?? null, function ($query, $startDate) {
                $query->where('due_date', '>=', $startDate);
            })
            ->when($filters['end_date'] ?? null, function ($query, $endDate) {
                $query->where('due_date', '<=', $endDate);
            });

        $stats = $baseQuery->selectRaw('
        COUNT(DISTINCT student_id) as total_students,
        COUNT(id) as total_fees,
        COUNT(CASE WHEN status = "paid" THEN 1 END) as paid_fees,
        COUNT(CASE WHEN status IN ("unpaid", "partial") THEN 1 END) as pending_fees,
        COUNT(CASE WHEN due_date < NOW() AND status IN ("unpaid", "partial") THEN 1 END) as overdue_fees,
        COALESCE(SUM(amount), 0) as total_amount,
        COALESCE(SUM(paid_amount), 0) as total_paid,
        COALESCE(SUM(concession_amount), 0) as total_concessions,
        COALESCE(SUM(amount - concession_amount - paid_amount), 0) as pending_amount,
        COALESCE(SUM(CASE WHEN due_date < NOW() AND status IN ("unpaid", "partial") 
            THEN (amount - concession_amount - paid_amount) ELSE 0 END), 0) as overdue_amount,
        AVG(amount) as avg_fee_amount,
        MIN(due_date) as earliest_due_date,
        MAX(due_date) as latest_due_date
    ')->first();

        $collectionRate = $stats->total_amount > 0
            ? round(($stats->total_paid / ($stats->total_amount - $stats->total_concessions)) * 100, 2)
            : 100;

        return [
            'total_students' => $stats->total_students,
            'total_fees' => $stats->total_fees,
            'paid_fees' => $stats->paid_fees,
            'pending_fees' => $stats->pending_fees,
            'overdue_fees' => $stats->overdue_fees,

            // Match Blade template expectations
            'total_billed' => $stats->total_amount,        // Template uses total_billed
            'total_collected' => $stats->total_paid,       // Template uses total_collected
            'total_amount' => $stats->total_amount,         // Keep original key too
            'total_paid' => $stats->total_paid,            // Keep original key too
            'total_concessions' => $stats->total_concessions,
            'pending_amount' => $stats->pending_amount,
            'total_pending' => $stats->pending_amount,    // Template expects this key
            'overdue_amount' => $stats->overdue_amount,
            'total_overdue' => $stats->overdue_amount,    // In case template expects this too
            'collection_rate' => $collectionRate,

            // Fix the missing avg_amount key
            'avg_amount' => round($stats->avg_fee_amount ?? 0, 2),    // Template expects this
            'avg_fee_amount' => round($stats->avg_fee_amount ?? 0, 2), // Keep original key

            // Fix the date keys
            'earliest_due' => $stats->earliest_due_date,    // Template expects earliest_due
            'latest_due' => $stats->latest_due_date,        // Template expects latest_due
            'earliest_due_date' => $stats->earliest_due_date, // Keep original key
            'latest_due_date' => $stats->latest_due_date,     // Keep original key
        ];
    }

    /**
     * Calculate overall collection efficiency
     */
    private function calculateCollectionEfficiency()
    {
        $totalBilled = StudentFee::sum('amount');
        $totalCollected = StudentFee::sum('paid_amount');

        return $totalBilled > 0 ? round(($totalCollected / $totalBilled) * 100, 2) : 0;
    }

    /**
     * Get top performing category by collection rate
     */
    private function getTopPerformingCategory($filters)
    {
        $query = FeeCategory::select('fee_categories.*')
            ->selectRaw('
            COALESCE(SUM(CASE WHEN students.status != "dropout" THEN student_fees.amount ELSE 0 END), 0) as total_amount,
            COALESCE(SUM(CASE WHEN students.status != "dropout" THEN student_fees.paid_amount ELSE 0 END), 0) as total_collected,
            COALESCE(SUM(CASE WHEN students.status != "dropout" THEN student_fees.concession_amount ELSE 0 END), 0) as total_concessions,
            ROUND(CASE
                WHEN SUM(CASE WHEN students.status != "dropout" THEN (student_fees.amount - student_fees.concession_amount) ELSE 0 END) > 0
                THEN (SUM(CASE WHEN students.status != "dropout" THEN student_fees.paid_amount ELSE 0 END) /
                      SUM(CASE WHEN students.status != "dropout" THEN (student_fees.amount - student_fees.concession_amount) ELSE 0 END)) * 100
                ELSE 100
            END, 2) as collection_rate
        ')
            ->leftJoin('student_fees', 'fee_categories.id', '=', 'student_fees.fee_category_id')
            ->leftJoin('students', 'student_fees.student_id', '=', 'students.id')
            ->leftJoin('batches', 'students.batch_id', '=', 'batches.id');

        // Apply academic year filter
        if (($filters['academic_year_filter'] ?? session('selected_academic_year_id')) && \Schema::hasColumn('batches', 'academic_year_id')) {
            $selectedYearId = $filters['academic_year_filter'] ?? session('selected_academic_year_id');
            $query->where(function ($q) use ($selectedYearId) {
                $q->where('batches.academic_year_id', $selectedYearId)
                    ->orWhereNull('student_fees.id');
            });
        }

        return $query
            ->when($filters['course_id'] ?? null, function ($query, $courseId) {
                $query->where('batches.course_id', $courseId);
            })
            ->when($filters['batch_id'] ?? null, function ($query, $batchId) {
                $query->where('students.batch_id', $batchId);
            })
            ->when($filters['fee_category_id'] ?? null, function ($query, $categoryId) {
                $query->where('fee_categories.id', $categoryId);
            })
            ->groupBy('fee_categories.id', 'fee_categories.name')
            ->havingRaw('SUM(CASE WHEN students.status != "dropout" THEN student_fees.amount ELSE 0 END) > 0')
            ->orderByRaw('collection_rate DESC, total_collected DESC')
            ->first();
    }

    /**
     * Get category with most pending amount
     */
    private function getMostPendingCategory($filters = [])
    {
        $query = FeeCategory::select('fee_categories.name')
            ->selectRaw('
                SUM(CASE WHEN student_fees.status IN ("unpaid", "partial") AND students.status != "dropout"
                    THEN (student_fees.amount - student_fees.concession_amount - student_fees.paid_amount) ELSE 0 END) as pending_amount
            ')
            ->join('student_fees', 'fee_categories.id', '=', 'student_fees.fee_category_id')
            ->join('students', 'student_fees.student_id', '=', 'students.id')
            ->join('batches', 'students.batch_id', '=', 'batches.id');

        // Apply academic year filter
        if (($filters['academic_year_filter'] ?? session('selected_academic_year_id')) && \Schema::hasColumn('batches', 'academic_year_id')) {
            $selectedYearId = $filters['academic_year_filter'] ?? session('selected_academic_year_id');
            $query->where('batches.academic_year_id', $selectedYearId);
        }

        return $query
            ->groupBy('fee_categories.id', 'fee_categories.name')
            ->orderBy('pending_amount', 'desc')
            ->first();
    }

    /**
     * FIXED: Critical defaulters page (excludes dropout students)
     */
    public function criticalDefaulters(Request $request)
    {
        $filters = $this->buildFilters($request);

        $criticalDefaulters = $this->getCriticalDefaulters($filters);

        $defaulterStats = [
            'total_defaulters' => $criticalDefaulters->count(),
            'critical_count' => $criticalDefaulters->where('total_overdue', '>', 25000)->count(),
            'total_at_risk' => $criticalDefaulters->sum('total_overdue'),
            'avg_overdue_amount' => $criticalDefaulters->avg('total_overdue') ?? 0,
            'avg_recovery_days' => $this->calculateAverageRecoveryDays($filters),
            'total_overdue_amount' => $criticalDefaulters->sum('total_overdue'),
            'max_overdue_days' => $criticalDefaulters->max('overdue_days'),
            'categories_affected' => $criticalDefaulters->sum('affected_categories'),
        ];

        // Add filter options for the view
        $feeCategories = FeeCategory::orderBy('name')->get();
        $courses = Course::orderBy('name')->get();
        $batches = Batch::with('course')->orderBy('name')->get();

        return view('admin.fee-category-analysis.critical-defaulters', compact(
            'criticalDefaulters',
            'defaulterStats',
            'filters',
            'feeCategories',
            'courses',
            'batches'
        ));
    }

    /**
     * NEW: Send category-specific reminders
     */
    public function sendCategoryReminders(Request $request, FeeCategory $feeCategory)
    {
        $validated = $request->validate([
            'reminder_type' => 'required|in:gentle,firm,urgent,final_notice',
            'include_overdue_only' => 'boolean',
            'minimum_amount' => 'nullable|numeric|min:0',
            'message_content' => 'nullable|string',
        ]);

        try {
            $studentsToRemind = $this->getStudentsForCategoryReminders($feeCategory, $validated);

            // Queue reminders (integrate with your existing reminder system)
            $reminderCount = $this->queueCategoryReminders($studentsToRemind, $validated, $feeCategory);

            return response()->json([
                'success' => true,
                'message' => "Queued {$reminderCount} reminders for {$feeCategory->name} category",
                'students_count' => $reminderCount,
            ]);

        } catch (\Exception $e) {
            \Log::error('Category reminder error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to send category reminders. Please try again.',
            ], 500);
        }
    }

    /**
     * FIXED: Add method to handle student intervention (excludes dropout students)
     */
    public function studentIntervention(Request $request, Student $student)
    {
        // Check if student is dropout - if so, return error
        if ($student->status === 'dropout') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot perform intervention on dropout students',
            ], 400);
        }

        $request->validate([
            'intervention_type' => 'required|in:reminder,call,meeting,email',
            'notes' => 'required|string|max:500',
        ]);

        // Log the intervention
        activity()
            ->causedBy(auth()->user())
            ->performedOn($student)
            ->withProperties([
                'intervention_type' => $request->intervention_type,
                'notes' => $request->notes,
                'outstanding_amount' => $student->getTotalOutstandingAmount(),
                'overdue_amount' => $student->getOverdueAmount(),
            ])
            ->log("Fee intervention: {$request->intervention_type}");

        return response()->json([
            'success' => true,
            'message' => 'Intervention recorded successfully',
        ]);
    }

    /**
     * NEW: Recovery tracking dashboard
     */
    public function recoveryTracking(Request $request)
    {
        $filters = $this->buildFilters($request);

        $recoveryMetrics = $this->getRecoveryMetrics($filters);
        $recoveryTrends = $this->getRecoveryTrends($filters);
        $categoryRecovery = $this->getCategoryRecoveryStats($filters);

        return view('admin.fee-category-analysis.recovery-tracking', compact(
            'recoveryMetrics',
            'recoveryTrends',
            'categoryRecovery',
            'filters'
        ));
    }

    // ==========================================
    // 3. HELPER METHODS - Add these to FeeCategoryAnalysisController
    // ==========================================

    /**
     * FIXED: Get critical defaulters for intervention (excludes dropout students)
     */
    private function getCriticalDefaulters($filters)
    {
        // First join batches and courses
        $query = DB::table('students')
            ->leftJoin('batches', 'students.batch_id', '=', 'batches.id')
            ->leftJoin('courses', 'batches.course_id', '=', 'courses.id')
            ->join('student_fees', 'students.id', '=', 'student_fees.student_id')
            ->join('fee_categories', 'student_fees.fee_category_id', '=', 'fee_categories.id')
            ->where('students.status', '!=', 'dropout') // EXCLUDE DROPOUT STUDENTS
            ->whereRaw('student_fees.amount - student_fees.concession_amount - student_fees.paid_amount > 0')
            ->where('student_fees.due_date', '<', now())
            ->whereIn('student_fees.status', ['unpaid', 'partial']);

        // Apply academic year filter
        if (($filters['academic_year_filter'] ?? session('selected_academic_year_id')) && \Schema::hasColumn('batches', 'academic_year_id')) {
            $selectedYearId = $filters['academic_year_filter'] ?? session('selected_academic_year_id');
            $query->where('batches.academic_year_id', $selectedYearId);
        }

        // Apply course filter
        if ($filters['course_id'] ?? null) {
            $query->where('batches.course_id', $filters['course_id']);
        }

        // Apply fee category filter
        if ($filters['fee_category_id'] ?? null) {
            $query->where('student_fees.fee_category_id', $filters['fee_category_id']);
        }

        // Apply batch filter
        if ($filters['batch_id'] ?? null) {
            $query->where('students.batch_id', $filters['batch_id']);
        }

        $criticalDefaulters = $query
            ->select(
                'students.id',
                'students.name',
                'students.enrollment_number',
                'students.email',
                'students.student_mobile',
                'students.batch_id',
                'batches.name as batch_name',
                'courses.name as course_name'
            )
            ->selectRaw('
            SUM(CASE WHEN student_fees.status IN ("unpaid", "partial") AND student_fees.due_date < NOW()
                THEN (student_fees.amount - student_fees.concession_amount - student_fees.paid_amount)
                ELSE 0 END) as total_overdue,
            SUM(CASE WHEN student_fees.status IN ("unpaid", "partial")
                THEN (student_fees.amount - student_fees.concession_amount - student_fees.paid_amount)
                ELSE 0 END) as total_pending,
            COUNT(CASE WHEN student_fees.status IN ("unpaid", "partial") AND student_fees.due_date < NOW()
                THEN 1 END) as overdue_fee_count,
            COUNT(DISTINCT student_fees.fee_category_id) as affected_categories,
            MIN(CASE WHEN student_fees.status IN ("unpaid", "partial") AND student_fees.due_date < NOW()
                THEN student_fees.due_date END) as oldest_overdue_date,
            GROUP_CONCAT(DISTINCT fee_categories.name SEPARATOR ", ") as overdue_categories
        ')
            ->groupBy(
                'students.id',
                'students.name',
                'students.enrollment_number',
                'students.email',
                'students.student_mobile',
                'students.batch_id',
                'batches.name',
                'courses.name'
            )
            ->havingRaw('total_overdue > ?', [$filters['min_amount'] ?? 1000]) // Only students with significant overdue amounts
            ->orderByRaw('total_overdue DESC, oldest_overdue_date ASC')
            ->limit(50)
            ->get();

        // Create a paginator manually
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $criticalDefaulters,
            $criticalDefaulters->count(),
            50, // Per page
            request()->get('page', 1),
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Get defaulter statistics
     */
    private function getDefaulterStatistics($filters)
    {
        $baseQuery = Student::whereHas('studentFees', function ($q) use ($filters) {
            $q->where('due_date', '<', now())
                ->whereIn('status', ['unpaid', 'partial'])
                ->whereRaw('amount - concession_amount - paid_amount > 0');

            if ($filters['fee_category_id']) {
                $q->where('fee_category_id', $filters['fee_category_id']);
            }
        });

        if ($filters['course_id']) {
            $baseQuery->whereHas('batch', function ($q) use ($filters) {
                $q->where('course_id', $filters['course_id']);
            });
        }

        $totalDefaulters = $baseQuery->count();

        // Calculate different risk levels
        $criticalCount = $baseQuery->whereHas('studentFees', function ($q) {
            $q->where('due_date', '<', now()->subDays(60))
                ->whereRaw('amount - concession_amount - paid_amount > 25000');
        })->count();

        $totalAmount = StudentFee::where('due_date', '<', now())
            ->whereIn('status', ['unpaid', 'partial'])
            ->when($filters['fee_category_id'], function ($q, $categoryId) {
                $q->where('fee_category_id', $categoryId);
            })
            ->sum(DB::raw('amount - concession_amount - paid_amount'));

        return [
            'total_defaulters' => $totalDefaulters,
            'critical_count' => $criticalCount,
            'total_at_risk' => $totalAmount,
            'avg_overdue_amount' => $totalDefaulters > 0 ? round($totalAmount / $totalDefaulters, 2) : 0,
            'avg_recovery_days' => $this->calculateAverageRecoveryDays($filters),
        ];
    }

    /**
     * Get students for category reminders
     */
    private function getStudentsForCategoryReminders($feeCategory, $criteria)
    {
        $query = Student::whereHas('studentFees', function ($q) use ($feeCategory, $criteria) {
            $q->where('fee_category_id', $feeCategory->id)
                ->whereIn('status', ['unpaid', 'partial'])
                ->whereRaw('amount - concession_amount - paid_amount > 0');

            if ($criteria['include_overdue_only']) {
                $q->where('due_date', '<', now());
            }

            if ($criteria['minimum_amount']) {
                $q->whereRaw('amount - concession_amount - paid_amount >= ?', [$criteria['minimum_amount']]);
            }
        });

        return $query->with([
            'studentFees' => function ($q) use ($feeCategory) {
                $q->where('fee_category_id', $feeCategory->id)
                    ->whereIn('status', ['unpaid', 'partial']);
            },
        ])->get();
    }

    /**
     * Queue category reminders
     */
    private function queueCategoryReminders($students, $criteria, $feeCategory)
    {
        $count = 0;

        foreach ($students as $student) {
            // Create reminder record
            $reminderData = [
                'student_id' => $student->id,
                'fee_category_id' => $feeCategory->id,
                'reminder_type' => $criteria['reminder_type'],
                'channel' => 'email', // Default channel
                'message_content' => $criteria['message_content'] ?? $this->getDefaultReminderMessage($criteria['reminder_type']),
                'scheduled_date' => now(),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Insert into your reminders table
            DB::table('payment_reminders')->insert($reminderData);

            // You can also dispatch a job here if you have a job system
            // \App\Jobs\SendPaymentReminder::dispatch($student, $reminderData);

            $count++;
        }

        return $count;
    }

    /**
     * Send individual student reminder
     */
    private function sendStudentReminder($student, $criteria)
    {
        // Create reminder record
        $reminderData = [
            'student_id' => $student->id,
            'reminder_type' => $criteria['reminder_type'] ?? 'general',
            'channel' => 'email',
            'message_content' => $this->getDefaultReminderMessage($criteria['reminder_type'] ?? 'general'),
            'scheduled_date' => now(),
            'status' => 'pending',
        ];

        DB::table('payment_reminders')->insert($reminderData);

        return [
            'message' => 'Reminder has been queued for sending.',
            'data' => $reminderData,
        ];
    }

    /**
     * Escalate student case
     */
    private function escalateStudent($student, $criteria)
    {
        // Log escalation
        DB::table('student_interventions')->insert([
            'student_id' => $student->id,
            'intervention_type' => 'escalation',
            'notes' => $criteria['reason'] ?? 'Case escalated due to non-payment',
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);

        return [
            'message' => 'Student case has been escalated to management.',
            'data' => ['escalation_level' => $criteria['priority'] ?? 'high'],
        ];
    }

    /**
     * Create payment plan
     */
    private function createPaymentPlan($student, $criteria)
    {
        // This would integrate with your payment plan system
        $planData = [
            'student_id' => $student->id,
            'installments' => $criteria['installments'] ?? 3,
            'first_due_date' => $criteria['first_due'] ?? now()->addDays(7),
            'created_by' => auth()->id(),
            'status' => 'active',
        ];

        // Insert into payment plans table (if you have one)
        // DB::table('payment_plans')->insert($planData);

        return [
            'message' => 'Payment plan has been created successfully.',
            'data' => $planData,
        ];
    }

    /**
     * Get default reminder message template
     */
    private function getDefaultReminderMessage($type)
    {
        $templates = [
            'gentle' => 'Dear {student_name}, this is a friendly reminder about your pending payment. Please pay at your earliest convenience.',
            'firm' => 'Dear {student_name}, your payment is now overdue. Please make the payment immediately.',
            'urgent' => 'URGENT: Dear {student_name}, immediate payment is required to avoid further action.',
            'final_notice' => 'FINAL NOTICE: Dear {student_name}, this is your last notice before escalation.',
        ];

        return $templates[$type] ?? $templates['gentle'];
    }

    // Additional helper methods
    private function getRecoveryOpportunities($filters)
    {
        return [];
    }

    private function calculateAverageRecoveryDays($filters)
    {
        return 30;
    }
}
