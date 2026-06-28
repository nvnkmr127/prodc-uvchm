                <!-- Enquiry & Funnel Metrics -->
                <div class="widget-card animate-fade-in animate-delay-100">
                    <div class="widget-header">
                        <h6 class="widget-title">
                            <i class="fas fa-funnel-dollar"></i>Enquiry & Admission Funnel
                        </h6>
                    </div>
                    <div class="widget-body">
                        <div class="row">
                            <div class="col-md-3 border-right">
                                <div class="text-center py-2">
                                    <div class="h3 font-weight-bold text-primary">{{ $dashboard_data['enquiry_stats']['today_count'] ?? 0 }}</div>
                                    <div class="small text-muted text-uppercase">New Today</div>
                                </div>
                            </div>
                            <div class="col-md-3 border-right">
                                <div class="text-center py-2">
                                    <div class="h3 font-weight-bold text-info">{{ $dashboard_data['enquiry_stats']['new_count'] ?? 0 }}</div>
                                    <div class="small text-muted text-uppercase">Fresh Leads</div>
                                </div>
                            </div>
                            <div class="col-md-3 border-right">
                                <div class="text-center py-2">
                                    <div class="h3 font-weight-bold text-warning">{{ $dashboard_data['enquiry_stats']['followup_today'] ?? 0 }}</div>
                                    <div class="small text-muted text-uppercase">Due Follow-ups</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center py-2">
                                    <div class="h3 font-weight-bold text-success">{{ $dashboard_data['enquiry_stats']['admitted_count'] ?? 0 }}</div>
                                    <div class="small text-muted text-uppercase">Total Admitted</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
