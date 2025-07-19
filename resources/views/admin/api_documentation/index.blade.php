@extends('layouts.theme')
@section('title', 'API Documentation')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-book text-primary"></i> API Documentation
        </h1>
        <div class="btn-group">
            <a href="{{ route('admin.api-tokens.index') }}" class="btn btn-primary">
                <i class="fas fa-key"></i> Manage Tokens
            </a>
            <a href="/api/documentation" target="_blank" class="btn btn-success">
                <i class="fas fa-external-link-alt"></i> View Swagger Docs
            </a>
        </div>
    </div>

    <!-- Quick Start Guide -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-rocket me-2"></i>Quick Start Guide
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-primary">
                                <span class="badge badge-primary me-2">1</span>Generate API Token
                            </h6>
                            <p class="text-muted mb-3">Create an API token for your application to authenticate requests.</p>
                            
                            <h6 class="font-weight-bold text-primary">
                                <span class="badge badge-primary me-2">2</span>Make API Calls
                            </h6>
                            <p class="text-muted mb-3">Include your token in the Authorization header of your requests.</p>
                            
                            <h6 class="font-weight-bold text-primary">
                                <span class="badge badge-primary me-2">3</span>Handle Responses
                            </h6>
                            <p class="text-muted">All API responses are in JSON format with consistent structure.</p>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light p-3 rounded">
                                <h6 class="font-weight-bold mb-3">Sample API Request</h6>
                                <pre class="bg-dark text-light p-3 rounded"><code>curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     https://uvchm.digicloudify.com/api/v1/test</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Available Endpoints -->
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white py-3">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-sitemap me-2"></i>Available Endpoints
                    </h6>
                </div>
                <div class="card-body">
                    <div class="accordion" id="endpointsAccordion">
                        <!-- Authentication Endpoints -->
                        <div class="card">
                            <div class="card-header" id="authHeader">
                                <h6 class="mb-0">
                                    <button class="btn btn-link font-weight-bold text-decoration-none" type="button" 
                                            data-toggle="collapse" data-target="#authCollapse">
                                        <i class="fas fa-lock text-primary"></i> Authentication & Testing
                                        <span class="badge badge-primary ml-2">3 endpoints</span>
                                    </button>
                                </h6>
                            </div>
                            <div id="authCollapse" class="collapse show" data-parent="#endpointsAccordion">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><span class="badge badge-success">GET</span></td>
                                                <td><code>/api/v1/test</code></td>
                                                <td>Test API authentication</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge badge-success">GET</span></td>
                                                <td><code>/api/v1/profile</code></td>
                                                <td>Get user profile information</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge badge-success">GET</span></td>
                                                <td><code>/api/v1/restricted-test</code></td>
                                                <td>Test restricted endpoint with abilities</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Student Management -->
                        <div class="card">
                            <div class="card-header" id="studentsHeader">
                                <h6 class="mb-0">
                                    <button class="btn btn-link font-weight-bold text-decoration-none collapsed" type="button" 
                                            data-toggle="collapse" data-target="#studentsCollapse">
                                        <i class="fas fa-user-graduate text-info"></i> Student Management
                                        <span class="badge badge-info ml-2">6 endpoints</span>
                                    </button>
                                </h6>
                            </div>
                            <div id="studentsCollapse" class="collapse" data-parent="#endpointsAccordion">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><span class="badge badge-success">GET</span></td>
                                                <td><code>/api/v1/students/search</code></td>
                                                <td>Search students by name, enrollment number, or mobile</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge badge-success">GET</span></td>
                                                <td><code>/api/v1/students/{id}</code></td>
                                                <td>Get student details</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge badge-success">GET</span></td>
                                                <td><code>/api/v1/students/{id}/profile</code></td>
                                                <td>Get comprehensive student profile</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge badge-success">GET</span></td>
                                                <td><code>/api/v1/students/{id}/attendance</code></td>
                                                <td>Get student attendance records</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge badge-success">GET</span></td>
                                                <td><code>/api/v1/students/{id}/financials</code></td>
                                                <td>Get student financial information</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge badge-warning">PUT</span></td>
                                                <td><code>/api/v1/students/{id}/profile</code></td>
                                                <td>Update student profile (limited fields)</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Attendance Management -->
                        <div class="card">
                            <div class="card-header" id="attendanceHeader">
                                <h6 class="mb-0">
                                    <button class="btn btn-link font-weight-bold text-decoration-none collapsed" type="button" 
                                            data-toggle="collapse" data-target="#attendanceCollapse">
                                        <i class="fas fa-calendar-check text-success"></i> Attendance Management
                                        <span class="badge badge-success ml-2">3 endpoints</span>
                                    </button>
                                </h6>
                            </div>
                            <div id="attendanceCollapse" class="collapse" data-parent="#endpointsAccordion">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><span class="badge badge-danger">POST</span></td>
                                                <td><code>/api/v1/attendance</code></td>
                                                <td>Submit attendance record (biometric/manual)</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge badge-success">GET</span></td>
                                                <td><code>/api/v1/attendance/today</code></td>
                                                <td>Get today's attendance records</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge badge-success">GET</span></td>
                                                <td><code>/api/v1/attendance/student/{id}</code></td>
                                                <td>Get attendance for specific student</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dashboard & Analytics -->
                        <div class="card">
                            <div class="card-header" id="dashboardHeader">
                                <h6 class="mb-0">
                                    <button class="btn btn-link font-weight-bold text-decoration-none collapsed" type="button" 
                                            data-toggle="collapse" data-target="#dashboardCollapse">
                                        <i class="fas fa-chart-bar text-warning"></i> Dashboard & Analytics
                                        <span class="badge badge-warning ml-2">3 endpoints</span>
                                    </button>
                                </h6>
                            </div>
                            <div id="dashboardCollapse" class="collapse" data-parent="#endpointsAccordion">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><span class="badge badge-success">GET</span></td>
                                                <td><code>/api/v1/dashboard/stats</code></td>
                                                <td>Get comprehensive dashboard statistics</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge badge-success">GET</span></td>
                                                <td><code>/api/v1/dashboard/attendance-trends</code></td>
                                                <td>Get attendance trends for charts</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge badge-success">GET</span></td>
                                                <td><code>/api/v1/dashboard/financial-trends</code></td>
                                                <td>Get financial trends and analytics</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Admin Operations -->
                        <div class="card">
                            <div class="card-header" id="adminHeader">
                                <h6 class="mb-0">
                                    <button class="btn btn-link font-weight-bold text-decoration-none collapsed" type="button" 
                                            data-toggle="collapse" data-target="#adminCollapse">
                                        <i class="fas fa-user-shield text-danger"></i> Admin Operations
                                        <span class="badge badge-danger ml-2">Admin Only</span>
                                    </button>
                                </h6>
                            </div>
                            <div id="adminCollapse" class="collapse" data-parent="#endpointsAccordion">
                                <div class="card-body">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <strong>Admin Access Required:</strong> These endpoints require admin, college-admin, or super-admin roles.
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><span class="badge badge-danger">POST</span></td>
                                                <td><code>/api/v1/admin/students</code></td>
                                                <td>Create new student</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge badge-success">GET</span></td>
                                                <td><code>/api/v1/admin/batches</code></td>
                                                <td>Get all batches</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge badge-success">GET</span></td>
                                                <td><code>/api/v1/admin/reports/attendance</code></td>
                                                <td>Generate attendance reports</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar with Resources -->
        <div class="col-lg-4">
            <!-- API Status Card -->
            <div class="card shadow mb-4 border-left-success">
                <div class="card-header bg-success text-white py-3">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-server me-2"></i>API Status
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-grow-1">
                            <div class="small font-weight-bold text-success text-uppercase mb-1">Server Status</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                <i class="fas fa-circle text-success"></i> Online
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-grow-1">
                            <div class="small font-weight-bold text-primary text-uppercase mb-1">API Version</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">v1.0.0</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="small font-weight-bold text-info text-uppercase mb-1">Response Time</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">~120ms</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Resources -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tools me-2"></i>Developer Resources
                    </h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="/api/documentation" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-book text-primary me-2"></i>
                                <strong>Swagger Documentation</strong>
                                <br><small class="text-muted">Interactive API explorer</small>
                            </div>
                            <i class="fas fa-external-link-alt text-muted"></i>
                        </a>
                        <a href="/postman-collection.json" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-download text-success me-2"></i>
                                <strong>Postman Collection</strong>
                                <br><small class="text-muted">Ready-to-use API collection</small>
                            </div>
                            <i class="fas fa-download text-muted"></i>
                        </a>
                        <a href="#" onclick="showSampleCode()" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-code text-warning me-2"></i>
                                <strong>Code Examples</strong>
                                <br><small class="text-muted">Sample implementations</small>
                            </div>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        <a href="{{ route('admin.api-tokens.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-key text-info me-2"></i>
                                <strong>Manage API Tokens</strong>
                                <br><small class="text-muted">Create and manage tokens</small>
                            </div>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Authentication Info -->
            <div class="card shadow mb-4 border-left-warning">
                <div class="card-header bg-warning text-white py-3">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-shield-alt me-2"></i>Authentication
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-3">All API requests require authentication using Laravel Sanctum tokens.</p>
                    <h6 class="font-weight-bold mb-2">Header Format:</h6>
                    <div class="bg-light p-2 rounded mb-3">
                        <code>Authorization: Bearer YOUR_TOKEN</code>
                    </div>
                    <h6 class="font-weight-bold mb-2">Token Abilities:</h6>
                    <ul class="small mb-0">
                        <li><code>*</code> - All permissions</li>
                        <li><code>read</code> - Read access only</li>
                        <li><code>write</code> - Write access</li>
                        <li><code>attendance</code> - Attendance operations</li>
                        <li><code>students</code> - Student management</li>
                        <li><code>reports</code> - Generate reports</li>
                    </ul>
                </div>
            </div>

            <!-- Rate Limiting Info -->
            <div class="card shadow mb-4 border-left-info">
                <div class="card-header bg-info text-white py-3">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-tachometer-alt me-2"></i>Rate Limiting
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="font-weight-bold text-primary">60</div>
                            <div class="small text-muted">Requests/min<br>Standard Users</div>
                        </div>
                        <div class="col-6">
                            <div class="font-weight-bold text-success">120</div>
                            <div class="small text-muted">Requests/min<br>Admin Users</div>
                        </div>
                    </div>
                    <hr>
                    <p class="small mb-0 text-muted">
                        Rate limits are per token. Exceeding limits will result in 429 Too Many Requests responses.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Code Examples Modal -->
