<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Course;
use App\Models\User;
use App\Models\Batch;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        try {
            $searchTerm = $request->input('q');

            // Validate search term
            if (empty($searchTerm) || strlen($searchTerm) < 2) {
                return response()->json([
                    'results' => [],
                    'total' => 0,
                    'query' => $searchTerm ?? ''
                ]);
            }

            $results = [];

            // Search for students (highest priority) - FIXED with safe property access
            $students = Student::where('name', 'LIKE', "%{$searchTerm}%")
                               ->orWhere('enrollment_number', 'LIKE', "%{$searchTerm}%")
                               ->orWhere('student_mobile', 'LIKE', "%{$searchTerm}%")
                               ->orWhere('father_mobile', 'LIKE', "%{$searchTerm}%")
                               ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                               ->with('batch.course')
                               ->limit(8)
                               ->get();

            foreach ($students as $student) {
                $courseName = 'No Course';
                $batchName = 'No Batch';

                if ($student->batch) {
                    $batchName = $student->batch->name ?? 'No Batch';
                    if ($student->batch->course) {
                        $courseName = $student->batch->course->name ?? 'No Course';
                    }
                }

                $results[] = [
                    'type' => 'Student',
                    'title' => $student->name . ' (' . ($student->enrollment_number ?? 'N/A') . ')',
                    'subtitle' => $courseName . ' - ' . $batchName,
                    'url' => route('admin.students.show', $student),
                    'icon' => 'fa-user-graduate',
                    'priority' => 1
                ];
            }

            // Search for batches - FIXED with try-catch for permission checks
            try {
                $batches = Batch::where('name', 'LIKE', "%{$searchTerm}%")
                                ->with('course')
                                ->limit(5)
                                ->get();

                foreach ($batches as $batch) {
                    $results[] = [
                        'type' => 'Batch',
                        'title' => $batch->name ?? 'Unknown Batch',
                        'subtitle' => optional($batch->course)->name ?? 'No Course',
                        'url' => route('admin.batches.show', $batch),
                        'icon' => 'fa-users',
                        'priority' => 2
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning('Batch search error: ' . $e->getMessage());
            }

            // Search for courses - FIXED with try-catch
            try {
                $courses = Course::where('name', 'LIKE', "%{$searchTerm}%")
                                 ->orWhere('code', 'LIKE', "%{$searchTerm}%")
                                 ->limit(4)
                                 ->get();

                foreach ($courses as $course) {
                    $results[] = [
                        'type' => 'Course',
                        'title' => $course->name ?? 'Unknown Course',
                        'subtitle' => $course->code ? 'Code: ' . $course->code : 'Duration: ' . ($course->duration_months ?? 'N/A') . ' months',
                        'url' => route('admin.courses.show', $course),
                        'icon' => 'fa-book',
                        'priority' => 3
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning('Course search error: ' . $e->getMessage());
            }

            // Search for faculty (if user has permission) - FIXED with try-catch
            try {
                if (class_exists('Spatie\Permission\Models\Role')) {
                    $faculty = User::role('staff')
                                  ->where('name', 'LIKE', "%{$searchTerm}%")
                                  ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                                  ->limit(3)
                                  ->get();

                    foreach ($faculty as $member) {
                        $results[] = [
                            'type' => 'Faculty',
                            'title' => $member->name ?? 'Unknown',
                            'subtitle' => $member->email ?? 'Faculty Member',
                            'url' => route('admin.faculty.show', $member),
                            'icon' => 'fa-chalkboard-teacher',
                            'priority' => 4
                        ];
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Faculty search error: ' . $e->getMessage());
            }

            // Sort results by priority and limit total results
            usort($results, function($a, $b) {
                return $a['priority'] - $b['priority'];
            });

            // Limit to 15 total results for better performance
            $results = array_slice($results, 0, 15);

            return response()->json([
                'results' => $results,
                'total' => count($results),
                'query' => $searchTerm
            ]);

        } catch (\Exception $e) {
            \Log::error('Global search error: ' . $e->getMessage(), [
                'query' => $request->input('q'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'results' => [],
                'total' => 0,
                'query' => $request->input('q', ''),
                'error' => 'Search failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Enhanced search for session-based searches (alternative method)
     */
    public function sessionSearch(Request $request)
    {
        $query = $request->input('q');
        
        if (empty($query) || strlen($query) < 2) {
            return response()->json([
                'students' => [],
                'courses' => [],
                'batches' => [],
                'faculty' => []
            ]);
        }

        $results = [
            'students' => $this->searchStudents($query),
            'courses' => $this->searchCourses($query),
            'batches' => $this->searchBatches($query),
            'faculty' => $this->searchFaculty($query),
        ];

        return response()->json($results);
    }

    /**
     * Search students with detailed info
     */
    private function searchStudents($query)
    {
        return Student::where('name', 'LIKE', "%{$query}%")
                     ->orWhere('enrollment_number', 'LIKE', "%{$query}%")
                     ->orWhere('student_mobile', 'LIKE', "%{$query}%")
                     ->orWhere('email', 'LIKE', "%{$query}%")
                     ->with('batch.course')
                     ->limit(6)
                     ->get()
                     ->map(function ($student) {
                         return [
                             'id' => $student->id,
                             'name' => $student->name,
                             'enrollment_number' => $student->enrollment_number,
                             'course' => $student->batch->course->name ?? 'N/A',
                             'batch' => $student->batch->name ?? 'N/A',
                             'mobile' => $student->student_mobile ?? 'N/A',
                             'url' => route('admin.students.show', $student),
                             'type' => 'student'
                         ];
                     });
    }

    /**
     * Search courses
     */
    private function searchCourses($query)
    {
        if (!auth()->user()->can('view courses')) {
            return collect([]);
        }

        return Course::where('name', 'LIKE', "%{$query}%")
                    ->orWhere('code', 'LIKE', "%{$query}%")
                    ->limit(4)
                    ->get()
                    ->map(function ($course) {
                        return [
                            'id' => $course->id,
                            'name' => $course->name,
                            'code' => $course->code ?? 'N/A',
                            'duration' => $course->duration_months . ' months',
                            'url' => route('admin.courses.show', $course),
                            'type' => 'course'
                        ];
                    });
    }

    /**
     * Search batches
     */
    private function searchBatches($query)
    {
        if (!auth()->user()->can('view batches')) {
            return collect([]);
        }

        return Batch::where('name', 'LIKE', "%{$query}%")
                   ->with('course')
                   ->limit(4)
                   ->get()
                   ->map(function ($batch) {
                       return [
                           'id' => $batch->id,
                           'name' => $batch->name,
                           'course' => $batch->course->name ?? 'N/A',
                           'students_count' => $batch->students()->count(),
                           'url' => route('admin.batches.show', $batch),
                           'type' => 'batch'
                       ];
                   });
    }

    /**
     * Search faculty
     */
    private function searchFaculty($query)
    {
        if (!auth()->user()->can('view faculty')) {
            return collect([]);
        }

        return User::role('staff')
                  ->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%")
                  ->limit(3)
                  ->get()
                  ->map(function ($member) {
                      return [
                          'id' => $member->id,
                          'name' => $member->name,
                          'email' => $member->email,
                          'url' => route('admin.faculty.show', $member),
                          'type' => 'faculty'
                      ];
                  });
    }
}