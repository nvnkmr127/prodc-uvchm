@extends('layouts.theme')
@section('title', 'Activity Protocol Analysis')

@push('styles')
    <style>
        .analysis-card {
            border: none; border-radius: 2rem; background: #fff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden;
        }
        .protocol-header {
            background: linear-gradient(135deg, #1a2a6c 0%, #b21f1f 50%, #fdbb2d 100%);
            color: white; padding: 3rem 2rem; position: relative;
        }
        .protocol-header::after {
            content: 'DEEP SCAN'; position: absolute; top: 1rem; right: 2rem;
            font-size: 5rem; font-weight: 900; opacity: 0.1; letter-spacing: -2px;
        }
        .entity-avatar {
            width: 120px; height: 120px; border-radius: 2.5rem; background: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 3rem; font-weight: 900; color: #1a2a6c;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2); border: 5px solid rgba(255,255,255,0.3);
        }
        .metric-pill {
            background: #f8f9fc; border-radius: 1.25rem; padding: 1.5rem;
            border: 1px solid #eaecf4; height: 100%; transition: all 0.3s;
        }
        .metric-pill:hover { transform: translateY(-5px); border-color: #4e73df; }
        .data-label { font-size: 0.7rem; font-weight: 800; color: #858796; text-transform: uppercase; letter-spacing: 1px; }
        .data-value { font-size: 1.15rem; font-weight: 800; color: #1a2a6c; }
        
        pre.metadata-viewer {
            background: #1e272e; color: #00d8d6; padding: 2rem;
            border-radius: 1.5rem; font-family: 'Fira Code', monospace;
            font-size: 0.9rem; border: 1px solid #485460;
            box-shadow: inset 0 2px 10px rgba(0,0,0,0.5);
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.student-portal-logs.index') }}">Data Base</a></li>
                    <li class="breadcrumb-item active">Protocol Analysis</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Event Deep Scan</h1>
        </div>
        <a href="{{ route('admin.student-portal-logs.index') }}" class="btn btn-light rounded-pill px-4 font-weight-bold shadow-sm">
            <i class="fas fa-arrow-left mr-2"></i> RETURN TO DATABASE
        </a>
    </div>

    <div class="analysis-card shadow-lg mb-5">
        <div class="protocol-header">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="entity-avatar animate__animated animate__fadeIn">
                        {{ substr($log->student->name ?? '?', 0, 1) }}
                    </div>
                </div>
                <div class="col ml-4">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge badge-pill badge-light text-dark font-weight-extrabold px-3 py-1 mr-3" style="font-size: 0.65rem;">PROTOCOL ID: #{{ $log->id }}</span>
                        @if($log->is_suspicious)
                            <span class="badge badge-pill badge-danger font-weight-extrabold px-3 py-1 animate__animated animate__pulse animate__infinite" style="font-size: 0.65rem;">SECURITY BREACH DETECTED</span>
                        @else
                            <span class="badge badge-pill badge-success font-weight-extrabold px-3 py-1" style="font-size: 0.65rem;">INTEGRITY VERIFIED</span>
                        @endif
                    </div>
                    <h2 class="font-weight-extrabold mb-1" style="letter-spacing: -1px; font-size: 2.5rem;">{{ $log->student->name ?? 'UNKNOWN ENTITY' }}</h2>
                    <p class="mb-0 opacity-75 font-weight-bold text-uppercase tracking-widest">Enrollment: {{ $log->student->enrollment_number ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <div class="card-body p-5">
            <div class="row mb-5">
                <div class="col-md-3 mb-4">
                    <div class="metric-pill">
                        <div class="data-label">Action Origin</div>
                        <div class="data-value mt-2 text-uppercase">{{ str_replace('_', ' ', $log->action) }}</div>
                        <div class="text-xs text-muted font-weight-bold mt-1">Authenticated Operation</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="metric-pill">
                        <div class="data-label">Network Entry</div>
                        <div class="data-value mt-2"><code>{{ $log->ip_address }}</code></div>
                        <div class="text-xs text-muted font-weight-bold mt-1">VLAN/WAN Gateway</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="metric-pill">
                        <div class="data-label">Temporal Mark</div>
                        <div class="data-value mt-2">{{ $log->created_at->format('M d, Y') }}</div>
                        <div class="text-xs text-muted font-weight-bold mt-1">Clock: {{ $log->created_at->format('H:i:s') }}</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="metric-pill">
                        <div class="data-label">Device Link</div>
                        <div class="data-value mt-2">{{ $log->mobile_number ?: 'NOT SUPPLIED' }}</div>
                        <div class="text-xs text-muted font-weight-bold mt-1">Primary Auth Mobile</div>
                    </div>
                </div>
            </div>

            @if($log->is_suspicious)
                <div class="alert alert-danger border-0 shadow-sm p-4 mb-5" style="border-radius: 1.5rem;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-white text-danger d-flex align-items-center justify-content-center shadow-sm mr-3" style="width: 40px; height: 40px;">
                            <i class="fas fa-biohazard fa-lg"></i>
                        </div>
                        <h5 class="mb-0 font-weight-bold">Security Violation Protocol Triggered</h5>
                    </div>
                    <p class="mb-0 font-weight-extrabold h6" style="letter-spacing: 0.5px;">REASON: {{ $log->flagged_reason }}</p>
                </div>
            @endif

            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-none border bg-light" style="border-radius: 1.5rem;">
                        <div class="card-header bg-transparent border-0 pt-4 px-4">
                            <h6 class="font-weight-extrabold text-primary text-uppercase small tracking-widest">Geolocation Matrix</h6>
                        </div>
                        <div class="card-body p-4">
                            @if($log->location_data)
                                <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                    <span class="font-weight-bold text-muted">City/Region</span>
                                    <span class="font-weight-extrabold text-dark text-uppercase">{{ $log->location_data['city'] ?? 'Unknown' }} / {{ $log->location_data['region'] ?? 'N/A' }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                    <span class="font-weight-bold text-muted">Country</span>
                                    <span class="font-weight-extrabold text-dark text-uppercase">{{ $log->location_data['country'] ?? 'Unknown' }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                    <span class="font-weight-bold text-muted">ISP / Network Agency</span>
                                    <span class="font-weight-extrabold text-dark">{{ $log->location_data['org'] ?? 'Direct Entry' }}</span>
                                </div>
                                <div class="d-flex justify-content-between text-xs font-weight-bold opacity-75">
                                    <span>Lat: {{ $log->location_data['lat'] ?? '--' }}</span>
                                    <span>Lon: {{ $log->location_data['lon'] ?? '--' }}</span>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-satellite-dish fa-3x text-muted mb-3"></i>
                                    <h6 class="font-weight-bold text-muted">GEOLOCATION DATA SHIELDED</h6>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="p-0">
                        <h6 class="font-weight-extrabold text-primary text-uppercase small tracking-widest mb-3 pl-2">System Payload (RAW)</h6>
                        <pre class="metadata-viewer mb-0 shadow-lg">{{ json_encode($log->metadata, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
