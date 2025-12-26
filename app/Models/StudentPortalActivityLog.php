<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'metadata',
        'is_suspicious',
        'flagged_reason'
    ];

    protected $casts = [
        'location_data' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'is_suspicious' => 'boolean'
    ];

    /**
     * Relationship to Student
     */
    public function student()
    {
        return $this->belongsTo(\App\Models\Student::class);
    }

    /**
     * Log an activity with suspicious activity detection
     */
    public static function logActivity($studentId, $action, $metadata = [])
    {
        $request = request();
        $ipAddress = $request->ip();
        $locationData = self::getLocationFromIp($ipAddress);

        // Detect suspicious activity
        $suspiciousCheck = \App\Services\SuspiciousActivityDetector::detect(
            $studentId,
            $action,
            $ipAddress,
            $locationData
        );

        return self::create([
            'student_id' => $studentId,
            'action' => $action,
            'ip_address' => $ipAddress,
            'user_agent' => $request->userAgent(),
            'mobile_number' => session('student_portal_mobile'),
            'location_data' => $locationData,
            'metadata' => $metadata,
            'is_suspicious' => $suspiciousCheck['is_suspicious'],
            'flagged_reason' => $suspiciousCheck['flagged_reason']
        ]);
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
            $response = file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,city,lat,lon");
            $data = json_decode($response, true);

            if ($data && $data['status'] === 'success') {
                return [
                    'country' => $data['country'] ?? null,
                    'city' => $data['city'] ?? null,
                    'lat' => $data['lat'] ?? null,
                    'lon' => $data['lon'] ?? null
                ];
            }
        } catch (\Exception $e) {
            // Silently fail and return null
        }

        return null;
    }
}
