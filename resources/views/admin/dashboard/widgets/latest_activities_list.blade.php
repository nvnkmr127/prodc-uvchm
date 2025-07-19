<div class="card shadow h-100">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
    </div>
    <div class="card-body" style="max-height: 350px; overflow-y: auto;">
        <ul class="list-group list-group-flush">
            @forelse($widgetData['latestActivities'] ?? [] as $activity)
                <li class="list-group-item px-0 py-2">
                    <div class="d-flex w-100 justify-content-between">
                        <small class="text-muted">{{ $activity->causer->name ?? 'System' }}</small>
                        <small>{{ $activity->created_at->diffForHumans() }}</small>
                    </div>
                    <p class="mb-0 small">{{ $activity->description }}</p>
                </li>
            @empty
                <li class="list-group-item">No recent activity.</li>
            @endforelse
        </ul>
    </div>
</div>
