@if($students->isNotEmpty())
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Enrollment No.</th>
                    <th>Batch</th>
                    <th>Total Due</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $student)
                    @php
                        // Recalculate basic due for fast display (or use accessor if improved)
                        $totalFees = $student->studentFees->sum('amount');
                        $paid = $student->studentFees->sum('paid_amount');
                        $concession = $student->studentFees->sum('concession_amount');
                        $due = $totalFees - $paid - $concession;
                    @endphp
                    <tr>
                        <td class="font-weight-bold">{{ $student->name }}</td>
                        <td>{{ $student->enrollment_number }}</td>
                        <td>{{ $student->batch->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge badge-{{ $due > 0 ? 'danger' : 'success' }}">
                                ₹ {{ number_format($due, 2) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.payments.component-dashboard', $student->id) }}"
                                class="btn btn-primary btn-sm">
                                <i class="fas fa-wallet"></i> View Ledger
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="text-center py-4">
        <i class="fas fa-user-slash fa-2x text-gray-300 mb-3"></i>
        <p class="text-muted">No students found matching your search.</p>
    </div>
@endif