<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     title="School Management System API",
 *     version="1.0.0",
 *     description="API documentation for School Management System - Complete student management, attendance tracking, and administrative operations.",
 *
 *     @OA\Contact(
 *         email="admin@uvchm.com",
 *         name="API Support"
 *     ),
 *
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="https://uvchm.digicloudify.com",
 *     description="Production Server"
 * )
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Development Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your API token in the format: Bearer YOUR_TOKEN"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="API authentication and testing endpoints"
 * )
 * @OA\Tag(
 *     name="Students",
 *     description="Student management operations"
 * )
 * @OA\Tag(
 *     name="Attendance",
 *     description="Attendance management operations"
 * )
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Dashboard and analytics endpoints"
 * )
 * @OA\Tag(
 *     name="Admin",
 *     description="Administrative operations (admin only)"
 * )
 */
class ApiDocumentationController extends Controller
{
    public function json()
    {
        $openapi = \OpenApi\Generator::scan([
            app_path('Http/Controllers/Api'),
            app_path('Models'),
        ]);

        return response()->json($openapi);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/test",
     *     operationId="testApi",
     *     tags={"Authentication"},
     *     summary="Test API connection",
     *     description="Simple endpoint to test if API is working and authentication is successful",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="API is working correctly",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="API is working!"),
     *             @OA\Property(property="user", type="string", example="John Doe"),
     *             @OA\Property(property="timestamp", type="string", example="2025-01-01T12:00:00Z")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    /**
     * @OA\Post(
     *     path="/login",
     *     operationId="login",
     *     tags={"Authentication"},
     *     summary="Login user",
     *     description="Login with email and password to get an access token",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="token", type="string", example="1|AbCdEf123456"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     )
     * )
     */
    public function login() {}

    /**
     * @OA\Get(
     *     path="/api/user",
     *     operationId="getUser",
     *     tags={"Authentication"},
     *     summary="Get user profile",
     *     description="Get the currently authenticated user",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="User profile retrieved",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com")
     *         )
     *     )
     * )
     */
    public function user() {}

    /**
     * @OA\Get(
     *     path="/api/students/search",
     *     operationId="searchStudents",
     *     tags={"Students"},
     *     summary="Search for students",
     *     description="Search for students by name, enrollment number, or mobile number",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=true,
     *         description="Search query (name, enrollment number, or mobile)",
     *
     *         @OA\Schema(type="string", example="john")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Students found successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="enrollment_number", type="string", example="ENR-12345678"),
     *                     @OA\Property(property="course", type="string", example="Hotel Management"),
     *                     @OA\Property(property="batch", type="string", example="2024-2025 Batch")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function searchStudents()
    {
        // Documentation only
    }

    /**
     * @OA\Get(
     *     path="/api/dashboard/my-payment-data",
     *     operationId="getMyPaymentData",
     *     tags={"Dashboard"},
     *     summary="Get payment data",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Payment data retrieved")
     * )
     */
    public function getMyPaymentData() {}

    /**
     * @OA\Get(
     *     path="/api/dashboard/my-activities",
     *     operationId="getMyActivities",
     *     tags={"Dashboard"},
     *     summary="Get user activities",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Activities retrieved")
     * )
     */
    public function getMyActivities() {}

    /**
     * @OA\Get(
     *     path="/api/dashboard/attendance-data",
     *     operationId="getAttendanceData",
     *     tags={"Dashboard"},
     *     summary="Get attendance data",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Attendance data retrieved")
     * )
     */
    public function getAttendanceData() {}

    /**
     * @OA\Post(
     *     path="/api/attendance",
     *     operationId="submitAttendance",
     *     tags={"Attendance"},
     *     summary="Submit attendance record",
     *     description="Submit attendance record from biometric device or manual entry",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="X-API-KEY",
     *         in="header",
     *         required=true,
     *         description="Biometric device API key",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"enrollment_number"},
     *
     *             @OA\Property(property="enrollment_number", type="string", example="ENR-12345678"),
     *             @OA\Property(property="timestamp", type="integer", example=1672531200, description="Unix timestamp (optional)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Attendance recorded successfully"
     *     )
     * )
     */
    public function submitAttendance() {}

    /**
     * @OA\Get(
     *     path="/api/students/{student}",
     *     operationId="getStudent",
     *     tags={"Students"},
     *     summary="Get student details",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="student",
     *         in="path",
     *         required=true,
     *         description="Student ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(response=200, description="Student details retrieved")
     * )
     */
    public function getStudent() {}

    /**
     * @OA\Get(
     *     path="/api/search",
     *     operationId="globalSearch",
     *     tags={"Search"},
     *     summary="Global search",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=true,
     *         description="Search term",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(response=200, description="Search results")
     * )
     */
    public function globalSearch() {}

    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     operationId="getNotifications",
     *     tags={"Notifications"},
     *     summary="Get notifications",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="List of notifications")
     * )
     */
    public function getNotifications() {}

    /**
     * @OA\Get(
     *     path="/api/notifications/unread-count",
     *     operationId="getUnreadNotificationCount",
     *     tags={"Notifications"},
     *     summary="Get unread notification count",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Unread count retrieved")
     * )
     */
    public function getUnreadNotificationCount() {}

    /**
     * @OA\Post(
     *     path="/api/notifications/{notification}/read",
     *     operationId="markNotificationRead",
     *     tags={"Notifications"},
     *     summary="Mark notification as read",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="notification",
     *         in="path",
     *         required=true,
     *         description="Notification ID",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(response=200, description="Notification marked as read")
     * )
     */
    public function markNotificationRead() {}

    /**
     * @OA\Post(
     *     path="/api/etimeoffice/webhook",
     *     operationId="etimeofficeWebhook",
     *     tags={"Webhooks"},
     *     summary="ETimeOffice Webhook",
     *     description="Handle biometric data from ETimeOffice",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=200, description="Webhook processed")
     * )
     */
    public function etimeofficeWebhook() {}

    /**
     * @OA\Get(
     *     path="/api/attendance/today",
     *     operationId="getTodayAttendance",
     *     tags={"Attendance"},
     *     summary="Get today's attendance",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Attendance records retrieved")
     * )
     */
    public function getTodayAttendance() {}

    /**
     * @OA\Get(
     *     path="/api/attendance/stats/today",
     *     operationId="getTodayStats",
     *     tags={"Attendance"},
     *     summary="Get today's attendance stats",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Statistics retrieved")
     * )
     */
    public function getTodayStats() {}
}
