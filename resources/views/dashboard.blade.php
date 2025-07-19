<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- ADD THIS BLOCK for the overdue alert --}}
            @if(isset($overdueInvoices) && $overdueInvoices->isNotEmpty())
                <div class="alert alert-danger mb-4">
                    <strong>Payment Overdue!</strong> You have {{ $overdueInvoices->count() }} invoice(s) past the due date. Please clear your dues promptly.
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                    
                    {{-- This is where we will add the student's timetable and other info later --}}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>