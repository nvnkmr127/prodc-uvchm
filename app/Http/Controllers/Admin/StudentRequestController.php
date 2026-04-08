<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        $requests = DB::table('student_profile_requests')
            ->join('students', 'student_profile_requests.student_id', '=', 'students.id')
            ->leftJoin('users', 'student_profile_requests.processed_by', '=', 'users.id')
            ->select(
                'student_profile_requests.*',
                'students.name as student_name',
                'students.enrollment_number',
                'students.id as student_id',
                'users.name as approved_by_name'
            )
            ->where('student_profile_requests.status', $status)
            ->orderBy('student_profile_requests.created_at', 'desc')
            ->paginate(10);

        return view('admin.student-requests.index', compact('requests', 'status'));
    }

    public function action(Request $request, $id)
    {
        $action = $request->action; // 'approve' or 'reject'
        $profileRequest = DB::table('student_profile_requests')->find($id);

        if (! $profileRequest) {
            return back()->with('error', 'Request not found.');
        }

        if ($action === 'reject') {
            DB::table('student_profile_requests')->where('id', $id)->update([
                'status' => 'rejected',
                'admin_comment' => $request->comment,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
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

                // If admission record is missing, try to find it by email and link it
                if (! $admission && $student->email) {
                    $admission = \App\Models\Admission::where('email', $student->email)->first();
                    if ($admission) {
                        $student->admission_id = $admission->id;
                        $student->save();
                    }
                }

                if ($admission) {
                    $admission->update(['address' => $newData['address']]);
                } else {
                    // Fallback: update village if no admission record found (best effort)
                    $student->update(['village' => $newData['address']]);
                }
            } elseif ($profileRequest->field_group === 'photo') {
                // Move file from private temp to public with proper naming
                if ($profileRequest->proof_file) {
                    $sourcePath = $profileRequest->proof_file;

                    // Generate a proper filename using student enrollment number
                    $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
                    $newFilename = 'student_'.$student->enrollment_number.'_'.time().'.'.$extension;
                    $destinationPath = 'student_photos/'.$newFilename;

                    if (Storage::exists($sourcePath)) {
                        // Ensure the student_photos directory exists
                        if (! Storage::exists('public/student_photos')) {
                            Storage::makeDirectory('public/student_photos');
                        }

                        // Copy file to public storage with new name
                        $fileContents = Storage::get($sourcePath);
                        Storage::put('public/'.$destinationPath, $fileContents);

                        // Delete the temporary file
                        Storage::delete($sourcePath);

                        // Update student record
                        $student->update(['photo' => $destinationPath]);
                    }
                }
            } elseif ($profileRequest->field_group === 'personal') {
                // Update mobile numbers
                $type = $newData['type'] ?? null; // 'student' or 'father'
                $mobile = $newData['mobile'] ?? null;

                if ($type === 'student' && $mobile) {
                    $student->update(['student_mobile' => $mobile]);
                } elseif ($type === 'father' && $mobile) {
                    $student->update(['father_mobile' => $mobile]);
                }
            } elseif ($profileRequest->field_group === 'dob') {
                // Update date of birth
                if (isset($newData['dob'])) {
                    $student->dob = $newData['dob'];
                    $student->save();
                    $student->refresh();
                }
            }

            DB::table('student_profile_requests')->where('id', $id)->update([
                'status' => 'approved',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            return back()->with('success', 'Request approved and data updated.');
        }

        return back();
    }

    public function preview($id)
    {
        $req = DB::table('student_profile_requests')->find($id);

        if ($req && $req->proof_file) {
            $path = storage_path('app/'.$req->proof_file);

            if (file_exists($path)) {
                return response()->file($path);
            }
        }

        abort(404, 'File not found or has been processed.');
    }
}
