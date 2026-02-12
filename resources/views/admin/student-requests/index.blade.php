@extends('layouts.theme')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Student Profile Requests</h1>
            <div>
                <a href="{{ route('admin.student-requests.index', ['status' => 'pending']) }}"
                    class="btn btn-sm {{ $status == 'pending' ? 'btn-primary' : 'btn-outline-primary' }}">Pending</a>
                <a href="{{ route('admin.student-requests.index', ['status' => 'approved']) }}"
                    class="btn btn-sm {{ $status == 'approved' ? 'btn-success' : 'btn-outline-success' }}">Approved</a>
                <a href="{{ route('admin.student-requests.index', ['status' => 'rejected']) }}"
                    class="btn btn-sm {{ $status == 'rejected' ? 'btn-danger' : 'btn-outline-danger' }}">Rejected</a>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Field</th>
                                <th>Requested Change</th>
                                <th>Status</th>
                                <th>Date</th>
                                @if($status == 'pending')
                                    <th>Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $req)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.students.show', $req->student_id) }}" 
                                           class="text-primary hover:underline">
                                            <div class="font-weight-bold">{{ $req->student_name }}</div>
                                        </a>
                                        <div class="small text-muted">{{ $req->enrollment_number }}</div>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ ucfirst($req->field_group) }}</span>
                                    </td>
                                    <td>
                                        @php $data = json_decode($req->new_data, true); @endphp

                                        @if($req->field_group == 'address')
                                            <div class="small text-muted mb-1">New Address:</div>
                                            <div class="p-2 bg-light rounded text-break">{{ $data['address'] ?? 'N/A' }}</div>
                                        @elseif($req->field_group == 'photo')
                                            <div class="d-flex align-items-center">
                                                <div class="mr-3 text-center">
                                                    <div class="small text-muted mb-1">New Photo</div>
                                                    @if($req->proof_file)
                                                        <a href="{{ route('admin.student-requests.preview', $req->id) }}"
                                                            target="_blank">
                                                            <img src="{{ route('admin.student-requests.preview', $req->id) }}"
                                                                style="width: 80px; height: 80px; object-fit: cover"
                                                                class="rounded border">
                                                        </a>
                                                    @else
                                                        <span class="text-danger">File Missing</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @elseif($req->field_group == 'personal')
                                            <div class="small text-muted mb-1">Mobile Number Update:</div>
                                            <div class="p-2 bg-light rounded">
                                                <strong>{{ ucfirst($data['type'] ?? 'Unknown') }}:</strong> 
                                                {{ $data['mobile'] ?? 'N/A' }}
                                            </div>
                                        @elseif($req->field_group == 'dob')
                                            <div class="small text-muted mb-1">Date of Birth:</div>
                                            <div class="p-2 bg-light rounded">
                                                {{ isset($data['dob']) ? \Carbon\Carbon::parse($data['dob'])->format('d M, Y') : 'N/A' }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($req->status == 'pending')
                                            <span class="badge badge-warning">Pending</span>
                                        @elseif($req->status == 'approved')
                                            <span class="badge badge-success">Approved</span>
                                            <div class="small text-muted">
                                                {{ \Carbon\Carbon::parse($req->processed_at)->format('d M Y') }}</div>
                                            @if($req->approved_by_name)
                                                <div class="small text-muted">
                                                    <i class="fas fa-user-check"></i> {{ $req->approved_by_name }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="badge badge-danger">Rejected</span>
                                            <div class="small text-muted">{{ $req->admin_comment }}</div>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($req->created_at)->diffForHumans() }}</td>
                                    @if($status == 'pending')
                                        <td>
                                            <form action="{{ route('admin.student-requests.action', $req->id) }}" method="POST"
                                                class="d-inline">
                                                @csrf
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-success btn-sm"
                                                    onclick="return confirm('Approve this change?')">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>

                                            <button type="button" class="btn btn-danger btn-sm" data-toggle="modal"
                                                data-target="#rejectModal-{{ $req->id }}">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">No requests found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $requests->appends(['status' => $status])->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modals (Outside table for better compatibility) -->
    @if($status == 'pending')
        @foreach($requests as $req)
            <div class="modal fade" id="rejectModal-{{ $req->id }}" tabindex="-1" role="dialog"
                aria-hidden="true" style="z-index: 1060;">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <form action="{{ route('admin.student-requests.action', $req->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="action" value="reject">
                            <div class="modal-header">
                                <h5 class="modal-title">Reject Request</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group mb-0">
                                    <label class="font-weight-bold">Reason for Rejection</label>
                                    <textarea name="comment" class="form-control" rows="3" placeholder="Explain why this request is being rejected..." required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">Reject Request</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
@endsection