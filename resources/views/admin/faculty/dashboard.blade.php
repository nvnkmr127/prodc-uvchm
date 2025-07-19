{{-- This uses the default app layout for non-admins, not the full admin theme --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Faculty Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="font-bold text-lg mb-4">My Classes Today ({{ now()->format('d F, Y') }})</h3>

                    @if(session('success'))
                        <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    <div class="space-y-4">
                        @forelse ($myClassesToday as $class)
                            <div class="border rounded-lg p-4 flex justify-between items-center">
                                <div>
                                    <p class="font-bold">{{ $class->subject->name }}</p>
                                    <p class="text-sm text-gray-600">{{ $class->batch->course->name }} - {{ $class->batch->name }}</p>
                                    <p class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($class->timeSlot->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($class->timeSlot->end_time)->format('h:i A') }} in Room: {{ $class->classroom->name }}</p>
                                </div>
                                <div>
                                    <a href="{{ route('faculty.attendance.create', $class->id) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Take Attendance</a>
                                </div>
                            </div>
                        @empty
                            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4" role="alert">
                                <p>You have no classes scheduled for today.</p>
                            </div>
                        @endforelse
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
