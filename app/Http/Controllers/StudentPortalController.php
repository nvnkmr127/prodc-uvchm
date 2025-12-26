<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Admission;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

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
        return view('public.student-portal.login');
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
        $allPayments = $student->payments()->with('componentItems.studentFee.feeCategory')->orderBy('payment_date', 'desc')->get();

        $pending = [];
        $history = [];

        // Get payment history from actual payments
        foreach ($allPayments as $payment) {
            foreach ($payment->componentItems as $item) {
                $history[] = [
                    'id' => $payment->id,
                    'category' => $item->studentFee->feeCategory->name ?? 'Fee',
                    'amount' => $item->amount_paid,
                    'payment_date' => $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M, Y') : '-',
                    'receipt_number' => $payment->receipt_number,
                    'status' => 'Paid'
                ];
            }
        }

        // Get pending fees
        $fees = $student->studentFees()->with('feeCategory')->where('status', '!=', 'paid')->get();
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
    public function getAttendanceData()
    {
        if (!session()->has('student_portal_auth'))
            return response()->json(['error' => 'Unauthorized'], 401);
        $student = Student::find(session('student_portal_auth'));

        // Fetch attendance for current month
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $attendanceRecords = $student->attendances()
            ->whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
            ->get();

        // Map dates to status for Calendar
        // Format: '2023-10-01' => 'present'
        $calendarData = [];
        foreach ($attendanceRecords as $record) {
            $dateStr = \Carbon\Carbon::parse($record->attendance_date)->format('Y-m-d');
            $calendarData[$dateStr] = $record->status;
        }

        return response()->json([
            'stats' => [ // Still return stats for summary
                'total_days' => $attendanceRecords->count(),
                'present_days' => $attendanceRecords->where('status', 'present')->count(),
                'percentage' => $student->getAttendancePercentage($startOfMonth, $endOfMonth)
            ],
            'calendar' => $calendarData
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
