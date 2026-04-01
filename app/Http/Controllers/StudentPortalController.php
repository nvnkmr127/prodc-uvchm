<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Admission;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use App\Models\Holiday;

class StudentPortalController extends Controller
{
    /**
     * Show the login page.
     */
    public function loginPage()
    {
        if (session()->has('student_portal_auth')) {
            return redirect()->route('student.dashboard');
        }

        return response()
            ->view('public.student-portal.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT')
            ->header('X-LiteSpeed-Cache-Control', 'no-cache');
    }

    /**
     * Handle authentication logic.
     */
    public function authenticate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enrollment_number' => 'required|string',
            'mobile_number' => 'required|string|regex:/^[6-9][0-9]{9}$/',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Rate limiting (simple session based for now, ideally Redis)
        $key = 'login_attempts_' . $request->ip();
        if (session()->get($key, 0) > 5) {
            return back()->withErrors(['error' => 'Too many login attempts. Please try again later.']);
        }
        session()->put($key, session()->get($key, 0) + 1);

        $student = Student::where('enrollment_number', $request->enrollment_number)
            ->where(function ($query) use ($request) {
                $query->where('student_mobile', $request->mobile_number)
                    ->orWhere('student_mobile', 'LIKE', '%' . substr($request->mobile_number, -10))
                    ->orWhere('father_mobile', $request->mobile_number)
                    ->orWhere('father_mobile', 'LIKE', '%' . substr($request->mobile_number, -10));
            })
            ->first();

        if (!$student) {
            // Log failed login attempt
            \App\Models\StudentPortalActivityLog::logActivity(null, 'login_failed', [
                'enrollment_number' => $request->enrollment_number,
                'mobile_number' => $request->mobile_number,
                'reason' => 'Invalid credentials'
            ]);
            return back()->withErrors(['error' => 'Invalid Enrollment Number or Mobile Number.']);
        }

        // Authentication Successful
        session()->forget($key);
        session()->put('student_portal_auth', $student->id);
        session()->put('student_portal_mobile', $request->mobile_number); // Store for activity logging
        session()->regenerate(); // Prevent session fixation

        // Log successful login
        \App\Models\StudentPortalActivityLog::logActivity($student->id, 'login_success', [
            'enrollment_number' => $request->enrollment_number
        ]);

