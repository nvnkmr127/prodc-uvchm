<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use App\Models\Student;

trait StudentPhotoHelper
{
    /**
     * Get student's profile photo URL with fallback to dummy avatar
     *
     * @param Student $student
     * @param int $size
     * @param string $background
     * @param string $color
     * @return string
     */
    public static function getStudentPhotoUrl(Student $student, int $size = 100, string $background = '4e73df', string $color = 'fff'): string
    {
        if ($student->photo) {
            // Try multiple methods to verify file existence
            $photoPath = $student->photo;
            
            // Method 1: Try direct storage disk check
            if (Storage::disk('public')->exists($photoPath)) {
                return asset('storage/' . $photoPath);
            }
            
            // Method 2: Check if it needs student_photos prefix
            if (!str_contains($photoPath, '/')) {
                $prefixedPath = 'student_photos/' . $photoPath;
                if (Storage::disk('public')->exists($prefixedPath)) {
                    return asset('storage/' . $prefixedPath);
                }
            }
            
            // Method 3: Direct filesystem check
            $fullPath = storage_path('app/public/' . $photoPath);
            if (file_exists($fullPath)) {
                return asset('storage/' . $photoPath);
            }
            
            // Method 4: Check with student_photos prefix on filesystem
            $fullPathWithPrefix = storage_path('app/public/student_photos/' . basename($photoPath));
            if (file_exists($fullPathWithPrefix)) {
                return asset('storage/student_photos/' . basename($photoPath));
            }
        }
        
        // Generate dummy avatar using UI Avatars service
        $name = urlencode($student->name);
        
        return "https://ui-avatars.com/api/?name={$name}&size={$size}&background={$background}&color={$color}&rounded=true&bold=true";
    }

    /**
     * Get student's circular profile photo for listings
     *
     * @param Student $student
     * @return string
     */
    public static function getStudentCircularPhoto(Student $student): string
    {
        return self::getStudentPhotoUrl($student, 40, '4e73df', 'fff');
    }

    /**
     * Get student's medium profile photo for cards
     *
     * @param Student $student
     * @return string
     */
    public static function getStudentMediumPhoto(Student $student): string
    {
        return self::getStudentPhotoUrl($student, 100, '4e73df', 'fff');
    }

    /**
     * Get student's large profile photo for detailed views
     *
     * @param Student $student
     * @return string
     */
    public static function getStudentLargePhoto(Student $student): string
    {
        return self::getStudentPhotoUrl($student, 150, '4e73df', 'fff');
    }

    /**
     * Get student's photo for ID cards
     *
     * @param Student $student
     * @return string
     */
    public static function getStudentIdCardPhoto(Student $student): string
    {
        return self::getStudentPhotoUrl($student, 200, '4e73df', 'fff');
    }

    /**
     * Get gender-specific dummy avatar colors
     *
     * @param Student $student
     * @param int $size
     * @return string
     */
    public static function getGenderSpecificPhoto(Student $student, int $size = 100): string
    {
        $colors = [
            'Male' => ['background' => '3498db', 'color' => 'fff'],
            'Female' => ['background' => 'e91e63', 'color' => 'fff'],
            'Other' => ['background' => '9c27b0', 'color' => 'fff'],
        ];
        
        $genderColors = $colors[$student->gender] ?? $colors['Other'];
        
        return self::getStudentPhotoUrl($student, $size, $genderColors['background'], $genderColors['color']);
    }

    /**
     * Get batch color-coded photo
     *
     * @param Student $student
     * @param int $size
     * @return string
     */
    public static function getBatchColoredPhoto(Student $student, int $size = 100): string
    {
        // Generate color based on batch ID for consistency
        $batchId = $student->batch_id ?? 0;
        $colors = [
            '4e73df', // Primary blue
            '1cc88a', // Success green
            'f6c23e', // Warning yellow
            'e74a3b', // Danger red
            '6f42c1', // Purple
            'fd7e14', // Orange
            '20c997', // Teal
            '6c757d', // Secondary gray
        ];
        
        $colorIndex = $batchId % count($colors);
        $backgroundColor = $colors[$colorIndex];
        
        return self::getStudentPhotoUrl($student, $size, $backgroundColor, 'fff');
    }

    /**
     * Get student photo for different contexts
     *
     * @param Student $student
     * @param string $context ('list', 'card', 'profile', 'id_card', 'small')
     * @return string
     */
    public static function getStudentPhotoForContext(Student $student, string $context = 'card'): string
    {
        switch ($context) {
            case 'list':
            case 'small':
                return self::getStudentCircularPhoto($student);
            case 'card':
                return self::getStudentMediumPhoto($student);
            case 'profile':
                return self::getStudentLargePhoto($student);
            case 'id_card':
                return self::getStudentIdCardPhoto($student);
            case 'gender':
                return self::getGenderSpecificPhoto($student);
            case 'batch':
                return self::getBatchColoredPhoto($student);
            default:
                return self::getStudentMediumPhoto($student);
        }
    }

    /**
     * Get multiple photo sizes for responsive design
     *
     * @param Student $student
     * @return array
     */
    public static function getResponsivePhotos(Student $student): array
    {
        return [
            'small' => self::getStudentPhotoUrl($student, 32),
            'medium' => self::getStudentPhotoUrl($student, 64),
            'large' => self::getStudentPhotoUrl($student, 128),
            'xl' => self::getStudentPhotoUrl($student, 256),
        ];
    }

    /**
     * Check if student has a real uploaded photo
     *
     * @param Student $student
     * @return bool
     */
    public static function hasRealPhoto(Student $student): bool
    {
        return $student->photo && Storage::disk('public')->exists($student->photo);
    }

    /**
     * Get photo type (real or dummy)
     *
     * @param Student $student
     * @return string
     */
    public static function getPhotoType(Student $student): string
    {
        return self::hasRealPhoto($student) ? 'real' : 'dummy';
    }

    /**
     * Generate a data URL for student photo (useful for PDFs, emails, etc.)
     *
     * @param Student $student
     * @param int $size
     * @return string
     */
    public static function getStudentPhotoDataUrl(Student $student, int $size = 100): string
    {
        if (self::hasRealPhoto($student)) {
            try {
                $path = storage_path('app/public/' . $student->photo);
                if (file_exists($path)) {
                    $imageData = base64_encode(file_get_contents($path));
                    $mimeType = mime_content_type($path);
                    return "data:{$mimeType};base64,{$imageData}";
                }
            } catch (\Exception $e) {
                // Fall through to dummy photo
            }
        }
        
        // For dummy photos, return the URL (since we can't easily convert external URLs to data URLs)
        return self::getStudentPhotoUrl($student, $size);
    }
}