<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GeneratePostmanCollection extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'api:postman {--output=postman-collection.json : Output file path}';

    /**
     * The console command description.
     */
    protected $description = 'Generate Postman collection from API routes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating Postman collection...');

        $baseUrl = config('app.url', 'https://uvchm.digicloudify.com');

        $collection = [
            'info' => [
                '_postman_id' => 'school-api-'.uniqid(),
                'name' => 'School Management System API',
                'description' => 'Complete API collection for School Management System',
                'version' => '1.0.0',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'variable' => [
                [
                    'key' => 'base_url',
                    'value' => $baseUrl,
                    'type' => 'string',
                ],
                [
                    'key' => 'api_token',
                    'value' => 'YOUR_API_TOKEN_HERE',
                    'type' => 'string',
                ],
                [
                    'key' => 'biometric_api_key',
                    'value' => 'your-biometric-key',
                    'type' => 'string',
                ],
            ],
            'item' => [
                $this->getAuthenticationFolder(),
                $this->getStudentsFolder(),
                $this->getAttendanceFolder(),
                $this->getDashboardFolder(),
                $this->getAdminFolder(),
            ],
        ];

        $outputPath = $this->option('output');

        // Ensure the directory exists
        $directory = dirname($outputPath);
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Write the collection file
        File::put($outputPath, json_encode($collection, JSON_PRETTY_PRINT));

        $this->info('✅ Postman collection generated successfully!');
        $this->line("📁 File location: {$outputPath}");
        $this->line('🌐 You can now import this file into Postman');

        return 0;
    }

    private function getAuthenticationFolder()
    {
        return [
            'name' => '🔐 Authentication',
            'description' => 'API authentication and testing endpoints',
            'item' => [
                [
                    'name' => 'Test API Authentication',
                    'event' => [
                        [
                            'listen' => 'test',
                            'script' => [
                                'exec' => [
                                    'pm.test("Status code is 200", function () {',
                                    '    pm.response.to.have.status(200);',
                                    '});',
                                    '',
                                    'pm.test("Response has success property", function () {',
                                    '    const jsonData = pm.response.json();',
                                    '    pm.expect(jsonData).to.have.property("success");',
                                    '    pm.expect(jsonData.success).to.eql(true);',
                                    '});',
                                ],
                                'type' => 'text/javascript',
                            ],
                        ],
                    ],
                    'request' => [
                        'method' => 'GET',
                        'header' => [
                            [
                                'key' => 'Authorization',
                                'value' => 'Bearer {{api_token}}',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'Accept',
                                'value' => 'application/json',
                                'type' => 'text',
                            ],
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/api/v1/test',
                            'host' => ['{{base_url}}'],
                            'path' => ['api', 'v1', 'test'],
                        ],
                        'description' => 'Test endpoint to verify API authentication is working',
                    ],
                    'response' => [],
                ],
                [
                    'name' => 'Get User Profile',
                    'request' => [
                        'method' => 'GET',
                        'header' => [
                            [
                                'key' => 'Authorization',
                                'value' => 'Bearer {{api_token}}',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'Accept',
                                'value' => 'application/json',
                                'type' => 'text',
                            ],
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/api/v1/profile',
                            'host' => ['{{base_url}}'],
                            'path' => ['api', 'v1', 'profile'],
                        ],
                        'description' => 'Get detailed user profile information',
                    ],
                ],
                [
                    'name' => 'Test Invalid Token',
                    'event' => [
                        [
                            'listen' => 'test',
                            'script' => [
                                'exec' => [
                                    'pm.test("Status code is 401", function () {',
                                    '    pm.response.to.have.status(401);',
                                    '});',
                                ],
                                'type' => 'text/javascript',
                            ],
                        ],
                    ],
                    'request' => [
                        'method' => 'GET',
                        'header' => [
                            [
                                'key' => 'Authorization',
                                'value' => 'Bearer invalid-token-12345',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'Accept',
                                'value' => 'application/json',
                                'type' => 'text',
                            ],
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/api/v1/test',
                            'host' => ['{{base_url}}'],
                            'path' => ['api', 'v1', 'test'],
                        ],
                        'description' => 'Test with invalid token (should return 401)',
                    ],
                ],
            ],
        ];
    }

    private function getStudentsFolder()
    {
        return [
            'name' => '👨‍🎓 Students',
            'description' => 'Student management operations',
            'item' => [
                [
                    'name' => 'Search Students',
                    'request' => [
                        'method' => 'GET',
                        'header' => [
                            [
                                'key' => 'Authorization',
                                'value' => 'Bearer {{api_token}}',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'Accept',
                                'value' => 'application/json',
                                'type' => 'text',
                            ],
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/api/v1/students/search?q=john',
                            'host' => ['{{base_url}}'],
                            'path' => ['api', 'v1', 'students', 'search'],
                            'query' => [
                                [
                                    'key' => 'q',
                                    'value' => 'john',
                                    'description' => 'Search term (name, enrollment number, or mobile)',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Get Student Profile',
                    'request' => [
                        'method' => 'GET',
                        'header' => [
                            [
                                'key' => 'Authorization',
                                'value' => 'Bearer {{api_token}}',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'Accept',
                                'value' => 'application/json',
                                'type' => 'text',
                            ],
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/api/v1/students/1/profile',
                            'host' => ['{{base_url}}'],
                            'path' => ['api', 'v1', 'students', '1', 'profile'],
                        ],
                    ],
                ],
                [
                    'name' => 'Get Student Attendance',
                    'request' => [
                        'method' => 'GET',
                        'header' => [
                            [
                                'key' => 'Authorization',
                                'value' => 'Bearer {{api_token}}',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'Accept',
                                'value' => 'application/json',
                                'type' => 'text',
                            ],
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/api/v1/students/1/attendance?month=2025-01',
                            'host' => ['{{base_url}}'],
                            'path' => ['api', 'v1', 'students', '1', 'attendance'],
                            'query' => [
                                [
                                    'key' => 'month',
                                    'value' => '2025-01',
                                    'description' => 'Month in YYYY-MM format',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getAttendanceFolder()
    {
        return [
            'name' => '📅 Attendance',
            'description' => 'Attendance management operations',
            'item' => [
                [
                    'name' => 'Submit Attendance (Valid)',
                    'request' => [
                        'method' => 'POST',
                        'header' => [
                            [
                                'key' => 'Authorization',
                                'value' => 'Bearer {{api_token}}',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'Accept',
                                'value' => 'application/json',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'Content-Type',
                                'value' => 'application/json',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'X-API-KEY',
                                'value' => '{{biometric_api_key}}',
                                'type' => 'text',
                            ],
                        ],
                        'body' => [
                            'mode' => 'raw',
                            'raw' => json_encode([
                                'enrollment_number' => 'ENR-12345678',
                                'timestamp' => time(),
                            ], JSON_PRETTY_PRINT),
                            'options' => [
                                'raw' => [
                                    'language' => 'json',
                                ],
                            ],
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/api/v1/attendance',
                            'host' => ['{{base_url}}'],
                            'path' => ['api', 'v1', 'attendance'],
                        ],
                    ],
                ],
                [
                    'name' => 'Get Today\'s Attendance',
                    'request' => [
                        'method' => 'GET',
                        'header' => [
                            [
                                'key' => 'Authorization',
                                'value' => 'Bearer {{api_token}}',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'Accept',
                                'value' => 'application/json',
                                'type' => 'text',
                            ],
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/api/v1/attendance/today',
                            'host' => ['{{base_url}}'],
                            'path' => ['api', 'v1', 'attendance', 'today'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getDashboardFolder()
    {
        return [
            'name' => '📊 Dashboard',
            'description' => 'Dashboard and analytics endpoints',
            'item' => [
                [
                    'name' => 'Get Dashboard Stats',
                    'request' => [
                        'method' => 'GET',
                        'header' => [
                            [
                                'key' => 'Authorization',
                                'value' => 'Bearer {{api_token}}',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'Accept',
                                'value' => 'application/json',
                                'type' => 'text',
                            ],
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/api/v1/dashboard/stats',
                            'host' => ['{{base_url}}'],
                            'path' => ['api', 'v1', 'dashboard', 'stats'],
                        ],
                    ],
                ],
                [
                    'name' => 'Get Attendance Trends',
                    'request' => [
                        'method' => 'GET',
                        'header' => [
                            [
                                'key' => 'Authorization',
                                'value' => 'Bearer {{api_token}}',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'Accept',
                                'value' => 'application/json',
                                'type' => 'text',
                            ],
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/api/v1/dashboard/attendance-trends?days=30',
                            'host' => ['{{base_url}}'],
                            'path' => ['api', 'v1', 'dashboard', 'attendance-trends'],
                            'query' => [
                                [
                                    'key' => 'days',
                                    'value' => '30',
                                    'description' => 'Number of days for trend analysis',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getAdminFolder()
    {
        return [
            'name' => '🛡️ Admin (Admin Only)',
            'description' => 'Administrative operations requiring admin privileges',
            'item' => [
                [
                    'name' => 'Create Student',
                    'request' => [
                        'method' => 'POST',
                        'header' => [
                            [
                                'key' => 'Authorization',
                                'value' => 'Bearer {{api_token}}',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'Accept',
                                'value' => 'application/json',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'Content-Type',
                                'value' => 'application/json',
                                'type' => 'text',
                            ],
                        ],
                        'body' => [
                            'mode' => 'raw',
                            'raw' => json_encode([
                                'name' => 'John Doe',
                                'email' => 'john.doe@example.com',
                                'enrollment_number' => 'ENR-'.rand(10000000, 99999999),
                                'batch_id' => 1,
                                'gender' => 'Male',
                                'student_mobile' => '9876543210',
                                'father_name' => 'John Doe Sr.',
                                'admission_date' => date('Y-m-d'),
                            ], JSON_PRETTY_PRINT),
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/api/v1/admin/students',
                            'host' => ['{{base_url}}'],
                            'path' => ['api', 'v1', 'admin', 'students'],
                        ],
                    ],
                ],
                [
                    'name' => 'Get All Batches',
                    'request' => [
                        'method' => 'GET',
                        'header' => [
                            [
                                'key' => 'Authorization',
                                'value' => 'Bearer {{api_token}}',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'Accept',
                                'value' => 'application/json',
                                'type' => 'text',
                            ],
                        ],
                        'url' => [
                            'raw' => '{{base_url}}/api/v1/admin/batches',
                            'host' => ['{{base_url}}'],
                            'path' => ['api', 'v1', 'admin', 'batches'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