        return redirect()->route('student.dashboard');
    }

    /**
     * Show the dashboard.
     */
    public function dashboard()
    {
        if (!session()->has('student_portal_auth')) {
            return redirect()->route('student.login');
        }

        $studentId = session('student_portal_auth');
        $student = Student::with(['admission', 'batch.course'])->find($studentId);

        if (!$student) {
            session()->forget('student_portal_auth');
            return redirect()->route('student.login')->withErrors(['error' => 'Student record not found.']);
        }

        // Log dashboard view
        \App\Models\StudentPortalActivityLog::logActivity($student->id, 'dashboard_view');

        // Fetch Pending Requests
        $pendingRequests = DB::table('student_profile_requests')
            ->where('student_id', $student->id)
            ->where('status', 'pending')
            ->get()
            ->keyBy('field_group');

        // Calculate Profile Completeness
        $completeness = $this->calculateCompleteness($student);

        return view('public.student-portal.dashboard', compact('student', 'pendingRequests', 'completeness'));
    }

    /**
     * Handle update requests.
     */
    public function requestUpdate(Request $request)
    {
        if (!session()->has('student_portal_auth')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $studentId = session('student_portal_auth');
        $fieldGroup = $request->field_group; // 'address', 'photo', 'personal'

        // Validation based on group
        $rules = [
            'field_group' => 'required|in:address,photo,personal,dob',
        ];

        if ($fieldGroup === 'photo') {
            $rules['photo'] = 'required|image|mimes:jpeg,png,jpg'; // Size check handled manually
        } elseif ($fieldGroup === 'address') {
            $rules['address'] = 'required|string|min:10';
        } elseif ($fieldGroup === 'personal') {
            $rules['mobile_number'] = 'required|digits:10';
            $rules['mobile_type'] = 'required|in:student,father';
        } elseif ($fieldGroup === 'dob') {
            $rules['dob'] = 'required|date|before:today';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Rate Limiting for Updates
        $updateKey = 'update_requests_' . $studentId;
        if (session()->get($updateKey, 0) > 3) {
            return response()->json(['error' => 'You have submitted too many requests recently. Please wait.'], 429);
        }
        session()->put($updateKey, session()->get($updateKey, 0) + 1);

        try {
            $newData = [];
            $proofFile = null;

            if ($fieldGroup === 'photo') {
                // Handle Image Upload with Compression
                $file = $request->file('photo');

                try {
                    // Compress Image to < 500KB
                    $compressedImage = $this->compressImage($file, 500);
                    $filename = 'temp_' . \Illuminate\Support\Str::random(40) . '.jpg'; // Convert to JPG

                    // Store in private/temp_uploads
                    Storage::put('private/temp_uploads/' . $filename, $compressedImage);

                    $path = 'private/temp_uploads/' . $filename;
                    $newData = ['photo_preview' => $filename];
                    $proofFile = $path;

                } catch (\Exception $e) {
                    return response()->json(['error' => 'Image compression failed: ' . $e->getMessage()], 500);
                }

            } elseif ($fieldGroup === 'address') {
                $newData = ['address' => $request->address];
            } elseif ($fieldGroup === 'personal') {
                $newData = [
                    'type' => $request->mobile_type, // 'student' or 'father'
                    'mobile' => $request->mobile_number
                ];
            } elseif ($fieldGroup === 'dob') {
                $newData = ['dob' => $request->dob];
            }

            // Store Request
            DB::table('student_profile_requests')->insert([
                'student_id' => $studentId,
                'field_group' => $fieldGroup,
                'new_data' => json_encode($newData),
                'proof_file' => $proofFile,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Log profile update request
            \App\Models\StudentPortalActivityLog::logActivity($studentId, 'profile_update_request', [
                'field_group' => $fieldGroup
            ]);

            return response()->json(['message' => 'Request submitted for approval.']);

        } catch (\Exception $e) {
            Log::error("Student Profile Update Request Error: " . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    /**
     * Helper: Compress Image using GD
     */
    private function compressImage($file, $maxSizeKb)
    {
        $sourcePath = $file->getPathname();
        list($width, $height, $type) = getimagesize($sourcePath);

        $image = null;
        if ($type == IMAGETYPE_JPEG)
            $image = imagecreatefromjpeg($sourcePath);
        elseif ($type == IMAGETYPE_PNG)
            $image = imagecreatefrompng($sourcePath);

        if (!$image)
            throw new \Exception("Unsupported image type");

        // Start compression loop
        $quality = 90;
        $output = '';

        ob_start();
        imagejpeg($image, null, $quality);
        $output = ob_get_clean();

        while (strlen($output) > ($maxSizeKb * 1024) && $quality > 10) {
            $quality -= 5;
            ob_start();
            imagejpeg($image, null, $quality);
            $output = ob_get_clean();
        }

        imagedestroy($image);
        return $output;
    }

    /**
     * AJAX Endpoint: Get Payment Data
     */
    public function getPaymentData()
    {
        if (!session()->has('student_portal_auth'))
            return response()->json(['error' => 'Unauthorized'], 401);

        $student = Student::find(session('student_portal_auth'));

        // Get all payments for this student
        $allPayments = Payment::where('student_id', $student->id)
            ->withoutGlobalScope('academic_year')
            ->with([
                'componentItems.studentFee' => function ($q) {
                    $q->withoutGlobalScope('academic_year');
                },
                'componentItems.studentFee.feeCategory'
            ])
            ->orderBy('payment_date', 'desc')
            ->get();

        $pending = [];
        $history = [];

        // Get payment history from actual payments
        foreach ($allPayments as $index => $payment) {
            if (app()->environment('local')) {
                Log::debug("Processing Payment #{$index} (ID: {$payment->id})");
                if ($payment->componentItems->isEmpty()) {
                    Log::debug("Payment {$payment->id} has no component items.");
                }
            }

            foreach ($payment->componentItems as $item) {
                $catName = $item->studentFee->feeCategory->name ?? 'Unknown Category';
                if (!$item->studentFee) {
                    if (app()->environment('local')) {
                        Log::debug("Payment Item ID {$item->id} has no StudentFee linked!");
                    }
                }

                $history[] = [
                    'id' => $payment->id,
                    'category' => $catName,
                    'amount' => $item->amount_paid,
                    'payment_date' => $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M, Y') : '-',
                    'receipt_number' => $payment->receipt_number,
                    'status' => 'Paid'
                ];
            }
        }

        // Get pending fees
        $fees = $student->studentFees()
            ->withoutGlobalScope('academic_year')
            ->with('feeCategory')
            ->where('status', '!=', 'paid')
            ->get();

        foreach ($fees as $fee) {
            $pending[] = [
                'id' => $fee->id,
                'category' => $fee->feeCategory->name ?? 'Fee',
                'amount' => $fee->amount,
                'status' => ucfirst($fee->status),
                'paid_amount' => $fee->paid_amount,
                'balance' => $fee->amount - $fee->paid_amount - $fee->concession_amount
            ];
        }

        return response()->json([
            'pending' => $pending,
            'history' => $history
        ]);
    }

    /**
     * AJAX Endpoint: Get Attendance Data
     */
    public function getAttendanceData(Request $request)
    {
        if (!session()->has('student_portal_auth'))
            return response()->json(['error' => 'Unauthorized'], 401);
        $student = Student::find(session('student_portal_auth'));

        // Determine Month & Year
        if ($request->has('month') && $request->has('year')) {
            $month = (int) $request->month;
            $year = (int) $request->year;
            $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endOfMonth = $startOfMonth->copy()->endOfMonth();
        } else {
            // ALWAYS default to current month as requested
            $startOfMonth = now()->startOfMonth();
            $endOfMonth = now()->endOfMonth();
        }

        // Fetch Attendance Records
        // Bypassing Academic Year scope to ensure we see records from all time when navigating
        $attendanceRecords = $student->attendances()
            ->withoutGlobalScope('academic_year')
            ->whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
            ->get();

        // Calculate Stats
        $totalDaysInMonth = $startOfMonth->daysInMonth;

        // Holidays Calculation
        // Assuming holidays are stored as single dates in 'holidays' table
        $holidaysCount = Holiday::whereBetween('date', [$startOfMonth, $endOfMonth])->count();

        // Weekends (assuming standard Sat/Sun, adjust if needed for institution)
        // Simplified: Count exact weekend days in the month
        $weekendsCount = 0;
        $tempDate = $startOfMonth->copy();
        while ($tempDate->lte($endOfMonth)) {
            if ($tempDate->isSunday()) { // Assuming only Sunday is off, change to isWeekend() for Sat+Sun
                $weekendsCount++;
            }
            $tempDate->addDay();
        }

        // Working Days = Total Days - Holidays - Weekends
        $workingDays = $totalDaysInMonth - $holidaysCount - $weekendsCount;

        $recordsByDate = $attendanceRecords
            ->keyBy(function ($record) {
                return Carbon::parse($record->attendance_date)->format('Y-m-d');
            });

        // 1. Calculate Daily Punch Counts for "Low Attendance" logic (Institution-wide)
        // We count distinct students per day to check if attendance is < 10
        $dailyCounts = Cache::remember(
            'attendance_daily_counts_' . $startOfMonth->format('Y-m'),
            300,
            function () use ($startOfMonth, $endOfMonth) {
                return DB::table('attendances')
                    ->whereBetween('attendance_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                    ->selectRaw('DATE(attendance_date) as date, count(distinct student_id) as count')
                    ->groupBy('date')
                    ->pluck('count', 'date')
                    ->toArray();
            }
        );

        // 2. Determine Effective Start Dates & Settings
        $today = now()->startOfDay();
        $admissionDate = $student->admission_date ? Carbon::parse($student->admission_date)->startOfDay() : $student->created_at->startOfDay();

        // Check first biometric use to avoid marking absent before they ever started using the system
        $firstBiometricUse = $student->attendances()
            ->withoutGlobalScope('academic_year')
            ->whereIn('status', ['present', 'late'])
            ->orderBy('attendance_date', 'asc')
            ->value('attendance_date');

        $biometricStartDate = $firstBiometricUse ? Carbon::parse($firstBiometricUse)->startOfDay() : null;

        // Internship Logic
        $isOnInternship = $student->batch && $student->batch->is_on_internship;
        $internshipStartDate = ($isOnInternship && $student->batch->internship_start_date)
            ? Carbon::parse($student->batch->internship_start_date)->startOfDay()
            : null;

        // 3. Fetch explicit holidays
        $dbHolidays = Holiday::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->pluck('name', 'date')
            ->toArray();

        // 4. Build Calendar Data Day-by-Day (Matching Admin Logic)
        $calendarData = [];
        $tempDate = $startOfMonth->copy();

        while ($tempDate->lte($endOfMonth)) {
            $dateStr = $tempDate->format('Y-m-d');

            // Basic Checks
            $isFuture = $tempDate->gt($today);
            $isPast = $tempDate->lt($today);
            $isWeekend = $tempDate->isSunday();
            $isExplicitHoliday = isset($dbHolidays[$dateStr]);

            // "Low Attendance" Holiday Logic
            $isLowAttendanceHoliday = false;
            // If it is a working day (not weekend, not declared holiday) and punches < 10, mark as holiday
            if (!$isFuture && !$isWeekend && !$isExplicitHoliday) {
                $dayPunchCount = $dailyCounts[$dateStr] ?? 0;
                if ($dayPunchCount < 10) {
                    $isLowAttendanceHoliday = true;
                }
            }

            $isEffectiveHoliday = $isExplicitHoliday || $isLowAttendanceHoliday;

            // "Not Started" Logic
            $isBeforeProfile = $tempDate->lt($admissionDate);
            // Ignore absent if before profile OR before first ever biometric use (if never punched)
            $shouldIgnoreForAbsent = $isBeforeProfile || (is_null($firstBiometricUse) && $tempDate->lt($today));

            // Default Status
            $status = 'none';

            // Check for explicit attendance record first
            $record = $recordsByDate->get($dateStr);

            if ($record) {
                $status = strtolower($record->status ?? '');
            } else {
                // No Record Logic
                if ($shouldIgnoreForAbsent) {
                    $status = 'none';
                } elseif ($isFuture) {
                    $status = 'none';
                } elseif ($isWeekend) {
                    $status = 'weekend'; // Show Sunday as distinct weekend color
                } elseif ($isEffectiveHoliday) {
                    $status = 'holiday';
                } else {
                    // Working Day (Past/Today) logic
                    if ($isPast) {
                        // Check Internship
                        $isInternshipDay = $isOnInternship;
                        if ($isOnInternship && $internshipStartDate) {
                            $isInternshipDay = $tempDate->gte($internshipStartDate);
                        }

                        if ($isInternshipDay) {
                            $status = 'internship';
                        } else {
                            $status = 'absent';
                        }
                    } else {
                        $status = 'none'; // Today pending
                    }
                }
            }

            // Populate Map
            if ($status !== 'none') {
                $calendarData[$dateStr] = $status;
            }

            $tempDate->addDay();
        }

        // Recalculate Stats based on the final calendar map
        $finalStatuses = collect($calendarData);
        $presentDays = $finalStatuses->filter(fn($s) => in_array($s, ['present', 'late', 'internship']))->count();
        $absentDays = $finalStatuses->filter(fn($s) => $s === 'absent')->count();
        $lateDays = $finalStatuses->filter(fn($s) => $s === 'late')->count();
        $totalHolidays = $finalStatuses->filter(fn($s) => in_array($s, ['holiday', 'weekend']))->count();

        // Working Days (effective)
        $workingDays = $presentDays + $absentDays;

        // Calculate percentage
        $percentage = 0;
        $totalForCalc = $presentDays + $absentDays;
        if ($totalForCalc > 0) {
            $percentage = round(($presentDays / $totalForCalc) * 100, 1);
        }

        // Get available months to populate dropdown - Bypass Academic Year Scope
        $availableMonths = $student->attendances()
            ->withoutGlobalScope('academic_year')
            ->select(DB::raw('DATE_FORMAT(attendance_date, "%Y-%m") as month_str'), DB::raw('YEAR(attendance_date) as year'), DB::raw('MONTH(attendance_date) as month'))
            ->distinct()
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'label' => Carbon::createFromDate($item->year, $item->month, 1)->format('F Y'),
                    'value' => $item->month_str // YYYY-MM
                ];
            });

        return response()->json([
            'stats' => [ // Still return stats for summary
                'total_working_days' => $workingDays,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'holidays' => $totalHolidays,
                'percentage' => $percentage
            ],
            'calendar' => $calendarData,
            'month_name' => $startOfMonth->format('F Y'),
            'first_date' => $startOfMonth->format('Y-m-d'),
            'current_month' => $startOfMonth->month,
            'current_year' => $startOfMonth->year,
            'available_months' => $availableMonths
        ]);
    }

    public function logout()
    {
        $studentId = session('student_portal_auth');

        // Log logout
        if ($studentId) {
            \App\Models\StudentPortalActivityLog::logActivity($studentId, 'logout');
        }

        session()->forget('student_portal_auth');
        session()->forget('student_portal_mobile');
        return redirect()->route('student.login');
    }

    private function calculateCompleteness($student)
    {
        $fields = [
            'name' => ['points' => 20, 'label' => 'Name'],
            'student_mobile' => ['points' => 25, 'label' => 'Mobile Number'],
            'father_mobile' => ['points' => 20, 'label' => "Father's Mobile"],
            'photo' => ['points' => 20, 'label' => 'Profile Photo'],
            'dob' => ['points' => 10, 'label' => 'Date of Birth'],
            'gender' => ['points' => 5, 'label' => 'Gender']
        ];

        $score = 0;
        $missing = [];

        foreach ($fields as $field => $config) {
            $hasValue = false;

            if ($field === 'address') {
                $hasValue = $student->admission && $student->admission->address;
            } else {
                $hasValue = !empty($student->$field);
            }

            if ($hasValue) {
                $score += $config['points'];
            } else {
                $missing[] = $config['label'];
            }
        }

        return [
            'percentage' => $score,
            'missing' => $missing
        ];
    }
}
