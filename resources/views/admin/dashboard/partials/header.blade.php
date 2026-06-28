        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="container">
                <div class="content">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="mb-2">
                                <i class="fas fa-graduation-cap mr-3"></i> Welcome back,
                                <strong>{{ $dashboard_data['user_name'] ?? auth()->user()->name }}</strong>
                            </h1>
                            <p class="mb-0 opacity-75">
                                ! Here's your academic overview.
                            </p>
                        </div>
                        <div class="col-md-4 text-md-right">
                            <div class="dashboard-date">
                                <div class="text-sm opacity-75">Academic Year</div>
                                <div class="h5 mb-1">{{ $dashboard_data['academic_year'] ?? '2024-25' }}</div>
                                <div class="text-sm opacity-75" id="current-time">
                                    {{ $dashboard_data['current_date'] ?? now()->format('d M Y') }} •
                                    <span
                                        id="live-time">{{ $dashboard_data['current_time'] ?? now()->format('H:i:s') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Birthday Celebrations -->
        <style>
            .birthday-glass-card {
                background: white;
                border-radius: 20px;
                border: 1px solid rgba(0, 0, 0, 0.05);
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
                overflow: hidden;
                margin-bottom: 2rem;
            }

            .bday-accent {
                background: linear-gradient(135deg, #FF512F 0%, #DD2476 100%);
                color: white;
                padding: 2.5rem 2rem;
                display: flex;
                flex-direction: column;
                justify-content: center;
                position: relative;
                overflow: hidden;
            }

            .bday-accent::after {
                content: '🎈';
                position: absolute;
                font-size: 6rem;
                right: -1rem;
                bottom: -1rem;
                opacity: 0.15;
                transform: rotate(-15deg);
            }

            .bday-list-section {
                padding: 1.5rem;
                border-right: 1px solid rgba(0, 0, 0, 0.05);
            }

            .bday-list-section:last-child {
                border-right: none;
            }

            .bday-student-card {
                background: #f8fafc;
                padding: 0.75rem;
                border-radius: 12px;
                margin-bottom: 0.75rem;
                transition: all 0.3s ease;
                border: 1px solid transparent;
            }

            .bday-student-card:hover {
                transform: translateX(5px);
                border-color: #DD2476;
                background: white;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            }

            .bday-empty-state {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100%;
                min-height: 150px;
                opacity: 0.6;
            }

            .pulse-indicator {
                width: 10px;
                height: 10px;
                background: #fff;
                border-radius: 50%;
                display: inline-block;
                margin-right: 8px;
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4);
                animation: pulse-white 2s infinite;
            }

            @keyframes pulse-white {
                0% {
                    transform: scale(0.95);
                    box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
                }

                70% {
                    transform: scale(1);
                    box-shadow: 0 0 0 10px rgba(255, 255, 255, 0);
                }

                100% {
                    transform: scale(0.95);
                    box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
                }
            }

            @media (max-width: 1200px) {
                .bday-list-section {
                    border-right: none;
                    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                }

                .bday-accent {
                    text-align: center;
                }

                .birthday-glass-card {
                    margin-left: 1rem;
                    margin-right: 1rem;
                }
            }
        </style>

        <div class="birthday-glass-card animate-fade-in mt-n4 position-relative zindex-1 mx-3 sticky-top-mobile"
            style="top: 0px;">
            <div class="row g-0">
                <!-- Spotlight -->
                <div class="col-xl-3 bday-accent">
                    <div class="mb-2"><span class="pulse-indicator"></span><span
                            class="small fw-bold text-uppercase">Today</span></div>
                    <h2 class="fw-bold mb-4">Happy Birthday!</h2>
                    <div class="today-list" style="max-height: 180px; overflow-y: auto; padding-right: 5px;">
                        @forelse($dashboard_data['birthdays']['today'] as $student)
                            <div
                                class="d-flex align-items-center gap-4 mb-3 justify-content-center justify-content-xl-start text-left">
                                <img src="{{ $student->photo_url }}" class="rounded-circle border border-white shadow-sm"
                                    style="width: 50px; height: 50px; object-fit: cover;" alt="">
                                <div class="ms-4">

                                    <div class="fw-bold small text-white">{{ $student->name }}</div>
                                    <div class="text-xs opacity-100 fw-bold">{{ $student->batch->course->code ?? 'N/A' }}</div>
                                    <div class="text-xs opacity-75">{{ $student->batch->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="bday-empty-state">
                                <i class="fas fa-gift fa-2x mb-2"></i>
                                <p class="small italic mb-0">No cakes today!</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Other Days -->
                <div class="col-xl-9">
                    <div class="row g-0 h-100">
                        <div class="col-md-4 bday-list-section">
                            <div class="text-xs fw-bold text-muted text-uppercase mb-3"><i
                                    class="fas fa-calendar-day text-primary mr-2"></i> Tomorrow</div>
                            <div class="scroll-container" style="max-height: 180px; overflow-y: auto;">
                                @forelse($dashboard_data['birthdays']['tomorrow'] as $student)
                                    <div class="bday-student-card d-flex align-items-center gap-3">
                                        <img src="{{ $student->photo_url }}" class="rounded-circle border"
                                            style="width: 32px; height: 32px; object-fit: cover;" alt="">
                                        <div>
                                            <div class="text-xs fw-bold text-dark">{{ $student->name }}</div>
                                            <div class="text-xs text-muted fw-bold">{{ $student->batch->course->code ?? 'N/A' }}
                                            </div>
                                            <div class="text-xs text-muted">{{ $student->batch->name ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="bday-empty-state">
                                        <p class="small italic mb-0">Quiet tomorrow</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="col-md-4 bday-list-section">
                            <div class="text-xs fw-bold text-muted text-uppercase mb-3"><i
                                    class="fas fa-forward text-info mr-2"></i> Next 3 Days</div>
                            <div class="scroll-container" style="max-height: 180px; overflow-y: auto;">
                                @forelse($dashboard_data['birthdays']['upcoming_3_days'] as $student)
                                    <div class="bday-student-card d-flex align-items-center gap-3">
                                        <img src="{{ $student->photo_url }}" class="rounded-circle border"
                                            style="width: 32px; height: 32px; object-fit: cover;" alt="">
                                        <div>
                                            <div class="text-xs fw-bold text-dark">{{ $student->name }}</div>
                                            <div class="text-xs text-info fw-bold">{{ $student->dob->format('d M') }}</div>
                                            <div class="text-xs text-muted">{{ $student->batch->course->code ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="bday-empty-state">
                                        <p class="small italic mb-0">Nothing upcoming</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="col-md-4 bday-list-section">
                            <div class="text-xs fw-bold text-muted text-uppercase mb-3"><i
                                    class="fas fa-history text-secondary mr-2"></i> Just Passed</div>
                            <div class="scroll-container" style="max-height: 180px; overflow-y: auto;">
                                @forelse($dashboard_data['birthdays']['last_3_days'] as $student)
                                    <div class="bday-student-card d-flex align-items-center gap-3 opacity-75">
                                        <img src="{{ $student->photo_url }}" class="rounded-circle border"
                                            style="width: 32px; height: 32px; object-fit: cover;" alt="">
                                        <div>
                                            <div class="text-xs fw-bold text-dark">{{ $student->name }}</div>
                                            <div class="text-xs text-muted fw-bold">{{ $student->dob->format('d M') }}</div>
                                            <div class="text-xs text-muted">{{ $student->batch->course->code ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="bday-empty-state">
                                        <p class="small italic mb-0">No recent ones</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
