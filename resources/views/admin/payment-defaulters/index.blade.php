<?php

// 5. Blade Views for Payment Management

// resources/views/admin/payment-defaulters/index.blade.php
?>
@extends('layouts.admin')

@section('title', 'Payment Defaulters')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Payment Defaulters Dashboard</h4>
                    <div class="btn-group">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bulkReminderModal">
                            Send Bulk Reminders
                        </button>
                        <a href="{{ route('admin.defaulters.export') }}" class="btn btn-success">
                            <i class="fas fa-download"></i> Export
                        </a>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h5>{{ $summary['total_defaulters'] }}</h5>
                                    <p>Total Defaulters</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5>₹{{ number_format($summary['total_overdue_amount']) }}</h5>
                                    <p>Total Overdue Amount</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5>{{ $summary['categories']['chronic'] ?? 0 }}</h5>
                                    <p>Chronic Defaulters</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-secondary text-white">
                                <div class="card-body">
                                    <h5>{{ $summary['categories']['severe'] ?? 0 }}</h5>
                                    <p>Severe Cases</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Options -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <form method="GET" class="d-flex gap-2">
                                <select name="category" class="form-select">
                                    <option value="">All Categories</option>
                                    <option value="mild" {{ request('category') == 'mild' ? 'selected' : '' }}>Mild</option>
                                    <option value="moderate" {{ request('category') == 'moderate' ? 'selected' : '' }}>Moderate</option>
                                    <option value="severe" {{ request('category') == 'severe' ? 'selected' : '' }}>Severe</option>
                                    <option value="chronic" {{ request('category') == 'chronic' ? 'selected' : '' }}>Chronic</option>
                                </select>
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </form>
                        </div>
                    </div>

                    <!-- Defaulters Table -->
                    <div class="table-responsive">
                        <table class="table table-striped" id="defaultersTable">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>Student</th>
                                    <th>Course/Batch</th>
                                    <th>Overdue Amount</th>
                                    <th>Days Overdue</th>
                                    <th>Category</th>
                                    <th>Fee Types</th>
                                    <th>Contact</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($defaulters as $defaulter)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="student_ids[]" value="{{ $defaulter['student']->id }}" class="student-checkbox">
                                    </td>
                                    <td>
                                        <strong>{{ $defaulter['student']->name }}</strong><br>
                                        <small>{{ $defaulter['student']->enrollment_number }}</small>
                                    </td>
                                    <td>
                                        {{ $defaulter['student']->course_name }}<br>
                                        <small>{{ $defaulter['student']->batch_name }}</small>
                                    </td>
                                    <td>
                                        <span class="text-danger font-weight-bold">
                                            ₹{{ number_format($defaulter['total_overdue_amount']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $defaulter['overdue_days'] > 30 ? 'danger' : 'warning' }}">
                                            {{ $defaulter['overdue_days'] }} days
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ 
                                            $defaulter['defaulter_category'] == 'chronic' ? 'danger' : 
                                            ($defaulter['defaulter_category'] == 'severe' ? 'warning' : 'info') 
                                        }}">
                                            {{ ucfirst($defaulter['defaulter_category']) }}
                                        </span>
                                    </td>
                                    <td>
                                        @foreach($defaulter['overdue_fee_types'] as $feeType)
                                            <span class="badge badge-secondary">{{ ucwords(str_replace('_', ' ', $feeType)) }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        @if($defaulter['contact_info']['student_mobile'])
                                            <a href="tel:{{ $defaulter['contact_info']['student_mobile'] }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-phone"></i>
                                            </a>
                                        @endif
                                        @if($defaulter['contact_info']['email'])
                                            <a href="mailto:{{ $defaulter['contact_info']['email'] }}" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-envelope"></i>
                                            </a>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.students.show', $defaulter['student']->id) }}" 
                                               class="btn btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn btn-outline-warning send-reminder-btn" 
                                                    data-student-id="{{ $defaulter['student']->id }}"
                                                    data-student-name="{{ $defaulter['student']->name }}">
                                                <i class="fas fa-bell"></i>
                                            </button>
                                            <a href="{{ route('admin.invoices.ledger', $defaulter['student']->id) }}" 
                                               class="btn btn-outline-success">
                                                <i class="fas fa-receipt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Reminder Modal -->
<div class="modal fade" id="bulkReminderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.defaulters.bulk-reminders') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Send Bulk Reminders</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Reminder Type</label>
                        <select name="reminder_type" class="form-select" required>
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                            <option value="whatsapp">WhatsApp</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Custom Message</label>
                        <textarea name="message" class="form-control" rows="4" 
                                  placeholder="Leave blank for auto-generated message"></textarea>
                    </div>
                    <div id="selectedStudents"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Reminders</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = this.checked);
    updateSelectedStudents();
});

document.querySelectorAll('.student-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateSelectedStudents);
});

function updateSelectedStudents() {
    const selected = document.querySelectorAll('.student-checkbox:checked');
    const selectedDiv = document.getElementById('selectedStudents');
    
    if (selected.length > 0) {
        selectedDiv.innerHTML = `<strong>${selected.length} students selected</strong>`;
        // Add hidden inputs for selected student IDs
        selectedDiv.innerHTML += '<div style="display:none;">';
        selected.forEach(checkbox => {
            selectedDiv.innerHTML += `<input type="hidden" name="student_ids[]" value="${checkbox.value}">`;
        });
        selectedDiv.innerHTML += '</div>';
    } else {
        selectedDiv.innerHTML = '<em>No students selected</em>';
    }
}
</script>
@endsection