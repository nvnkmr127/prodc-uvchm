<div class="row">
    <!-- Student Summary & Stats -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm bg-primary text-white overflow-hidden"
            style="background: var(--primary-gradient) !important;">
            <div class="card-body position-relative p-4">
                <div class="d-flex align-items-center justify-content-between position-relative z-1">
                    <div class="d-flex align-items-center">
                        <div class="mr-4">
                            <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center font-weight-bold shadow-sm"
                                style="width: 64px; height: 64px; font-size: 24px;">
                                {{ substr($student->name, 0, 1) }}
                            </div>
                        </div>
                        <div>
                            <h4 class="mb-1 font-weight-bold text-white">{{ $student->name }}</h4>
                            <p class="mb-0 text-white-50">
                                <i class="fas fa-id-card mr-2"></i> {{ $student->enrollment_number }}
                                <span class="mx-2 text-white-50">|</span>
                                <i class="fas fa-layer-group mr-2"></i> {{ $student->batch->name ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right d-none d-md-block">
                        <h2 class="mb-0 font-weight-bold text-white">{{ $startDate->format('F Y') }}</h2>
                        <div class="badge badge-light text-primary mt-2 px-3 py-1 shadow-sm">Monthly Report</div>
                    </div>
                </div>
                <!-- Decorative Circle -->
                <div class="position-absolute"
                    style="top: -40px; right: -40px; width: 200px; height: 200px; border-radius: 50%; background: rgba(255,255,255,0.1);">
                </div>
                <div class="position-absolute"
                    style="bottom: -20px; left: 100px; width: 100px; height: 100px; border-radius: 50%; background: rgba(255,255,255,0.05);">
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="col-12 mb-4 sticky-top" style="top: 80px; z-index: 99;">
        <div class="card border-0 shadow-lg"
            style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);">
            <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap">
                <div class="custom-control custom-checkbox ml-2 my-1">
                    <input type="checkbox" class="custom-control-input" id="selectAllParams"
                        onchange="toggleAllDays(this)">
                    <label class="custom-control-label font-weight-bold text-muted cursor-pointer"
                        for="selectAllParams">Select All Days</label>
                </div>
                <div class="btn-group shadow-sm my-1">
                    <button type="button" class="btn btn-success" onclick="markSelected('present')"
                        title="Mark Present"><i class="fas fa-check"></i> <span
                            class="d-none d-md-inline ml-1">Present</span></button>
                    <button type="button" class="btn btn-danger" onclick="markSelected('absent')" title="Mark Absent"><i
                            class="fas fa-times"></i> <span class="d-none d-md-inline ml-1">Absent</span></button>
                    <button type="button" class="btn btn-warning" onclick="markSelected('late')" title="Mark Late"><i
                            class="fas fa-clock"></i> <span class="d-none d-md-inline ml-1">Late</span></button>
                    <button type="button" class="btn btn-info" onclick="markSelected('excused')" title="Mark Excused"><i
                            class="fas fa-notes-medical"></i> <span
                            class="d-none d-md-inline ml-1">Excused</span></button>
                    <button type="button" class="btn btn-secondary" onclick="markSelected('holiday')"
                        title="Mark Holiday"><i class="fas fa-umbrella-beach"></i> <span
                            class="d-none d-md-inline ml-1">Holiday</span></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Grid -->
    <div class="col-12">
        <div class="row">
            @foreach($calendar as $dateStr => $day)
                @php
                    $status = $day['status'];
                    $record = $day['record'];
                    $isSunday = $day['is_sunday'];

                    $statusColor = match ($status) {
                        'present' => 'success',
                        'absent' => 'danger',
                        'late' => 'warning',
                        'excused' => 'info',
                        'holiday' => 'secondary',
                        'weekend' => 'light',
                        'pending' => 'light',
                        default => 'light'
                    };

                    // Background Colors
                    $arrBg = [
                        'success' => '#ecfdf5',
                        'danger' => '#fef2f2',
                        'warning' => '#fffbeb',
                        'info' => '#eff6ff',
                        'secondary' => '#fdf2f8', // Holiday (Pinkish)
                        'light' => '#ffffff'
                    ];

                    $bgColor = $arrBg[$statusColor] ?? '#ffffff';
                    $cardBorder = 'border: 1px solid var(--' . $statusColor . ');';

                    // Special Overrides
                    if ($status === 'weekend') {
                        $bgColor = '#fff5f5'; // Light Red for Sunday "oneside"
                        $cardBorder = 'border: 1px solid #fee2e2;';
                        $statusColor = 'danger-light';
                    }

                    if ($status === 'pending') {
                        $bgColor = '#ffffff';
                        $cardBorder = 'border: 1px solid #e5e7eb;';
                    }

                    if ($status === 'holiday') {
                        $cardBorder = 'border: 1px dashed #db2777;';
                    }
                @endphp

                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card h-100 shadow-hover transition-all border-0"
                        style="background-color: {{ $bgColor }}; {{ $cardBorder }} border-radius: 12px; position: relative; {{ $isSunday ? 'opacity: 0.9;' : '' }}">

                        <div class="card-body p-3 text-center">
                            {{-- Checkbox only for today or past days that aren't holidays/weekends (unless record exists)
                            --}}
                            <div class="custom-control custom-checkbox position-absolute" style="top: 10px; left: 10px;">
                                <input type="checkbox" class="custom-control-input day-checkbox" id="check_{{ $dateStr }}"
                                    value="{{ $dateStr }}">
                                <label class="custom-control-label" for="check_{{ $dateStr }}"></label>
                            </div>

                            <h4 class="mt-2 mb-0 font-weight-bold {{ $isSunday ? 'text-danger' : 'text-dark' }}">
                                {{ $day['date']->format('d') }}</h4>
                            <small class="text-uppercase {{ $isSunday ? 'text-danger' : 'text-muted' }} font-weight-bold"
                                style="font-size: 0.7rem; letter-spacing: 1px;">{{ $day['date']->format('l') }}</small>

                            <div class="mt-3">
                                <span
                                    class="badge badge-{{ $status === 'weekend' ? 'light text-danger' : $statusColor }} px-3 py-2 text-capitalize shadow-sm"
                                    style="font-size: 0.75rem;">
                                    {{ $day['holiday_name'] ?? ($status == 'weekend' ? 'Weekend' : $status) }}
                                </span>
                            </div>

                            @if($record)
                                <div class="mt-2 text-muted" style="font-size: 0.65rem;">
                                    <i class="fas fa-user-edit mr-1"></i> {{ $record->markedBy->name ?? 'System' }}<br>
                                    <i class="fas fa-clock mr-1"></i>
                                    {{ $record->marked_at ? $record->marked_at->format('H:i') : '' }}
                                </div>
                            @elseif($status === 'absent' && !$isSunday && $status !== 'holiday')
                                <div class="mt-2 text-danger" style="font-size: 0.65rem;">
                                    <i class="fas fa-exclamation-circle mr-1"></i> Auto Absent
                                </div>
                            @else
                                <div class="mt-2 text-muted" style="min-height: 20px;"></div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<style>
    /* Scoped styles for the calendar */
    .shadow-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        z-index: 10;
    }

    .transition-all {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .cursor-pointer {
        cursor: pointer;
    }
</style>