<div class="modal fade" id="codeExamplesModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-code me-2"></i>Code Examples
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="codeTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="curl-tab" data-bs-toggle="tab" data-bs-target="#curl" type="button">cURL</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="php-tab" data-bs-toggle="tab" data-bs-target="#php" type="button">PHP</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="javascript-tab" data-bs-toggle="tab" data-bs-target="#javascript" type="button">JavaScript</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="python-tab" data-bs-toggle="tab" data-bs-target="#python" type="button">Python</button>
                    </li>
                </ul>
                <div class="tab-content" id="codeTabContent">
                    <div class="tab-pane fade show active" id="curl" role="tabpanel">
                        <pre class="bg-dark text-light p-3 rounded mt-3"><code># Test API Authentication
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     https://uvchm.digicloudify.com/api/v1/test

# Search Students
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     "https://uvchm.digicloudify.com/api/v1/students/search?q=john"

# Submit Attendance
curl -X POST \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -H "X-API-KEY: your-biometric-key" \
     -d '{"enrollment_number": "ENR-12345678"}' \
     https://uvchm.digicloudify.com/api/v1/attendance</code></pre>
                    </div>
                    <div class="tab-pane fade" id="php" role="tabpanel">
                        <pre class="bg-dark text-light p-3 rounded mt-3"><code>&lt;?php
