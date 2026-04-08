<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TimeSlotController extends Controller
{
    /**
     * Display a listing of time slots
     */
    public function index(Request $request)
    {
        // Handle AJAX requests for existing slots (for the enhanced form)
        if ($request->has('ajax')) {
            $timeSlots = TimeSlot::select('id', 'name', 'start_time', 'end_time', 'duration', 'is_active')
                ->orderBy('sort_order')
                ->orderBy('start_time')
                ->get()
                ->map(function ($slot) {
                    return [
                        'id' => $slot->id,
                        'name' => $slot->name ?: ($slot->start_time.' - '.$slot->end_time),
                        'start_time' => date('H:i', strtotime($slot->start_time)),
                        'end_time' => date('H:i', strtotime($slot->end_time)),
                        'duration' => $this->formatDuration($slot->duration),
                        'is_active' => $slot->is_active,
                    ];
                });

            return response()->json(['timeSlots' => $timeSlots]);
        }

        // Handle conflict checking (for the enhanced form)
        if ($request->has('check_conflict')) {
            $startTime = $request->start;
            $endTime = $request->end;

            $conflict = TimeSlot::where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime) {
                    // Check if new slot starts during existing slot
                    $q->where('start_time', '<=', $startTime)
                        ->where('end_time', '>', $startTime);
                })->orWhere(function ($q) use ($endTime) {
                    // Check if new slot ends during existing slot
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>=', $endTime);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    // Check if new slot completely contains existing slot
                    $q->where('start_time', '>=', $startTime)
                        ->where('end_time', '<=', $endTime);
                });
            })->first();

            if ($conflict) {
                $conflictName = $conflict->name ?: ($conflict->start_time.' - '.$conflict->end_time);

                return response()->json([
                    'conflict' => true,
                    'message' => "This time overlaps with existing slot: {$conflictName}",
                ]);
            }

            return response()->json(['conflict' => false]);
        }

        // Regular index view
        $time_slots = TimeSlot::orderBy('sort_order')->orderBy('start_time')->get();

        return view('admin.time_slots.index', compact('time_slots'));
    }

    /**
     * Show the form for creating a new time slot
     */
    public function create()
    {
        return view('admin.time_slots.create');
    }

    /**
     * Store a newly created time slot
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:50',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'sort_order' => 'nullable|integer|min:0|max:100',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:500',
        ]);

        // Calculate duration in minutes
        $start = Carbon::createFromFormat('H:i', $validated['start_time']);
        $end = Carbon::createFromFormat('H:i', $validated['end_time']);
        $duration = $end->diffInMinutes($start);

        // Set defaults
        $validated['duration'] = $duration;
        $validated['is_active'] = $request->has('is_active') ? true : false;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        // Auto-generate name if not provided
        if (empty($validated['name'])) {
            $validated['name'] = $validated['start_time'].' - '.$validated['end_time'];
        }

        // Check for conflicts before saving
        $conflict = TimeSlot::where(function ($query) use ($validated) {
            $startTime = $validated['start_time'].':00';
            $endTime = $validated['end_time'].':00';

            $query->where(function ($q) use ($startTime) {
                $q->where('start_time', '<=', $startTime)
                    ->where('end_time', '>', $startTime);
            })->orWhere(function ($q) use ($endTime) {
                $q->where('start_time', '<', $endTime)
                    ->where('end_time', '>=', $endTime);
            })->orWhere(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '>=', $startTime)
                    ->where('end_time', '<=', $endTime);
            });
        })->first();

        if ($conflict) {
            $conflictName = $conflict->name ?: ($conflict->start_time.' - '.$conflict->end_time);

            return redirect()->back()
                ->withInput()
                ->with('error', "Time slot conflicts with existing slot: {$conflictName}");
        }

        TimeSlot::create($validated);

        $message = 'Time slot created successfully!';

        // Handle "add another" functionality (from enhanced form)
        if ($request->has('add_another')) {
            return redirect()->route('admin.time-slots.create')
                ->with('success', $message.' Add another one below.');
        }

        return redirect()->route('admin.time-slots.index')
            ->with('success', $message);
    }

    /**
     * Show the form for editing a time slot
     */
    public function edit(TimeSlot $time_slot)
    {
        return view('admin.time_slots.edit', compact('time_slot'));
    }

    /**
     * Update the specified time slot
     */
    public function update(Request $request, TimeSlot $time_slot)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:50',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'sort_order' => 'nullable|integer|min:0|max:100',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:500',
        ]);

        // Calculate duration in minutes
        $start = Carbon::createFromFormat('H:i', $validated['start_time']);
        $end = Carbon::createFromFormat('H:i', $validated['end_time']);
        $duration = $end->diffInMinutes($start);

        // Set values
        $validated['duration'] = $duration;
        $validated['is_active'] = $request->has('is_active') ? true : false;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        // Auto-generate name if not provided
        if (empty($validated['name'])) {
            $validated['name'] = $validated['start_time'].' - '.$validated['end_time'];
        }

        // Check for conflicts before saving (excluding current slot)
        $conflict = TimeSlot::where('id', '!=', $time_slot->id)
            ->where(function ($query) use ($validated) {
                $startTime = $validated['start_time'].':00';
                $endTime = $validated['end_time'].':00';

                $query->where(function ($q) use ($startTime) {
                    $q->where('start_time', '<=', $startTime)
                        ->where('end_time', '>', $startTime);
                })->orWhere(function ($q) use ($endTime) {
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>=', $endTime);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '>=', $startTime)
                        ->where('end_time', '<=', $endTime);
                });
            })->first();

        if ($conflict) {
            $conflictName = $conflict->name ?: ($conflict->start_time.' - '.$conflict->end_time);

            return redirect()->back()
                ->withInput()
                ->with('error', "Time slot conflicts with existing slot: {$conflictName}");
        }

        $time_slot->update($validated);

        return redirect()->route('admin.time-slots.index')
            ->with('success', 'Time slot updated successfully.');
    }

    /**
     * Remove the specified time slot
     */
    public function destroy(TimeSlot $time_slot)
    {
        // Check if time slot is being used in timetables
        $usageCount = $time_slot->timetables()->count();

        if ($usageCount > 0) {
            return redirect()->route('admin.time-slots.index')
                ->with('error', "Cannot delete time slot. It is being used in {$usageCount} timetable entries.");
        }

        $name = $time_slot->name ?: ($time_slot->start_time.' - '.$time_slot->end_time);
        $time_slot->delete();

        return redirect()->route('admin.time-slots.index')
            ->with('success', "Time slot '{$name}' deleted successfully.");
    }

    /**
     * Show the bulk generator form (your existing functionality)
     */
    public function showGenerateForm()
    {
        return view('admin.time_slots.generate');
    }

    /**
     * Generate multiple time slots (your existing functionality - enhanced)
     */
    public function generateSlots(Request $request)
    {
        $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'duration' => 'required|integer|min:1|max:480', // Max 8 hours
            'break_duration' => 'nullable|integer|min:0|max:120', // Max 2 hours
            'name_prefix' => 'nullable|string|max:20',
            'start_order' => 'nullable|integer|min:0|max:100',
        ]);

        $start = Carbon::createFromFormat('H:i', $request->start_time);
        $end = Carbon::createFromFormat('H:i', $request->end_time);
        $duration = (int) $request->duration;
        $breakDuration = (int) ($request->break_duration ?? 0);
        $namePrefix = $request->name_prefix ?? 'Period';
        $startOrder = (int) ($request->start_order ?? 0);

        $count = 0;
        $currentTime = $start->copy();
        $conflicts = [];
        $created = [];

        while ($currentTime->copy()->addMinutes($duration)->lte($end)) {
            $slotEnd = $currentTime->copy()->addMinutes($duration);

            $startTimeStr = $currentTime->format('H:i:s');
            $endTimeStr = $slotEnd->format('H:i:s');

            // Check for conflicts
            $conflict = TimeSlot::where(function ($query) use ($startTimeStr, $endTimeStr) {
                $query->where(function ($q) use ($startTimeStr) {
                    $q->where('start_time', '<=', $startTimeStr)
                        ->where('end_time', '>', $startTimeStr);
                })->orWhere(function ($q) use ($endTimeStr) {
                    $q->where('start_time', '<', $endTimeStr)
                        ->where('end_time', '>=', $endTimeStr);
                })->orWhere(function ($q) use ($startTimeStr, $endTimeStr) {
                    $q->where('start_time', '>=', $startTimeStr)
                        ->where('end_time', '<=', $endTimeStr);
                });
            })->first();

            if ($conflict) {
                $conflicts[] = $currentTime->format('H:i').' - '.$slotEnd->format('H:i');
            } else {
                $timeSlot = TimeSlot::create([
                    'name' => $namePrefix.' '.($count + 1),
                    'start_time' => $startTimeStr,
                    'end_time' => $endTimeStr,
                    'duration' => $duration,
                    'sort_order' => $startOrder + $count,
                    'is_active' => true,
                    'description' => 'Auto-generated time slot',
                ]);

                $created[] = $timeSlot->name;
                $count++;
            }

            $currentTime = $slotEnd->addMinutes($breakDuration);
        }

        $message = "Successfully generated {$count} new time slots.";

        if (! empty($conflicts)) {
            $conflictList = implode(', ', $conflicts);
            $message .= ' Skipped '.count($conflicts)." conflicting slots: {$conflictList}";
        }

        return redirect()->route('admin.time-slots.index')
            ->with('success', $message);
    }

    /**
     * Export time slots to CSV
     */
    public function export()
    {
        $timeSlots = TimeSlot::orderBy('sort_order')->orderBy('start_time')->get();

        $filename = 'time_slots_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($timeSlots) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, ['Name', 'Start Time', 'End Time', 'Duration (minutes)', 'Sort Order', 'Active', 'Description']);

            // Add data rows
            foreach ($timeSlots as $slot) {
                fputcsv($file, [
                    $slot->name,
                    $slot->start_time,
                    $slot->end_time,
                    $slot->duration,
                    $slot->sort_order,
                    $slot->is_active ? 'Yes' : 'No',
                    $slot->description,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Bulk actions for time slots
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'slots' => 'required|array',
            'slots.*' => 'exists:time_slots,id',
        ]);

        $slots = TimeSlot::whereIn('id', $request->slots)->get();
        $count = $slots->count();

        switch ($request->action) {
            case 'activate':
                TimeSlot::whereIn('id', $request->slots)->update(['is_active' => true]);
                $message = "Activated {$count} time slots.";
                break;

            case 'deactivate':
                TimeSlot::whereIn('id', $request->slots)->update(['is_active' => false]);
                $message = "Deactivated {$count} time slots.";
                break;

            case 'delete':
                // Check for usage in timetables
                $usedSlots = TimeSlot::whereIn('id', $request->slots)
                    ->whereHas('timetables')
                    ->count();

                if ($usedSlots > 0) {
                    return redirect()->back()
                        ->with('error', "Cannot delete {$usedSlots} time slots as they are being used in timetables.");
                }

                TimeSlot::whereIn('id', $request->slots)->delete();
                $message = "Deleted {$count} time slots.";
                break;
        }

        return redirect()->route('admin.time-slots.index')
            ->with('success', $message);
    }

    /**
     * Helper method to format duration
     */
    private function formatDuration($minutes)
    {
        if (! $minutes) {
            return '0 minutes';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0) {
            return $hours.'h'.($mins > 0 ? ' '.$mins.'m' : '');
        }

        return $mins.'m';
    }

    /**
     * Get time slots for AJAX dropdown (used by timetable forms)
     */
    public function getForDropdown()
    {
        $timeSlots = TimeSlot::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->get(['id', 'name', 'start_time', 'end_time']);

        return response()->json($timeSlots);
    }
}
