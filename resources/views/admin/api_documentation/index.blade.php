@extends('layouts.theme')
@section('title', 'Developer API Hub')

@section('content')
  <div class="developer-hub">
    <!-- Hero Section -->
    <div class="bg-gradient-primary text-white py-5 mb-5 rounded-3 shadow position-relative overflow-hidden">
      <div class="position-absolute top-0 end-0 p-3 opacity-10">
        <i class="fas fa-code fa-10x"></i>
      </div>
      <div class="container-fluid px-4 position-relative">
        <h1 class="display-4 font-weight-bold">Developer Hub</h1>
        <p class="lead mb-4">Build powerful integrations with our secure and scalable API.</p>
        <div class="d-flex gap-3">
          <a href="#swagger-ui" class="btn btn-light btn-lg text-primary font-weight-bold shadow-sm">
            <i class="fas fa-book-reader me-2"></i>Explore Reference
          </a>
          <a href="{{ route('admin.api-tokens.index') }}" class="btn btn-outline-light btn-lg font-weight-bold">
            <i class="fas fa-key me-2"></i>Manage API Keys
          </a>
        </div>
      </div>
    </div>

    <div class="container-fluid px-4">
      <div class="row">
        <!-- Sidebar for Quick Navigation -->
        <div class="col-lg-3 mb-4">
          <div class="sticky-top" style="top: 100px; z-index: 999;">
            <div class="card shadow-sm border-0 mb-4">
              <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-gray-800">
                  <i class="fas fa-compass me-2 text-primary"></i>Quick Navigation
                </h6>
              </div>
              <div class="list-group list-group-flush">
                <a href="#overview" class="list-group-item list-group-item-action border-0 rounded-2 mb-1 active-link"
                  onclick="setActiveLink(this)">
                  <i class="fas fa-info-circle me-3"></i>Overview
                </a>
                <a href="#authentication" class="list-group-item list-group-item-action border-0 rounded-2 mb-1"
                  onclick="setActiveLink(this)">
                  <i class="fas fa-lock me-3"></i>Authentication
                </a>
                <a href="#ratelimits" class="list-group-item list-group-item-action border-0 rounded-2 mb-1"
                  onclick="setActiveLink(this)">
                  <i class="fas fa-tachometer-alt me-3"></i>Rate Limits
                </a>
                <a href="#swagger-ui" class="list-group-item list-group-item-action border-0 rounded-2 mb-1"
                  onclick="setActiveLink(this)">
                  <i class="fas fa-cubes me-3"></i>API Reference
                </a>
                <a href="#support" class="list-group-item list-group-item-action border-0 rounded-2 mb-1"
                  onclick="setActiveLink(this)">
                  <i class="fas fa-life-ring me-3"></i>Support
                </a>
              </div>
            </div>

            <!-- Status Card -->
            <div class="card shadow-sm border-0 border-left-success">
              <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">System Status</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">Operational</div>
                  </div>
                  <div class="icon-circle bg-success text-white">
                    <i class="fas fa-check"></i>
                  </div>
                </div>
                <div class="mt-3">
                  <small class="text-muted">Last checked: <span id="last-checked">Just now</span></small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">

          <!-- Overview Section -->
          <section id="overview" class="mb-5">
            <div class="card shadow-sm border-0">
              <div class="card-body p-4">
                <h3 class="text-gray-800 font-weight-bold mb-4">Introduction</h3>
                <p class="lead text-gray-600">
                  Welcome to the School Management System API. Our RESTful API provides programmable access to student
                  data, attendance records, and administrative functions.
                </p>
                <div class="row mt-4">
                  <div class="col-md-4">
                    <div class="p-3 bg-light rounded-3 h-100">
                      <div class="text-primary mb-3"><i class="fas fa-cogs fa-2x"></i></div>
                      <h5 class="font-weight-bold">Robust & Scalable</h5>
                      <p class="text-secondary small mb-0">Built on modern standards to handle high-volume requests
                        reliably.</p>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="p-3 bg-light rounded-3 h-100">
                      <div class="text-success mb-3"><i class="fas fa-shield-alt fa-2x"></i></div>
                      <h5 class="font-weight-bold">Secure by Design</h5>
                      <p class="text-secondary small mb-0">Token-based authentication ensures your data remains protected.
                      </p>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="p-3 bg-light rounded-3 h-100">
                      <div class="text-info mb-3"><i class="fas fa-sync fa-2x"></i></div>
                      <h5 class="font-weight-bold">Real-time Data</h5>
                      <p class="text-secondary small mb-0">Access up-to-the-minute attendance and activity data.</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- Authentication Section -->
          <section id="authentication" class="mb-5">
            <div class="card shadow-sm border-0 overflow-hidden">
              <div class="card-header bg-white py-3 d-flex align-items-center">
                <div class="icon-circle bg-primary-subtle text-primary me-3">
                   <i class="fas fa-lock"></i>
                </div>
                <h5 class="m-0 font-weight-bold text-gray-800">Authentication</h5>
              </div>
              <div class="card-body p-4">
                <p class="text-gray-600 mb-4">Verify your identity using <strong>Bearer Tokens</strong>. We use Laravel Sanctum for secure managed access. Include your API token in the
                  <code>Authorization</code> header of every request.
                </p>

                <div class="bg-dark text-white p-4 rounded-3 mb-4 font-monospace position-relative group shadow-lg">
                  <div class="small text-muted mb-2 text-uppercase font-weight-bold">Request Header</div>
                  <button class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-3 copy-btn border-0"
                    onclick="copyToClipboard('Authorization: Bearer YOUR_API_TOKEN', this)">
                    <i class="fas fa-copy"></i>
                  </button>
                  <code class="text-info">Authorization: <span class="text-success">Bearer</span> <span class="text-warning">YOUR_API_TOKEN</span></code>
                </div>

                <div class="alert alert-warning border-0 shadow-sm rounded-3">
                  <div class="d-flex align-items-center">
                    <div class="me-3 h4 mb-0"><i class="fas fa-exclamation-triangle text-warning"></i></div>
                    <div>
                      <strong class="text-dark">Security Note:</strong> Never share your API tokens in client-side code (JavaScript) or public repositories. You can manage and revoke tokens in the <a
                      href="{{ route('admin.api-tokens.index') }}" class="alert-link">API Tokens dashboard</a>.
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- Rate Limits Section -->
          <section id="ratelimits" class="mb-5">
            <div class="card shadow-sm border-0">
              <div class="card-header bg-white py-3">
                <h5 class="m-0 font-weight-bold text-primary">Rate Limits</h5>
              </div>
              <div class="card-body p-4">
                <p>To ensure system stability, requests are rate-limited per token. Headers are included in every response
                  to track your current usage.</p>
                <div class="table-responsive">
                  <table class="table table-bordered">
                    <thead class="bg-light">
                      <tr>
                        <th>Limit Type</th>
                        <th>Standard</th>
                        <th>Write Operations</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>Requests per Minute</td>
                        <td><span class="badge bg-primary">60</span></td>
                        <td><span class="badge bg-warning text-dark">30</span></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </section>

          <!-- API Reference (Swagger UI) -->
          <section id="swagger-ui-container" class="mb-5 position-relative">
            <div class="card shadow-lg border-0">
              <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="m-0 font-weight-bold"><i class="fas fa-terminal me-2"></i>API Reference</h5>
                <span class="badge bg-success">v1.0.0</span>
              </div>
              <div class="card-body p-0">
                <!-- Swagger UI Element -->
                <div id="swagger-ui"></div>
              </div>
            </div>
          </section>

          <!-- Support Section -->
          <section id="support" class="mb-5">
            <div class="card shadow-sm border-0 bg-primary text-white">
              <div class="card-body p-5 text-center">
                <h3 class="font-weight-bold mb-3">Need Help?</h3>
                <p class="lead mb-4">Our engineering team is here to support your integration journey.</p>
                <div class="d-flex justify-content-center gap-3">
                  <a href="mailto:support@uvchm.com" class="btn btn-light btn-lg font-weight-bold">
                    <i class="fas fa-envelope me-2"></i>Contact Support
                  </a>
                  <a href="#" class="btn btn-outline-light btn-lg font-weight-bold">
                    <i class="fab fa-discord me-2"></i>Join Community
                  </a>
                </div>
              </div>
            </div>
          </section>

        </div>
      </div>
    </div>
  </div>
