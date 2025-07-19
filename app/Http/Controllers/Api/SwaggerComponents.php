<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Components(
 *     @OA\Response(
 *         response="Unauthenticated",
 *         description="Authentication required",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
 *         )
 *     ),
 *     @OA\Response(
 *         response="Forbidden",
 *         description="Insufficient permissions",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
 *         )
 *     ),
 *     @OA\Response(
 *         response="NotFound",
 *         description="Resource not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="No query results for model.")
 *         )
 *     ),
 *     @OA\Response(
 *         response="ValidationError",
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 @OA\AdditionalProperties(
 *                     type="array",
 *                     @OA\Items(type="string")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response="ServerError",
 *         description="Internal server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Server Error"),
 *             @OA\Property(property="error", type="string", example="Internal server error occurred")
 *         )
 *     ),
 *     @OA\Schema(
 *         schema="Student",
 *         type="object",
 *         title="Student",
 *         description="Student model",
 *         @OA\Property(property="id", type="integer", example=1, description="Student ID"),
 *         @OA\Property(property="name", type="string", example="John Doe", description="Student full name"),
 *         @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Student email address"),
 *         @OA\Property(property="enrollment_number", type="string", example="ENR-12345678", description="Unique enrollment number"),
 *         @OA\Property(property="gender", type="string", enum={"Male", "Female", "Other"}, example="Male", description="Student gender"),
 *         @OA\Property(property="student_mobile", type="string", example="9876543210", description="Student mobile number"),
 *         @OA\Property(property="father_name", type="string", example="John Doe Sr.", description="Father's name"),
 *         @OA\Property(property="father_mobile", type="string", example="9876543211", description="Father's mobile number"),
 *         @OA\Property(property="village", type="string", example="New York", description="Student's village/city"),
 *         @OA\Property(property="admission_date", type="string", format="date", example="2024-01-15", description="Date of admission"),
 *         @OA\Property(property="status", type="string", enum={"active", "graduated", "dropout"}, example="active", description="Student status"),
 *         @OA\Property(property="batch_id", type="integer", example=1, description="Batch ID"),
 *         @OA\Property(property="photo", type="string", nullable=true, example="photos/student1.jpg", description="Student photo path"),
 *         @OA\Property(property="current_employer", type="string", nullable=true, example="ABC Hotel", description="Current employer (for alumni)"),
 *         @OA\Property(property="job_title", type="string", nullable=true, example="Manager", description="Current job title (for alumni)"),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 *     ),
 *     @OA\Schema(
 *         schema="Attendance",
 *         type="object",
 *         title="Attendance",
 *         description="Attendance record model",
 *         @OA\Property(property="id", type="integer", example=1, description="Attendance record ID"),
 *         @OA\Property(property="student_id", type="integer", example=1, description="Student ID"),
 *         @OA\Property(property="batch_id", type="integer", example=1, description="Batch ID"),
 *         @OA\Property(property="faculty_id", type="integer", example=1, description="Faculty ID who marked attendance"),
 *         @OA\Property(property="attendance_date", type="string", format="date", example="2025-01-15", description="Date of attendance"),
 *         @OA\Property(property="status", type="string", enum={"present", "absent", "late", "excused"}, example="present", description="Attendance status"),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-15T10:30:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-01-15T10:30:00Z")
 *     ),
 *     @OA\Schema(
 *         schema="Invoice",
 *         type="object",
 *         title="Invoice",
 *         description="Invoice model",
 *         @OA\Property(property="id", type="integer", example=1, description="Invoice ID"),
 *         @OA\Property(property="student_id", type="integer", example=1, description="Student ID"),
 *         @OA\Property(property="invoice_number", type="string", example="INV-2025-001", description="Unique invoice number"),
 *         @OA\Property(property="issue_date", type="string", format="date", example="2025-01-15", description="Invoice issue date"),
 *         @OA\Property(property="due_date", type="string", format="date", example="2025-02-15", description="Payment due date"),
 *         @OA\Property(property="total_amount", type="number", format="float", example=50000.00, description="Total invoice amount"),
 *         @OA\Property(property="paid_amount", type="number", format="float", example=30000.00, description="Amount paid"),
 *         @OA\Property(property="due_amount", type="number", format="float", example=20000.00, description="Amount due"),
 *         @OA\Property(property="concession_amount", type="number", format="float", example=0.00, description="Concession amount"),
 *         @OA\Property(property="status", type="string", enum={"unpaid", "partially_paid", "paid", "cancelled"}, example="partially_paid", description="Invoice status"),
 *         @OA\Property(property="term_number", type="integer", nullable=true, example=1, description="Payment term number"),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-15T10:30:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-01-15T10:30:00Z")
 *     ),
 *     @OA\Schema(
 *         schema="Course",
 *         type="object",
 *         title="Course",
 *         description="Course model",
 *         @OA\Property(property="id", type="integer", example=1, description="Course ID"),
 *         @OA\Property(property="name", type="string", example="Hotel Management", description="Course name"),
 *         @OA\Property(property="enrollment_prefix", type="string", nullable=true, example="HM", description="Enrollment number prefix"),
 *         @OA\Property(property="duration_in_years", type="number", format="float", example=2.0, description="Course duration in years"),
 *         @OA\Property(property="max_batch_size", type="integer", example=30, description="Maximum students per batch"),
 *         @OA\Property(property="description", type="string", nullable=true, example="Comprehensive hotel management course", description="Course description"),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-15T10:30:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-01-15T10:30:00Z")
 *     ),
 *     @OA\Schema(
 *         schema="Batch",
 *         type="object",
 *         title="Batch",
 *         description="Batch model",
 *         @OA\Property(property="id", type="integer", example=1, description="Batch ID"),
 *         @OA\Property(property="course_id", type="integer", example=1, description="Course ID"),
 *         @OA\Property(property="name", type="string", example="2024-2025 Batch", description="Batch name"),
 *         @OA\Property(property="start_date", type="string", format="date", example="2024-01-01", description="Batch start date"),
 *         @OA\Property(property="end_date", type="string", format="date", example="2025-12-31", description="Batch end date"),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
 *     ),
 *     @OA\Schema(
 *         schema="User",
 *         type="object",
 *         title="User",
 *         description="User model",
 *         @OA\Property(property="id", type="integer", example=1, description="User ID"),
 *         @OA\Property(property="name", type="string", example="John Doe", description="User full name"),
 *         @OA\Property(property="email", type="string", format="email", example="john@example.com", description="User email address"),
 *         @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true, example="2024-01-15T10:30:00Z", description="Email verification timestamp"),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 *     ),
 *     @OA\Schema(
 *         schema="ApiResponse",
 *         type="object",
 *         title="Standard API Response",
 *         description="Standard API response format",
 *         @OA\Property(property="success", type="boolean", example=true, description="Indicates if the request was successful"),
 *         @OA\Property(property="message", type="string", example="Operation completed successfully", description="Response message"),
 *         @OA\Property(property="data", type="object", description="Response data"),
 *         @OA\Property(property="timestamp", type="string", format="date-time", example="2025-01-15T10:30:00Z", description="Response timestamp")
 *     ),
 *     @OA\Schema(
 *         schema="PaginatedResponse",
 *         type="object",
 *         title="Paginated Response",
 *         description="Paginated API response format",
 *         @OA\Property(property="data", type="array", @OA\Items(type="object"), description="Array of data items"),
 *         @OA\Property(property="links", type="object", 
 *             @OA\Property(property="first", type="string", example="https://example.com/api/resource?page=1"),
 *             @OA\Property(property="last", type="string", example="https://example.com/api/resource?page=10"),
 *             @OA\Property(property="prev", type="string", nullable=true, example="https://example.com/api/resource?page=1"),
 *             @OA\Property(property="next", type="string", nullable=true, example="https://example.com/api/resource?page=3")
 *         ),
 *         @OA\Property(property="meta", type="object",
 *             @OA\Property(property="current_page", type="integer", example=2),
 *             @OA\Property(property="from", type="integer", example=11),
 *             @OA\Property(property="last_page", type="integer", example=10),
 *             @OA\Property(property="per_page", type="integer", example=10),
 *             @OA\Property(property="to", type="integer", example=20),
 *             @OA\Property(property="total", type="integer", example=95)
 *         )
 *     )
 * )
 */
class SwaggerComponents
{
    // This class exists only for Swagger component definitions
}