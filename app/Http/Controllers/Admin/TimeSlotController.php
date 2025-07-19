<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TimeSlot;
use Illuminate\Http\Request;

class TimeSlotController extends Controller
{
    public function index()
    {
        $time_slots = TimeSlot::orderBy('start_time')->get();
        return view('admin.time_slots.index', compact('time_slots'));
    }

    public function create()
    {
        return view('admin.time_slots.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);
        TimeSlot::create($validated);
        return redirect()->route('admin.time-slots.index')->with('success', 'Time slot created successfully.');
    }
 // This method shows the new generator form
    public function showGenerateForm()
    {
        return view('admin.time_slots.generate');
    }

    // This method handles the logic for the new generator
    public function generateSlots(Request $request)
    {
        $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'duration' => 'required|integer|min:1',
            'break_duration' => 'nullable|integer|min:0',
        ]);

        $start = Carbon::parse($request->start_time);
        $end = Carbon::parse($request->end_time);
        $duration = (int)$request->duration;
        $break = (int)$request->break_duration;
        $count = 0;

        $currentTime = $start->copy();

        while ($currentTime->copy()->addMinutes($duration)->lte($end)) {
            $slotEnd = $currentTime->copy()->addMinutes($duration);
            
            TimeSlot::create([
                'start_time' => $currentTime->format('H:i:s'),
                'end_time' => $slotEnd->format('H:i:s'),
            ]);
            
            $currentTime = $slotEnd->addMinutes($break);
            $count++;
        }

        return redirect()->route('admin.time-slots.index')->with('success', "Successfully generated {$count} new time slots.");
    }
    public function edit(TimeSlot $time_slot)
    {
        return view('admin.time_slots.edit', compact('time_slot'));
    }

    public function update(Request $request, TimeSlot $time_slot)
    {
        $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);
        $time_slot->update($validated);
        return redirect()->route('admin.time-slots.index')->with('success', 'Time slot updated successfully.');
    }

    public function destroy(TimeSlot $time_slot)
    {
        $time_slot->delete();
        return redirect()->route('admin.time-slots.index')->with('success', 'Time slot deleted successfully.');
    }
}