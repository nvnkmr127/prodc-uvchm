<div class="card" style="border: 2px solid #4e73df; width: 320px; height: 510px; font-family: sans-serif;">
    <div class="card-header bg-primary text-white text-center">
        <h5 class="mb-0">{{ setting('college_name', 'My College') }}</h5>
    </div>
    <div class="card-body text-center">
        <img src="{{ $student->photo ? asset('storage/' . $student->photo->file_path) : 'https://ui-avatars.com/api/?name=' . urlencode($student->name) . '&size=150' }}" alt="Student Photo" class="img-thumbnail rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
        <h5 class="card-title font-weight-bold">{{ $student->name }}</h5>
        <p class="card-text mb-1"><strong>Course:</strong> {{ $student->batch->course->name ?? 'N/A' }}</p>
        <p class="card-text mb-3"><strong>Batch:</strong> {{ $student->batch->name ?? 'N/A' }}</p>

        <p class="card-text mb-1"><strong>Enrollment No:</strong></p>
        <h6 class="font-weight-bold">{{ $student->enrollment_number }}</h6>

         <p class="card-text mb-1 mt-3"><strong>Valid Upto:</strong></p>
        <h6 class="font-weight-bold">{{ \Carbon\Carbon::parse($student->batch->end_date ?? now()->addYear())->format('M Y') }}</h6>
    </div>
    <div class="card-footer text-center bg-light">
        <p class="small text-muted mb-0">{{ setting('college_address', 'College Address') }}</p>
    </div>
</div>