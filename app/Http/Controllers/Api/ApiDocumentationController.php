<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     title="School Management System API",
 *     version="1.0.0",
 *     description="API documentation for School Management System - Complete student management, attendance tracking, and administrative operations.",
 *     @OA\Contact(
 *         email="admin@uvchm.com",
 *         name="API Support"
 *     ),
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
 *
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
 *
 * @OA\Tag(
 *     name="Students",
 *     description="Student management operations"
 * )
 *
 * @OA\Tag(
 *     name="Attendance",
 *     description="Attendance management operations"
 * )
 *
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Dashboard and analytics endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Admin",
 *     description="Administrative operations (admin only)"
 * )
 */
class ApiDocumentationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/test",
     *     operationId="testApi",
     *     tags={"Authentication"},
     *     summary="Test API connection",
     *     description="Simple endpoint to test if API is working and authentication is successful",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="API is working correctly",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="API is working!"),
     *             @OA\Property(property="user", type="string", example="John Doe"),
     *             @OA\Property(property="timestamp", type="string", example="2025-01-01T12:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function test()
    {
        // This method exists only for documentation purposes
        // The actual implementation is in TestController
    }

    /**
     * @OA\Get(
     *     path="/api/students/search",
     *     operationId="searchStudents",
     *     tags={"Students"},
     *     summary="Search for students",
     *     description="Search for students by name, enrollment number, or mobile number",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=true,
     *         description="Search query (name, enrollment number, or mobile)",
     *         @OA\Schema(type="string", example="john")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Students found successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
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
     * @OA\Post(
     *     path="/api/attendance",
     *     operationId="submitAttendance",
     *     tags={"Attendance"},
     *     summary="Submit attendance record",
     *     description="Submit attendance record from biometric device or manual entry",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="X-API-KEY",
     *         in="header",
     *         required=true,
     *         description="Biometric device API key",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"enrollment_number"},
     *             @OA\Property(property="enrollment_number", type="string", example="ENR-12345678"),
     *             @OA\Property(property="timestamp", type="integer", example=1672531200, description="Unix timestamp (optional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance recorded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Attendance recorded for John Doe"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="student_id", type="integer", example=1),
     *                 @OA\Property(property="attendance_date", type="string", format="date", example="2025-01-15"),
     *                 @OA\Property(property="status", type="string", example="present")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid API key",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function submitAttendance()
    {
        // Documentation only
    }
}