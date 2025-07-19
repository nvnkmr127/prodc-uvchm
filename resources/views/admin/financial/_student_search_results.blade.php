<div class="table-responsive">
    <table class="table table-bordered">
        <thead class="thead-light">
            <tr>
                <th>Student Name</th>
                <th>Enrollment #</th>
                <th>Batch</th>
                <th class="text-right">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($students as $student)
                <tr>
                    <td>{{ $student->user->name ?? 'N/A' }}</td>
                    <td>{{ $student->enrollment_number }}</td>
                    <td>{{ $student->batch->course->name ?? 'N/A' }} - {{ $student->batch->name ?? 'N/A' }}</td>
                    <td class="text-right">
                        <a href="{{ route('admin.financials.student.ledger', $student) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye fa-sm"></i> View Ledger
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No students found matching your search.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
