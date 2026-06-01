<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Timetable;
use App\Models\User; // Added missing import
use App\Services\FacultyBiometricMappingService;
use App\Services\SecureFileValidator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;

class FacultyController extends Controller
{
    protected $biometricMappingService;

    public function __construct(FacultyBiometricMappingService $biometricMappingService)
    {
        $this->biometricMappingService = $biometricMappingService;
    }
    /**
     * Display a listing of faculty members
     */
    public function index()
    {
        try {
            $faculties = User::role('staff')
                ->with(['subjects', 'leaveBalances']) // Eager load relationships
                ->orderBy('name')
                ->get();

            return view('admin.faculty.index', compact('faculties'));

        } catch (\Exception $e) {
            Log::error('Error loading faculty index', [
                'error' => $e->getMessage(),
            ]);

            return view('admin.faculty.index', ['faculties' => collect()]);
        }
    }

    /**
     * Show the form for creating a new faculty member
     */
    public function create()
    {
        return view('admin.faculty.create');
    }

    /**
     * Store a newly created faculty member
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'department' => ['nullable', 'string', 'max:100'],
            'employee_id' => ['nullable', 'string', 'max:50', 'unique:users,employee_id'],
            'biometric_employee_code' => ['nullable', 'string', 'max:50', 'unique:users,biometric_employee_code'],
        ]);

        DB::beginTransaction();

        try {
            // Create the user - only include fields that exist in database
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(), // Auto-verify faculty emails
            ];

            // Add optional fields only if they exist in database schema
            if ($request->phone) {
                $userData['phone'] = $request->phone;
            }
            if ($request->department) {
                $userData['department'] = $request->department;
            }
            if ($request->employee_id) {
                $userData['employee_id'] = $request->employee_id;
            }
            if ($request->biometric_employee_code) {
                $userData['biometric_employee_code'] = $request->biometric_employee_code;
            }

            $user = User::create($userData);

            // Assign the staff role
            $user->assignRole('staff');

            // Automatically generate and assign biometric code if not provided
            if (empty($user->biometric_employee_code)) {
                $this->biometricMappingService->assignBiometricCode($user);
            }

            // Create leave balances for the new faculty member (only if LeaveType exists)
            if (class_exists(LeaveType::class)) {
                $this->createInitialLeaveBalances($user);
            }

            DB::commit();

            Log::info('Faculty member created successfully', [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_by' => auth()->id(),
            ]);

            return redirect()->route('admin.faculty.index')
                ->with('success', 'Faculty member created successfully and initial leave balances have been set.');

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error creating faculty member', [
                'error' => $e->getMessage(),
                'request_data' => $request->except('password', 'password_confirmation'),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to create faculty member. Please try again.')
                ->withInput($request->except('password', 'password_confirmation'));
        }
    }

    /**
     * Display the specified faculty member
     */
    public function show(User $faculty)
    {
        if (! $faculty->hasRole('staff')) {
            abort(404, 'Faculty member not found');
        }

        // Load relationships only if they exist
        $relationships = ['subjects'];

        if (method_exists($faculty, 'leaveBalances')) {
            $relationships[] = 'leaveBalances.leaveType';
        }

        if (method_exists($faculty, 'leaveApplications')) {
            $relationships['leaveApplications'] = function ($query) {
                $query->latest()->limit(5);
            };
        }

        $faculty->load($relationships);

        return view('admin.faculty.show', compact('faculty'));
    }

    /**
     * Show the form for editing the specified faculty member
     */
    public function edit(User $faculty)
    {
        if (! $faculty->hasRole('staff')) {
            abort(404, 'Faculty member not found');
        }

        return view('admin.faculty.edit', compact('faculty'));
    }

