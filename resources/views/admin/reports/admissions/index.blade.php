@extends('layouts.theme')
@section('title', 'Admissions Funnel Analytics')

@push('styles')
    <style>
        /* --- Modern CRM Design System (Synced with Enquiry Hub) --- */
        :root {
            --crm-primary: #4e73df;
            --crm-secondary: #858796;
        }

        /* --- Stats Grid System --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card-mini {
            background: white;
            padding: 1.25rem;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border-left: 0.25rem solid #e3e6f0;
            transition: transform 0.2s;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-card-mini:hover {
            transform: translateY(-3px);
        }

        .stat-label {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: #5a5c69;
            line-height: 1.2;
        }

        /* Specific Border Colors */
        .border-enquiry {
            border-left-color: #36b9cc !important;
        }

        .border-converted {
            border-left-color: #f6c23e !important;
        }

        .border-admission {
            border-left-color: #4e73df !important;
        }

        .border-approved {
            border-left-color: #1cc88a !important;
        }

        .border-rejected {
            border-left-color: #e74a3b !important;
        }

        /* --- Custom Table Styles --- */
        .table-custom {
            margin: 0;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-custom thead th {
            border: none;
            background: #f8f9fc;
            padding: 1rem;
            border-bottom: 2px solid #e3e6f0;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            color: #858796;
        }

        .table-custom tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid #f0f2f5;
        }

        .progress {
            height: 0.5rem;
            border-radius: 50px;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Admissions Funnel Analytics</h1>
            <div class="d-flex align-items-center">
                <span class="badge badge-light border px-3 py-2 mr-2">
                    <i class="fas fa-calendar-alt mr-1"></i> {{ \Carbon\Carbon::parse($startDate)->format('M d') }} -
                    {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                </span>
            </div>
        </div>

        {{-- Filter Form --}}
        <div class="card shadow mb-4 border-0" style="border-radius: 1rem;">
            <div class="card-body py-3">
                <form method="GET">
                    <div class="row align-items-end">
                        <div class="col-md-5">
                            <label class="small font-weight-bold text-gray-600">Start Date</label>
                            <input type="date" name="start_date" class="form-control bg-light border-0"
                                value="{{ $startDate }}">
                        </div>
                        <div class="col-md-5">
                            <label class="small font-weight-bold text-gray-600">End Date</label>
                            <input type="date" name="end_date" class="form-control bg-light border-0"
                                value="{{ $endDate }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100 shadow-sm font-weight-bold">
                                <i class="fas fa-filter mr-1"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card-mini border-enquiry">
                <div class="stat-label text-info">Total Enquiries</div>
                <div class="stat-value">{{ $totalEnquiries }}</div>
                <div class="small text-muted mt-1">Incoming leads</div>
            </div>
            <div class="stat-card-mini border-converted">
                <div class="stat-label text-warning">Conversions</div>
                <div class="stat-value">{{ $convertedEnquiries }}</div>
                <div class="small text-muted mt-1">{{ $enquiryToAdmissionRate }}% to admission</div>
            </div>
            <div class="stat-card-mini border-admission">
                <div class="stat-label text-primary">Total Admissions</div>
                <div class="stat-value">{{ $totalAdmissions }}</div>
                <div class="small text-muted mt-1">Applications filed</div>
            </div>
            <div class="stat-card-mini border-approved">
                <div class="stat-label text-success">Approved</div>
                <div class="stat-value">{{ $approvedAdmissions }}</div>
                <div class="small text-muted mt-1">{{ $admissionApprovalRate }}% approval rate</div>
            </div>
            <div class="stat-card-mini border-rejected">
                <div class="stat-label text-danger">Overall Yield</div>
                <div class="stat-value">{{ $overallConversionRate }}%</div>
                <div class="small text-muted mt-1">Enquiry to Student</div>
            </div>
        </div>

        <div class="row">
            <!-- Funnel Chart -->
            <div class="col-xl-5 col-lg-6 mb-4">
                <div class="card shadow border-0 h-100" style="border-radius: 1rem;">
                    <div class="card-header py-3 bg-white border-0">
                        <h6 class="m-0 font-weight-bold text-primary">Admission Funnel Drop-off</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area" style="height: 300px;">
                            <canvas id="admissionFunnelChart"></canvas>
                        </div>
                        <div class="mt-4 small text-center">
                            <span class="mr-2"><i class="fas fa-circle text-info"></i> Enrolled</span>
                            <span class="mr-2"><i class="fas fa-circle text-warning"></i> Converted</span>
                            <span class="mr-2"><i class="fas fa-circle text-primary"></i> Applied</span>
                            <span class="mr-2"><i class="fas fa-circle text-success"></i> Approved</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Source Performance Table -->
            <div class="col-xl-7 col-lg-6 mb-4">
                <div class="card shadow border-0 h-100" style="border-radius: 1rem; overflow: hidden;">
                    <div class="card-header py-3 bg-white border-0">
                        <h6 class="m-0 font-weight-bold text-primary">Source Performance Analysis</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>Source</th>
                                        <th class="text-center">Enquiries</th>
                                        <th class="text-center">Admissions</th>
                                        <th class="text-center">Conversion</th>
                                        <th class="text-center">Approval</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($sourceBreakdown as $source)
                                        <tr>
                                            <td class="font-weight-bold text-gray-800">{{ $source->source ?? 'Direct/Other' }}
                                            </td>
                                            <td class="text-center">{{ $source->total_enquiries }}</td>
                                            <td class="text-center">{{ $source->total_admissions }}</td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <span
                                                        class="mr-2 small font-weight-bold">{{ $source->conversion_rate }}%</span>
                                                    <div class="progress w-50">
                                                        <div class="progress-bar bg-warning" role="progressbar"
                                                            style="width: {{ $source->conversion_rate }}%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <span
                                                        class="mr-2 small font-weight-bold">{{ $source->approval_rate }}%</span>
                                                    <div class="progress w-50">
                                                        <div class="progress-bar bg-success" role="progressbar"
                                                            style="width: {{ $source->approval_rate }}%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No data available for the
                                                selected period.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var ctxFunnel = document.getElementById("admissionFunnelChart");
            if (ctxFunnel) {
                new Chart(ctxFunnel, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($funnelLabels) !!},
                        datasets: [{
                            label: 'Count',
                            data: {!! json_encode($funnelData) !!},
                            backgroundColor: ['#36b9cc', '#f6c23e', '#4e73df', '#1cc88a'],
                            hoverBackgroundColor: ['#2c9faf', '#dda20a', '#2e59d9', '#17a673'],
                            borderColor: "rgba(234, 236, 244, 1)",
                            borderWidth: 1,
                            borderRadius: 5,
                            barPercentage: 0.6,
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: "rgb(234, 236, 244)",
                                    drawBorder: false,
                                    borderDash: [2],
                                    zeroLineBorderDash: [2]
                                },
                                ticks: {
                                    stepSize: 1,
                                    padding: 10,
                                }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { padding: 10 }
                            }
                        },
                    },
                });
            }
        });
    </script>
@endpush