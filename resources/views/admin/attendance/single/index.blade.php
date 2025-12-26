@extends('layouts.theme')

@section('title', 'Single Student Attendance')

@section('content')
    <div class="row">
        <!-- Sidebar Selection Panel -->
        <div class="col-lg-3 col-md-4 mb-4">
            <div class="card border-0 shadow-lg sticky-top"
                style="top: 100px; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="mb-0 text-primary font-weight-bold">
                        <i class="fas fa-filter mr-2"></i> Selection
                    </h5>
                </div>
                <div class="card-body">
                    <div class="form-group mb-4">
                        <label for="student_search" class="text-xs font-weight-bold text-uppercase text-muted mb-2">Find
                            Student</label>
                        <div class="position-relative">
                            <input type="text" id="student_search" class="form-control"
                                placeholder="Search by name or enrollment..." style="height: 50px;">
                            <input type="hidden" id="student_id">
                            <div id="search_results" class="list-group position-absolute w-100 shadow-lg"
                                style="z-index: 1000; display: none; max-height: 300px; overflow-y: auto;">
                                <!-- Results will go here -->
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block" id="selected_student_info">Enter name or enrollment
                            no.</small>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group mb-4">
                                <label for="month"
                                    class="text-xs font-weight-bold text-uppercase text-muted mb-2">Month</label>
                                <select id="month" class="form-control">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ $m == date('n') ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group mb-4">
                                <label for="year"
                                    class="text-xs font-weight-bold text-uppercase text-muted mb-2">Year</label>
                                <select id="year" class="form-control">
                                    @foreach(range(date('Y') - 1, date('Y') + 1) as $y)
                                        <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <button id="load-calendar" class="btn btn-primary btn-block py-3 shadow-md font-weight-bold">
                        <i class="fas fa-calendar-check mr-2"></i> Load Attendance
                    </button>

                    <div class="mt-4 text-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle mr-1"></i> Use the sidebar to filter and load the attendance
                            calendar.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Calendar Area -->
        <div class="col-lg-9 col-md-8">
            <div id="calendar-container">
                <!-- Empty State -->
                <div class="card border-0 shadow-sm"
                    style="min-height: 500px; display: flex; align-items: center; justify-content: center;">
                    <div class="text-center text-muted">
                        <div class="mb-3">
                            <i class="fas fa-user-graduate fa-4x text-gray-300"></i>
                        </div>
                        <h5>No Student Selected</h5>
                        <p class="mb-0">Please select a student from the sidebar to view their attendance.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Debounce function to limit API calls
        function debounce(func, wait) {
            let timeout;
            return function (...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        const searchInput = document.getElementById('student_search');
        const resultsContainer = document.getElementById('search_results');
        const studentIdInput = document.getElementById('student_id');
        const studentInfo = document.getElementById('selected_student_info');

        searchInput.addEventListener('input', debounce(function () {
            const query = this.value;
            if (query.length < 2) {
                resultsContainer.style.display = 'none';
                return;
            }

            fetch(`{{ route('admin.attendance.single.students') }}?search=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    resultsContainer.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(student => {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.className = 'list-group-item list-group-item-action';
                            item.innerHTML = `
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="font-weight-bold">${student.name}</div>
                                            <small class="text-muted">${student.enrollment_number}</small>
                                        </div>
                                        <small class="text-primary">${student.batch_name || ''}</small>
                                    </div>
                                `;
                            item.onclick = function (e) {
                                e.preventDefault();
                                selectStudent(student);
                            };
                            resultsContainer.appendChild(item);
                        });
                        resultsContainer.style.display = 'block';
                    } else {
                        resultsContainer.innerHTML = '<div class="list-group-item text-muted">No students found</div>';
                        resultsContainer.style.display = 'block';
                    }
                })
                .catch(err => console.error(err));
        }, 300));

        function selectStudent(student) {
            searchInput.value = `${student.name} (${student.enrollment_number})`;
            studentIdInput.value = student.id;
            resultsContainer.style.display = 'none';
            studentInfo.innerHTML = `<i class="fas fa-check-circle text-success"></i> Selected: <strong>${student.name}</strong>`;
        }

        // Hide dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                resultsContainer.style.display = 'none';
            }
        });

        document.getElementById('load-calendar').addEventListener('click', function () {
            const studentId = document.getElementById('student_id').value;
            const month = document.getElementById('month').value;
            const year = document.getElementById('year').value;

            if (!studentId) {
                alert('Please search and select a student first');
                return;
            }

            const container = document.getElementById('calendar-container');
            container.innerHTML = '<div class="card border-0 shadow-sm" style="min-height: 500px; display: flex; align-items: center; justify-content: center;"><div class="spinner-border text-primary" role="status"></div></div>';

            fetch(`{{ route('admin.attendance.single.calendar') }}?student_id=${studentId}&month=${month}&year=${year}`)
                .then(response => response.text())
                .then(html => {
                    container.innerHTML = html;
                })
                .catch(error => {
                    container.innerHTML = '<div class="alert alert-danger">Failed to load calendar</div>';
                    console.error(error);
                });
        });

        // Make functions global so they can be called from the partial view
        window.toggleAllDays = function (source) {
            const checkboxes = document.querySelectorAll('.day-checkbox');
            checkboxes.forEach(cb => cb.checked = source.checked);
        };

        window.markSelected = function (status) {
            const selected = Array.from(document.querySelectorAll('.day-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) {
                alert('Please select at least one date');
                return;
            }

            const studentId = document.getElementById('student_id').value;
            // Find the button inside the layout
            const button = event.target.closest('button');
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;

            const payload = {
                student_id: studentId,
                attendance: selected.map(date => ({
                    date: date,
                    status: status
                })),
                _token: '{{ csrf_token() }}'
            };

            fetch('{{ route("admin.attendance.single.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
                .then(response => response.json())
                .then(data => {
                    // Reload calendar
                    document.getElementById('load-calendar').click();
                })
                .catch(error => {
                    alert('Error updating attendance');
                    console.error(error);
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                });
        };
    </script>
@endpush