@endsection

@push('styles')
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.18.3/swagger-ui.css">
  <style>
    .developer-hub .bg-gradient-primary {
      background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    }

    .active-link {
      background-color: #f8f9fa !important;
      border-right: 4px solid #4e73df !important;
      color: #4e73df !important;
      font-weight: 600;
    }

    .bg-primary-subtle {
      background-color: rgba(78, 115, 223, 0.1);
    }

    .icon-circle {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* Code Block Styling */
    code {
      color: #e83e8c;
    }

    /* Swagger UI Custom Overrides for Premium Look */
    .swagger-ui .wrapper {
      padding: 0;
    }

    .swagger-ui .info {
      margin: 20px 0;
      padding: 0 20px;
    }

    .swagger-ui .scheme-container {
      background: #f8f9fc;
      box-shadow: none;
      border-bottom: 1px solid #e3e6f0;
      padding: 20px;
    }

    .swagger-ui .opblock {
      border-radius: 12px;
      border: none;
      box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
      margin-bottom: 20px;
      overflow: hidden;
    }

    .swagger-ui .opblock .opblock-summary {
      padding: 15px 20px;
    }

    .swagger-ui .btn.execute {
      background-color: #4e73df;
      color: white;
      border-color: #4e73df;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .swagger-ui .btn.execute:hover {
      background-color: #2e59d9;
    }

    .swagger-ui .model-box {
        border-radius: 8px;
        background: #f8f9fc;
        padding: 15px;
    }
  </style>
@endpush

@push('scripts')
  <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.18.3/swagger-ui-bundle.js"> </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.18.3/swagger-ui-standalone-preset.js"> </script>
  <script>
    window.onload = function () {
      const ui = SwaggerUIBundle({
        url: "{{ route('admin.api-documentation.json') }}",
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
          SwaggerUIBundle.presets.apis,
          SwaggerUIStandalonePreset
        ],
        plugins: [
          SwaggerUIBundle.plugins.DownloadUrl
        ],
        layout: "BaseLayout",
        docExpansion: 'list',
        filter: true,
        persistAuthorization: true,
        displayRequestDuration: true
      });
      window.ui = ui;

      // Check system health
      checkSystemStatus();
    };

    function checkSystemStatus() {
        const pingUrl = "{{ url('/api/ping') }}";
        fetch(pingUrl)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('last-checked').innerText = 'Online - ' + new Date().toLocaleTimeString();
                }
            })
            .catch(() => {
                const statusCard = document.querySelector('.border-left-success');
                statusCard.classList.replace('border-left-success', 'border-left-danger');
                statusCard.querySelector('.text-success').classList.replace('text-success', 'text-danger');
                statusCard.querySelector('.bg-success').classList.replace('bg-success', 'text-danger');
                statusCard.querySelector('.text-gray-800').innerText = 'Issues Detected';
                document.getElementById('last-checked').innerText = 'Offline';
            });
    }

    function setActiveLink(element) {
      document.querySelectorAll('.list-group-item').forEach(el => {
        el.classList.remove('active-link');
      });
      element.classList.add('active-link');
    }

    function copyToClipboard(text, btn) {
        const originalHtml = btn.innerHTML;
        navigator.clipboard.writeText(text).then(() => {
            btn.innerHTML = '<i class="fas fa-check text-success"></i>';
            setTimeout(() => {
                btn.innerHTML = originalHtml;
            }, 2000);
        });
    }
  </script>
@endpush