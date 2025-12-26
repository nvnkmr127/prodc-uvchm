@forelse($categoryAnalysis as $category)
    <tr>
        <td class="pl-4 align-middle">
            <div class="d-flex align-items-center">
                <div class="mr-3">
                    @if($category->is_mandatory)
                        <div class="icon-circle bg-primary text-white">
                            <i class="fas fa-lock"></i>
                        </div>
                    @else
                        <div class="icon-circle bg-info text-white">
                            <i class="fas fa-unlock"></i>
                        </div>
                    @endif
                </div>
                <div>
                    <div class="font-weight-bold text-gray-800">{{ $category->name }}</div>
                    <div class="small text-muted">{{ $category->category_code ?? 'No Code' }}</div>
                </div>
            </div>
        </td>
        <td class="align-middle">
            <div class="small">
                <div class="d-flex justify-content-between mb-1" style="width: 120px;">
                    <span>Paid:</span>
                    <span class="font-weight-bold text-success">{{ number_format($category->paid_students) }}</span>
                </div>
                <div class="d-flex justify-content-between" style="width: 120px;">
                    <span>Pending:</span>
                    <span class="font-weight-bold text-warning">{{ number_format($category->pending_students) }}</span>
                </div>
            </div>
        </td>
        <td class="align-middle">
            <div class="font-weight-bold text-gray-800">
                ₹{{ number_format($category->total_billed) }}</div>
            @if($category->total_concessions > 0)
                <div class="small text-info">
                    - ₹{{ number_format($category->total_concessions) }} (Conc.)
                </div>
            @endif
        </td>
        <td class="align-middle" style="min-width: 140px;">
            <div class="d-flex align-items-center mb-1">
                <span class="font-weight-bold small mr-2">{{ $category->collection_rate }}%</span>
                <div class="progress progress-thick w-100">
                    <div class="progress-bar {{ $category->collection_rate >= 80 ? 'bg-success' : ($category->collection_rate >= 50 ? 'bg-warning' : 'bg-danger') }}"
                        role="progressbar" style="width: {{ $category->collection_rate }}%"></div>
                </div>
            </div>
            <div class="small text-success">
                Collected: ₹{{ number_format($category->total_collected) }}
            </div>
        </td>
        <td class="align-middle">
            <div class="font-weight-bold text-danger">₹{{ number_format($category->total_pending) }}
            </div>
            @if($category->total_overdue > 0)
                <div class="small text-danger font-weight-bold">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    ₹{{ number_format($category->total_overdue) }} Overdue
                </div>
            @endif
        </td>
        <td class="align-middle text-right pr-4">
            <div class="btn-group shadow-sm" role="group">
                <a href="{{ route('admin.fee-category-analysis.show', $category->id) }}"
                    class="btn btn-sm btn-white text-primary border-light hover-primary" data-toggle="tooltip"
                    title="View Details">
                    <i class="fas fa-eye"></i>
                </a>
                <button type="button" class="btn btn-sm btn-white text-warning border-light hover-warning"
                    onclick="showPendingStudents({{ $category->id }})" data-toggle="tooltip" title="View Pending List">
                    <i class="fas fa-users"></i>
                </button>
                <button type="button" class="btn btn-sm btn-white text-success border-light hover-success"
                    onclick="sendReminders({{ $category->id }})" data-toggle="tooltip" title="Send Reminders">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="text-center py-5">
            <div class="text-gray-500">
                <i class="fas fa-folder-open fa-3x mb-3 text-gray-300"></i>
                <p>No fee categories found matching your filters.</p>
            </div>
        </td>
    </tr>
@endforelse