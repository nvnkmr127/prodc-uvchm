@if(isset($summaryStats['top_performing_category']) || isset($summaryStats['most_pending_category']))
    <div class="row">
        @if(isset($summaryStats['top_performing_category']) && $summaryStats['top_performing_category'])
            <div class="col-lg-6 mb-4">
                <div class="card bg-gradient-success-soft border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start text-white">
                            <div>
                                <div class="text-white-50 small text-uppercase font-weight-bold mb-1">Top Performer</div>
                                <h4 class="font-weight-bold mb-3">{{ $summaryStats['top_performing_category']->name }}</h4>
                                <div class="d-flex align-items-center">
                                    <span
                                        class="display-4 font-weight-bold mr-3">{{ round($summaryStats['top_performing_category']->collection_rate) }}<small
                                            class="text-white-50">%</small></span>
                                    <div class="text-white-50 line-height-1 small">
                                        Collection<br>Rate
                                    </div>
                                </div>
                            </div>
                            <div class="icon-circle bg-white text-success shadow-sm" style="width: 60px; height: 60px;">
                                <i class="fas fa-trophy fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(isset($summaryStats['most_pending_category']) && $summaryStats['most_pending_category'])
            <div class="col-lg-6 mb-4">
                <div class="card bg-gradient-danger-soft border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start text-white">
                            <div>
                                <div class="text-white-50 small text-uppercase font-weight-bold mb-1">Attention Needed</div>
                                <h4 class="font-weight-bold mb-3">{{ $summaryStats['most_pending_category']->name }}</h4>
                                <div class="d-flex align-items-center">
                                    <span
                                        class="display-4 font-weight-bold mr-3">₹{{ number_format(($summaryStats['most_pending_category']->pending_amount) / 1000, 1) }}<small
                                            class="text-white-50">k</small></span>
                                    <div class="text-white-50 line-height-1 small">
                                        Pending<br>Amount
                                    </div>
                                </div>
                            </div>
                            <div class="icon-circle bg-white text-danger shadow-sm" style="width: 60px; height: 60px;">
                                <i class="fas fa-exclamation fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif