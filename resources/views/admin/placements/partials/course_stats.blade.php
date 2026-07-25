<div id="courseStatsContainer">
    @if(count($courseStats) > 0)
    <div class="card shadow mb-4" style="border-radius: 16px;">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-chart-bar mr-1"></i> Course-Wise Placement Statistics</h6>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($courseStats as $cStat)
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="p-3 border rounded bg-light shadow-sm" style="border-radius: 12px !important;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="font-weight-bold text-dark text-truncate" style="max-width: 70%;" title="{{ $cStat->name }}">{{ $cStat->name }}</span>
                                <span class="badge badge-primary px-2 py-1">{{ $cStat->placement_rate }}%</span>
                            </div>
                            <div class="progress mb-2" style="height: 6px; border-radius: 4px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $cStat->placement_rate }}%" aria-valuenow="{{ $cStat->placement_rate }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex justify-content-between text-muted small">
                                <span>Placed: <strong class="text-dark">{{ $cStat->placed_students }}</strong></span>
                                <span>Total: <strong class="text-dark">{{ $cStat->total_students }}</strong></span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
