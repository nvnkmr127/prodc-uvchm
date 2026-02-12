<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class StudentPortalActivityLog extends Model
{
    public $timestamps = false; // We only use created_at

    protected $fillable = [
        'student_id',
        'action',
        'ip_address',
        'user_agent',
        'mobile_number',
        'location_data',
        'metadata'
    ];

    protected $casts = [
        'location_data' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime'
    ];

    /**
     * Relationship to Student
     */
    public function student()
    {
        return $this->belongsTo(\App\Models\Student::class);
    }

    /**
     * Log an activity
     */
    public static function logActivity($studentId, $action, $metadata = [])
    {
        $request = request();

        try {
            // Try to get mobile number from session, or metadata, or request
            $mobileNumber = session('student_portal_mobile')
                ?? ($metadata['mobile_number'] ?? null);

            return self::create([
                'student_id' => $studentId,
                'action' => $action,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'mobile_number' => $mobileNumber,
                'location_data' => self::getLocationFromIp($request->ip()),
                'metadata' => $metadata
            ]);
        } catch (\Exception $e) {
            // Log the logging error to Laravel logs but don't crash the app
            \Log::error("Failed to log student portal activity: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Get location data from IP address using free API
     */
    private static function getLocationFromIp($ip)
    {
        // Skip for local IPs
        if ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.')) {
            return ['city' => 'Local', 'country' => 'Local'];
        }

        try {
            $response = Http::timeout(2)
                ->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'status,country,city,lat,lon'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data && ($data['status'] ?? '') === 'success') {
                    return [
                        'country' => $data['country'] ?? null,
                        'city' => $data['city'] ?? null,
                        'lat' => $data['lat'] ?? null,
                        'lon' => $data['lon'] ?? null
                    ];
                }
            }
        } catch (\Exception $e) {
            // Silently fail and return null
        }

        return null;
    }
}
