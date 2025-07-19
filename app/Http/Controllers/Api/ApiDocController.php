<?php
// Create app/Http/Controllers/ApiDocController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiDocController extends Controller
{
    public function documentation()
    {
        return view('api.documentation');
    }

    public function json()
    {
        $swagger = [
            "openapi" => "3.0.0",
            "info" => [
                "title" => "School Management System API",
                "description" => "API documentation for School Management System",
                "version" => "1.0.0",
                "contact" => [
                    "email" => "admin@uvchm.com"
                ]
            ],
            "servers" => [
                [
                    "url" => config('app.url'),
                    "description" => "Production Server"
                ]
            ],
            "components" => [
                "securitySchemes" => [
                    "bearerAuth" => [
                        "type" => "http",
                        "scheme" => "bearer",
                        "bearerFormat" => "JWT"
                    ]
                ]
            ],
            "security" => [
                [
                    "bearerAuth" => []
                ]
            ],
            "paths" => [
                "/api/v1/test" => [
                    "get" => [
                        "tags" => ["Authentication"],
                        "summary" => "Test API authentication",
                        "description" => "Test endpoint to verify API authentication",
                        "security" => [["bearerAuth" => []]],
                        "responses" => [
                            "200" => [
                                "description" => "Success",
                                "content" => [
                                    "application/json" => [
                                        "schema" => [
                                            "type" => "object",
                                            "properties" => [
                                                "success" => ["type" => "boolean", "example" => true],
                                                "message" => ["type" => "string", "example" => "API is working!"],
                                                "user" => ["type" => "string", "example" => "John Doe"],
                                                "timestamp" => ["type" => "string", "example" => "2025-01-15T10:30:00Z"]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            "401" => [
                                "description" => "Unauthenticated",
                                "content" => [
                                    "application/json" => [
                                        "schema" => [
                                            "type" => "object",
                                            "properties" => [
                                                "message" => ["type" => "string", "example" => "Unauthenticated."]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                "/api/v1/students/search" => [
                    "get" => [
                        "tags" => ["Students"],
                        "summary" => "Search students",
                        "description" => "Search for students by name, enrollment number, or mobile",
                        "security" => [["bearerAuth" => []]],
                        "parameters" => [
                            [
                                "name" => "q",
                                "in" => "query",
                                "required" => true,
                                "description" => "Search query",
                                "schema" => ["type" => "string", "example" => "john"]
                            ]
                        ],
                        "responses" => [
                            "200" => [
                                "description" => "Students found",
                                "content" => [
                                    "application/json" => [
                                        "schema" => [
                                            "type" => "object",
                                            "properties" => [
                                                "data" => [
                                                    "type" => "array",
                                                    "items" => [
                                                        "type" => "object",
                                                        "properties" => [
                                                            "id" => ["type" => "integer", "example" => 1],
                                                            "name" => ["type" => "string", "example" => "John Doe"],
                                                            "enrollment_number" => ["type" => "string", "example" => "ENR-12345678"],
                                                            "course" => ["type" => "string", "example" => "Hotel Management"],
                                                            "batch" => ["type" => "string", "example" => "2024-2025 Batch"]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                "/api/v1/attendance" => [
                    "post" => [
                        "tags" => ["Attendance"],
                        "summary" => "Submit attendance",
                        "description" => "Submit attendance record from biometric device",
                        "security" => [["bearerAuth" => []]],
                        "parameters" => [
                            [
                                "name" => "X-API-KEY",
                                "in" => "header",
                                "required" => true,
                                "description" => "Biometric device API key",
                                "schema" => ["type" => "string"]
                            ]
                        ],
                        "requestBody" => [
                            "required" => true,
                            "content" => [
                                "application/json" => [
                                    "schema" => [
                                        "type" => "object",
                                        "required" => ["enrollment_number"],
                                        "properties" => [
                                            "enrollment_number" => ["type" => "string", "example" => "ENR-12345678"],
                                            "timestamp" => ["type" => "integer", "example" => 1672531200]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        "responses" => [
                            "200" => [
                                "description" => "Attendance recorded",
                                "content" => [
                                    "application/json" => [
                                        "schema" => [
                                            "type" => "object",
                                            "properties" => [
                                                "status" => ["type" => "string", "example" => "success"],
                                                "message" => ["type" => "string", "example" => "Attendance recorded for John Doe"]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return response()->json($swagger);
    }
}