    /**
     * Update the specified faculty member
     */
    public function update(Request $request, User $faculty)
    {
        if (! $faculty->hasRole('staff')) {
            abort(404, 'Faculty member not found');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$faculty->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'department' => ['nullable', 'string', 'max:100'],
            'employee_id' => ['nullable', 'string', 'max:50', 'unique:users,employee_id,'.$faculty->id],
            'biometric_employee_code' => ['nullable', 'string', 'max:50', 'unique:users,biometric_employee_code,'.$faculty->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
            ];

            // Add optional fields only if they exist in database schema
            if ($request->has('phone')) {
                $updateData['phone'] = $request->phone;
            }
            if ($request->has('department')) {
                $updateData['department'] = $request->department;
            }
            if ($request->has('employee_id')) {
                $updateData['employee_id'] = $request->employee_id;
            }
            if ($request->has('biometric_employee_code')) {
                $updateData['biometric_employee_code'] = $request->biometric_employee_code;
            }

            // Only update password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $faculty->update($updateData);

            Log::info('Faculty member updated', [
                'user_id' => $faculty->id,
                'updated_by' => auth()->id(),
                'changes' => $faculty->getChanges(),
            ]);

            return redirect()->route('admin.faculty.index')
                ->with('success', 'Faculty member updated successfully.');

        } catch (\Exception $e) {
            Log::error('Error updating faculty member', [
                'user_id' => $faculty->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update faculty member.')
                ->withInput();
        }
    }

    /**
     * Remove the specified faculty member
     */
    public function destroy(User $faculty)
    {
        if (! $faculty->hasRole('staff')) {
            abort(404, 'Faculty member not found');
        }

        try {
            DB::beginTransaction();

            // Check if faculty has any active assignments
            $hasActiveAssignments = $faculty->subjects()->exists();

            // Check timetable entries only if the relationship exists
            if (method_exists($faculty, 'timetableEntries') || method_exists($faculty, 'timetables')) {
                $timetableMethod = method_exists($faculty, 'timetableEntries') ? 'timetableEntries' : 'timetables';
                $hasActiveAssignments = $hasActiveAssignments || $faculty->{$timetableMethod}()->exists();
            }

            if ($hasActiveAssignments) {
                return redirect()->back()
                    ->with('error', 'Cannot delete faculty member with active subject assignments or timetable entries.');
            }

            $facultyName = $faculty->name;

            // Remove role before deleting (optional, but cleaner)
            $faculty->removeRole('staff');

            $faculty->delete();

            DB::commit();

            Log::info('Faculty member deleted', [
                'deleted_user_name' => $facultyName,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()->route('admin.faculty.index')
                ->with('success', "Faculty member {$facultyName} deleted successfully.");

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error deleting faculty member', [
                'user_id' => $faculty->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete faculty member.');
        }
    }

    /**
     * Create initial leave balances for a new faculty member
     */
    private function createInitialLeaveBalances(User $user)
    {
        try {
            $leaveTypes = LeaveType::all();
            $currentYear = Carbon::now()->year;

            foreach ($leaveTypes as $type) {
                LeaveBalance::create([
                    'user_id' => $user->id,
                    'leave_type_id' => $type->id,
                    'remaining_days' => $type->days_per_year,
                    'year' => $currentYear,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to create initial leave balances', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw exception - faculty creation should still succeed
        }
    }

    /**
     * Show biometric mapping interface for faculty
     */
    public function biometricMapping(Request $request)
    {
        // Get basic statistics
        $statsData = User::role('staff')->where('status', 'active')
            ->selectRaw('count(*) as total, count(biometric_employee_code) as mapped')
            ->first();

        $totalFaculty = $statsData->total ?? 0;
        $mappedFaculty = $statsData->mapped ?? 0;
        $unmappedFaculty = $totalFaculty - $mappedFaculty;
        $mappingPercentage = $totalFaculty > 0 ? round(($mappedFaculty / $totalFaculty) * 100, 2) : 0;

        $stats = [
            'total_faculty' => $totalFaculty,
            'mapped_faculty' => $mappedFaculty,
            'unmapped_faculty' => $unmappedFaculty,
            'mapping_percentage' => $mappingPercentage,
        ];

        // Start query for faculty
        $query = User::role('staff')->where('status', 'active');

        // Apply search filter if provided
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('biometric_employee_code', 'like', "%{$search}%");
            });
        }

        // Use pagination
        $facultiesFetch = $query->paginate(100)->withQueryString();

        $faculties = $facultiesFetch->map(function ($faculty) {
            return [
                'id' => $faculty->id,
                'name' => $faculty->name,
                'employee_id' => $faculty->employee_id,
                'biometric_code' => $faculty->biometric_employee_code,
                'department' => $faculty->department ?? 'No Department',
                'phone' => $faculty->phone ?? 'No Phone',
                'suggested_code' => $this->biometricMappingService->generateBiometricCodeFromEmployeeId($faculty->employee_id ?? ''),
            ];
        });

        return view('admin.faculty.biometric-mapping', compact('stats', 'faculties', 'facultiesFetch'));
    }

    /**
     * Bulk update biometric codes via AJAX
     */
    public function bulkUpdateBiometricMapping(Request $request)
    {
        Log::info('Bulk faculty biometric update started', [
            'request_data' => $request->all(),
            'mappings_count' => count($request->input('mappings', [])),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
        ]);

        $request->validate([
            'mappings' => 'required|array',
            'mappings.*.faculty_id' => 'required|integer|exists:users,id',
            'mappings.*.biometric_code' => 'nullable|string|max:50|regex:/^[a-zA-Z0-9\-]*$/',
        ]);

        try {
            Log::info('Validation passed, calling faculty biometric mapping service', [
                'mappings' => $request->mappings,
            ]);

            $results = $this->biometricMappingService->bulkUpdateCodes($request->mappings);

            Log::info('Bulk faculty biometric update completed', [
                'results' => $results,
                'success_count' => $results['success_count'],
                'error_count' => $results['error_count'],
            ]);

            return response()->json([
                'success' => true,
                'message' => "Updated {$results['success_count']} faculty members successfully".
                    ($results['error_count'] > 0 ? " with {$results['error_count']} errors" : ''),
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk faculty biometric update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update biometric codes: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import biometric mappings from Excel/CSV
     */
    public function importBiometricMapping(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120',
        ]);

        $fileValidator = new SecureFileValidator;
        $validationResult = $fileValidator->validateFile($request->file('file'), ['xlsx', 'xls', 'csv']);

        if (! $validationResult['valid']) {
            return back()->with('error', $validationResult['error']);
        }

        try {
            $results = $this->biometricMappingService->importBiometricMappings($request->file('file'));

            if ($results['success']) {
                $message = "Successfully imported {$results['imported_count']} biometric codes";
                if (! empty($results['errors'])) {
                    $message .= ' with '.count($results['errors']).' errors';
                }

                return back()->with('success', $message)
                    ->with('import_errors', $results['errors'] ?? []);
            } else {
                return back()->with('error', 'Import failed: '.$results['error']);
            }

        } catch (\Exception $e) {
            Log::error('Faculty biometric import failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Import failed: '.$e->getMessage());
        }
    }

    /**
     * Export unmapped faculty to Excel
     */
    public function exportBiometricMapping()
    {
        try {
            return $this->biometricMappingService->exportUnmappedFaculty();
        } catch (\Exception $e) {
            Log::error('Export unmapped faculty failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Export failed: '.$e->getMessage());
        }
    }

    /**
     * Download sample biometric mapping file
     */
    public function downloadBiometricMappingSample()
    {
        return $this->biometricMappingService->exportUnmappedFaculty();
    }

    /**
     * Auto-generate biometric codes for all unmapped faculty
     */
    public function autoGenerateBiometricMapping()
    {
        try {
            $results = $this->biometricMappingService->autoGenerateAllCodes();

            $message = "Auto-generated {$results['success_count']} biometric codes";
            if ($results['error_count'] > 0) {
                $message .= " with {$results['error_count']} errors";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('Auto-generate faculty biometric codes failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to auto-generate codes: '.$e->getMessage(),
            ], 500);
        }
    }
}