$token = 'YOUR_TOKEN';
$baseUrl = 'https://uvchm.digicloudify.com/api/v1';

$headers = [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
    'Content-Type: application/json'
];

// Test API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/test');
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
echo "User: " . $data['data']['user_name'];
?&gt;</code></pre>
                    </div>
                    <div class="tab-pane fade" id="javascript" role="tabpanel">
                        <pre class="bg-dark text-light p-3 rounded mt-3"><code>const token = 'YOUR_TOKEN';
const baseUrl = 'https://uvchm.digicloudify.com/api/v1';

// Test API
async function testAPI() {
    try {
        const response = await fetch(`${baseUrl}/test`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        console.log('User:', data.data.user_name);
    } catch (error) {
        console.error('Error:', error);
    }
}

// Search Students
async function searchStudents(query) {
    const response = await fetch(`${baseUrl}/students/search?q=${query}`, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        }
    });
    
    return await response.json();
}</code></pre>
                    </div>
                    <div class="tab-pane fade" id="python" role="tabpanel">
                        <pre class="bg-dark text-light p-3 rounded mt-3"><code>import requests

token = 'YOUR_TOKEN'
base_url = 'https://uvchm.digicloudify.com/api/v1'

headers = {
    'Authorization': f'Bearer {token}',
    'Accept': 'application/json',
    'Content-Type': 'application/json'
}

# Test API
response = requests.get(f'{base_url}/test', headers=headers)
data = response.json()
print(f"User: {data['data']['user_name']}")

# Search Students
def search_students(query):
    response = requests.get(
        f'{base_url}/students/search',
        headers=headers,
        params={'q': query}
    )
    return response.json()

# Submit Attendance
def submit_attendance(enrollment_number):
    data = {'enrollment_number': enrollment_number}
    response = requests.post(
        f'{base_url}/attendance',
        headers=headers,
        json=data
    )
    return response.json()</code></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function showSampleCode() {
        $('#codeExamplesModal').modal('show');
    }

    // Auto-refresh API status every 30 seconds
    setInterval(function() {
        // You can implement actual API health check here
        console.log('API health check...');
    }, 30000);
</script>
@endpush