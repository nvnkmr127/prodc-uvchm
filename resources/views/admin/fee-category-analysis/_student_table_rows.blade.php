@forelse($studentFees as $fee)
    @php
        $due = $fee->amount - $fee->concession_amount - $fee->paid_amount;
        $status = $due <= 0 ? 'Paid' : ($fee->paid_amount > 0 ? 'Partial' : 'Unpaid');
        $badge = match($status) { 'Paid'=>'success', 'Partial'=>'warning', 'Unpaid'=>'danger' };
    @endphp
    <tr>
        <td>
            <span class="font-weight-bold text-dark">{{ $fee->student->name ?? 'N/A' }}</span>
            <br><small class="text-muted">{{ $fee->student->enrollment_number ?? '' }}</small>
        </td>
        <td>
            {{ $fee->student->batch->course->name ?? 'N/A' }}
            <br><small class="text-muted">{{ $fee->student->batch->name ?? '' }}</small>
        </td>
        <td class="text-right">₹{{ number_format($fee->amount, 2) }}</td>
        <td class="text-right text-info">₹{{ number_format($fee->concession_amount, 2) }}</td>
        <td class="text-right text-success">₹{{ number_format($fee->paid_amount, 2) }}</td>
        <td class="text-right text-danger font-weight-bold">₹{{ number_format($due, 2) }}</td>
        <td class="text-center">
            <span class="badge badge-{{ $badge }}">{{ ucfirst($status) }}</span>
        </td>
        <td class="text-center">
            <a href="{{ route('admin.payments.component-dashboard', $fee->student_id) }}" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-eye"></i>
            </a>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="text-center py-5 text-muted">
            <i class="fas fa-filter fa-2x mb-3 text-gray-300"></i>
            <p>No records found.</p>
        </td>
    </tr>
@endforelse
