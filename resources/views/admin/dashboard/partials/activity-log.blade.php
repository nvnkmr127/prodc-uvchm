                <!-- My Activity Log -->
                <div class="widget-card animate-fade-in animate-delay-200">
                    <div class="widget-header">
                        <h6 class="widget-title">
                            <i class="fas fa-history"></i>My Activity Log
                        </h6>
                        <a href="{{ route('admin.activity-log.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                    <div class="widget-body">
                        <div class="activity-log" id="activity-list">
                            @if(isset($dashboard_data['my_activities']) && count($dashboard_data['my_activities']) > 0)
                                @foreach($dashboard_data['my_activities'] as $activity)
                                    <div class="activity-item">
                                        <div class="activity-icon {{ $activity['type'] ?? 'update' }}">
                                            <i class="fas fa-{{ $activity['icon'] ?? 'edit' }}"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-text">
                                                {{ $activity['description'] ?? 'No description provided.' }}
                                            </div>
                                            <div class="activity-meta">
                                                @if(isset($activity['student_name']))
                                                    Student: {{ $activity['student_name'] }}
                                                @endif
                                                @if(isset($activity['amount']))
                                                    • Amount: ₹{{ number_format($activity['amount']) }}
                                                @endif
                                            </div>
                                        </div>
                                        <div class="activity-time">
                                            @if(isset($activity['created_at']))
                                                {{ \Carbon\Carbon::parse($activity['created_at'])->diffForHumans() }}
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="loading-spinner">
                                    <div class="spinner"></div>
                                    <p class="mt-2 text-muted">Loading your activities...</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
