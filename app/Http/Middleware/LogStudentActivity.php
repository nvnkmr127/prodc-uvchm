<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LogStudentActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Log student page visits
        if ($request->route('student') && auth()->check()) {
            $student = $request->route('student');

            // Only log meaningful visits (not AJAX requests)
            if (! $request->ajax() && ! $request->wantsJson()) {
                $this->logStudentVisit($student, $request);
            }
        }

        return $response;
    }

    private function logStudentVisit($student, Request $request)
    {
        $routeName = $request->route()->getName();
        $action = $this->getActionFromRoute($routeName);

        if ($action) {
            $user = auth()->user();
            
            // Debounce page visits to once per hour per user to prevent DB bloat
            if ($action === 'profile' || $action === 'edit') {
                $cacheKey = "student_view_{$student->id}_by_{$user->id}_{$action}";
                
                if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                    return;
                }
                
                \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addMinutes(60));
            }

            activity()
                ->causedBy($user)
                ->performedOn($student)
                ->withProperties([
                    'route' => $routeName,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'type' => 'page_visit',
                ])
                ->log("Student {$action} page accessed");
        }
    }

    private function getActionFromRoute($routeName)
    {
        return match ($routeName) {
            'admin.students.show' => 'profile',
            'admin.students.edit' => 'edit',
            'admin.students.update' => 'update',
            default => null
        };
    }
}
