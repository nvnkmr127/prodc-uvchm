

@extends('admin.theme')

@section('title', 'Payment Gateways')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Payment Gateway Configuration</h3>
                </div>
                <div class="card-body">
                    
                    {{-- Gateway Statistics --}}
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-credit-card"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Transactions</span>
                                    <span class="info-box-number">{{ number_format($stats['total_transactions'] ?? 0) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-rupee-sign"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Amount</span>
                                    <span class="info-box-number">₹{{ number_format($stats['total_amount'] ?? 0, 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-calendar"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">This Month</span>
                                    <span class="info-box-number">{{ number_format($stats['this_month_transactions'] ?? 0) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary"><i class="fas fa-chart-line"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Monthly Amount</span>
                                    <span class="info-box-number">₹{{ number_format($stats['this_month_amount'] ?? 0, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Gateways --}}
                    <div class="row">
                        @foreach($gateways as $gatewayKey => $gateway)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">{{ $gateway['name'] }}</h5>
                                    <span class="badge {{ $gateway['enabled'] ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $gateway['enabled'] ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong>Status:</strong> 
                                        <span class="text-{{ $gateway['enabled'] ? 'success' : 'muted' }}">
                                            {{ $gateway['enabled'] ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Mode:</strong> 
                                        <span class="badge {{ ($gateway['test_mode'] ?? true) ? 'badge-warning' : 'badge-success' }}">
                                            {{ ($gateway['test_mode'] ?? true) ? 'Test' : 'Live' }}
                                        </span>
                                    </div>

                                    <div class="mb-3">
                                        <strong>Supported Methods:</strong><br>
                                        @foreach($gateway['supported_methods'] ?? [] as $method)
                                            <small class="badge badge-info mr-1">{{ ucfirst($method) }}</small>
                                        @endforeach
                                    </div>

                                    @if($gateway['enabled'])
                                        <button class="btn btn-sm btn-outline-primary test-gateway" 
                                                data-gateway="{{ $gatewayKey }}">
                                            <i class="fas fa-check-circle"></i> Test Connection
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Configuration Instructions --}}
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Configuration Instructions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="accordion" id="configAccordion">
                                        
                                        {{-- Razorpay Instructions --}}
                                        <div class="card">
                                            <div class="card-header" id="razorpayHeading">
                                                <h6 class="mb-0">
                                                    <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#razorpayCollapse">
                                                        Razorpay Configuration
                                                    </button>
                                                </h6>
                                            </div>
                                            <div id="razorpayCollapse" class="collapse" data-parent="#configAccordion">
                                                <div class="card-body">
                                                    <ol>
                                                        <li>Sign up at <a href="https://razorpay.com" target="_blank">Razorpay.com</a></li>
                                                        <li>Get your Key ID and Key Secret from Dashboard → Account & Settings → API Keys</li>
                                                        <li>Configure webhook URL: <code>{{ route('webhook.razorpay') ?? url('/webhook/razorpay') }}</code></li>
                                                        <li>Enable events: payment.captured, payment.failed, order.paid</li>
                                                        <li>Add credentials to Settings → Payment Gateways</li>
                                                    </ol>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- PayU Instructions --}}
                                        <div class="card">
                                            <div class="card-header" id="payuHeading">
                                                <h6 class="mb-0">
                                                    <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#payuCollapse">
                                                        PayU Configuration
                                                    </button>
                                                </h6>
                                            </div>
                                            <div id="payuCollapse" class="collapse" data-parent="#configAccordion">
                                                <div class="card-body">
                                                    <ol>
                                                        <li>Sign up at <a href="https://www.payu.in" target="_blank">PayU.in</a></li>
                                                        <li>Get Merchant Key and Salt from your PayU dashboard</li>
                                                        <li>Configure return URL and webhook URL in PayU dashboard</li>
                                                        <li>Test with test credentials first</li>
                                                        <li>Add credentials to Settings → Payment Gateways</li>
                                                    </ol>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- PhonePe Instructions --}}
                                        <div class="card">
                                            <div class="card-header" id="phonepeHeading">
                                                <h6 class="mb-0">
                                                    <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#phonepeCollapse">
                                                        PhonePe Configuration
                                                    </button>
                                                </h6>
                                            </div>
                                            <div id="phonepeCollapse" class="collapse" data-parent="#configAccordion">
                                                <div class="card-body">
                                                    <ol>
                                                        <li>Apply for PhonePe merchant account</li>
                                                        <li>Get Merchant ID and API Key</li>
                                                        <li>Configure webhook URLs for payment status</li>
                                                        <li>Test with UAT environment first</li>
                                                        <li>Add credentials to Settings → Payment Gateways</li>
                                                    </ol>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- Test Results Modal --}}
<div class="modal fade" id="testResultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gateway Test Results</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="testResults"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Test gateway connection
    $('.test-gateway').click(function() {
        const gateway = $(this).data('gateway');
        const button = $(this);
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Testing...');
        
        $.ajax({
            url: '{{ route("admin.integrations.test-gateway", ":gateway") }}'.replace(':gateway', gateway),
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                showTestResults(gateway, response);
            },
            error: function(xhr) {
                showTestResults(gateway, {
                    success: false,
                    message: 'Test failed: ' + xhr.responseText
                });
            },
            complete: function() {
                button.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Test Connection');
            }
        });
    });
    
    function showTestResults(gateway, result) {
        const status = result.success ? 'success' : 'danger';
        const icon = result.success ? 'check-circle' : 'times-circle';
        
        let html = `
            <div class="alert alert-${status}">
                <h6><i class="fas fa-${icon}"></i> ${gateway.toUpperCase()} Test Result</h6>
                <p>${result.message}</p>
        `;
        
        if (result.data) {
            html += '<hr><strong>Details:</strong><ul>';
            for (const [key, value] of Object.entries(result.data)) {
                html += `<li><strong>${key.replace(/_/g, ' ').toUpperCase()}:</strong> ${value}</li>`;
            }
            html += '</ul>';
        }
        
        html += '</div>';
        
        $('#testResults').html(html);
        $('#testResultModal').modal('show');
    }
});
</script>
@endpush