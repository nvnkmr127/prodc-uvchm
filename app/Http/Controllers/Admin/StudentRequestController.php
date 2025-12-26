<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\Admission;
use Illuminate\Support\Facades\Storage;

class StudentRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        $requests = DB::table('student_profile_requests')
            ->join('students', 'student_profile_requests.student_id', '=', 'students.id')
            ->select('student_profile_requests.*', 'students.name as student_name', 'students.enrollment_number')
            ->where('student_profile_requests.status', $status)
            ->orderBy('student_profile_requests.created_at', 'desc')
            ->paginate(10);

        return view('admin.student-requests.index', compact('requests', 'status'));
    }

    public function action(Request $request, $id)
    {
        $action = $request->action; // 'approve' or 'reject'
        $profileRequest = DB::table('student_profile_requests')->find($id);

        if (!$profileRequest) {
            return back()->with('error', 'Request not found.');
        }

        if ($action === 'reject') {
            DB::table('student_profile_requests')->where('id', $id)->update([
                'status' => 'rejected',
                'admin_comment' => $request->comment,
                'processed_by' => auth()->id(),
                'processed_at' => now()
            ]);

            // Optional: Delete proof file if rejected
            if ($profileRequest->proof_file && Storage::exists($profileRequest->proof_file)) {
                Storage::delete($profileRequest->proof_file);
            }

            return back()->with('success', 'Request rejected.');
        }

        if ($action === 'approve') {
            $newData = json_decode($profileRequest->new_data, true);
            $student = Student::find($profileRequest->student_id);

            // Apply Changes
            if ($profileRequest->field_group === 'address') {
                $admission = $student->admission;
                if ($admission) {
                    $admission->update(['address' => $newData['address']]);
                }
            } elseif ($profileRequest->field_group === 'photo') {
                // Move file from private temp to public
                if ($profileRequest->proof_file) {
                    $sourcePath = $profileRequest->proof_file;
                    $filename = basename($sourcePath);
                    $destinationPath = 'student-photos/' . $filename; // Public path

                    if (Storage::exists($sourcePath)) {
                        Storage::move($sourcePath, 'public/' . $destinationPath);
                        $student->update(['photo' => $destinationPath]);
                    }
                }
            }

            DB::table('student_profile_requests')->where('id', $id)->update([
                'status' => 'approved',
                'processed_by' => auth()->id(),
                'processed_at' => now()
            ]);

            return back()->with('success', 'Request approved and data updated.');
        }

        return back();
    }
}
