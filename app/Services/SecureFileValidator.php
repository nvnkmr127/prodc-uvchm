<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class SecureFileValidator
{
    /**
     * Magic bytes for different file types
     */
    private const MAGIC_BYTES = [
        // Excel formats
        'xlsx' => [
            '504B0304', // ZIP signature (XLSX is a ZIP file)
            '504B0506', // ZIP empty archive
            '504B0708'  // ZIP spanned archive
        ],
        'xls' => [
            'D0CF11E0A1B11AE1', // OLE2 signature
            '09080600',          // Alternative XLS signature
            'FD377A58'           // Alternative XLS signature
        ],
        'csv' => [
            // CSV files are plain text, so we'll validate content structure
        ]
    ];

    /**
     * Validate uploaded file for security
     */
    public function validateFile(UploadedFile $file, array $allowedTypes = ['xlsx', 'xls', 'csv']): array
    {
        try {
            // Basic validation
            $basicValidation = $this->performBasicValidation($file, $allowedTypes);
            if (!$basicValidation['valid']) {
                return $basicValidation;
            }

            // Magic byte validation
            $magicByteValidation = $this->validateMagicBytes($file);
            if (!$magicByteValidation['valid']) {
                return $magicByteValidation;
            }

            // Content structure validation
            $contentValidation = $this->validateFileContent($file);
            if (!$contentValidation['valid']) {
                return $contentValidation;
            }

            return ['valid' => true, 'message' => 'File validation passed'];

        } catch (\Exception $e) {
            Log::error('File validation error:', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            
            return [
                'valid' => false,
                'error' => 'File validation failed due to security concerns'
            ];
        }
    }

    /**
     * Perform basic file validation
     */
    private function performBasicValidation(UploadedFile $file, array $allowedTypes): array
    {
        // Check file size (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            return [
                'valid' => false,
                'error' => 'File size exceeds maximum allowed size of 5MB'
            ];
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedTypes)) {
            return [
                'valid' => false,
                'error' => 'Invalid file format. Allowed formats: ' . implode(', ', $allowedTypes)
            ];
        }

        // Check MIME type
        $allowedMimes = [
            'text/csv',
            'application/csv',
            'text/plain',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return [
                'valid' => false,
                'error' => 'Invalid MIME type detected'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate file magic bytes
     */
    private function validateMagicBytes(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        // Skip magic byte validation for CSV as it's plain text
        if ($extension === 'csv') {
            return ['valid' => true];
        }

        $handle = fopen($file->getPathname(), 'rb');
        if (!$handle) {
            return [
                'valid' => false,
                'error' => 'Unable to read file for validation'
            ];
        }

        // Read first 16 bytes
        $header = fread($handle, 16);
        fclose($handle);

        $headerHex = strtoupper(bin2hex($header));

        // Check against known magic bytes
        if (isset(self::MAGIC_BYTES[$extension])) {
            foreach (self::MAGIC_BYTES[$extension] as $magicByte) {
                if (strpos($headerHex, $magicByte) === 0) {
                    return ['valid' => true];
                }
            }
        }

        return [
            'valid' => false,
            'error' => 'File content does not match expected format'
        ];
    }

    /**
     * Validate file content structure
     */
    private function validateFileContent(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        switch ($extension) {
            case 'csv':
                return $this->validateCsvContent($file);
            case 'xlsx':
            case 'xls':
                return $this->validateExcelContent($file);
            default:
                return ['valid' => true];
        }
    }

    /**
     * Validate CSV content structure
     */
    private function validateCsvContent(UploadedFile $file): array
    {
        $handle = fopen($file->getPathname(), 'r');
        if (!$handle) {
            return [
                'valid' => false,
                'error' => 'Unable to read CSV file'
            ];
        }

        // Read first few lines to validate structure
        $lineCount = 0;
        $headerCount = 0;
        
        while (($line = fgets($handle)) !== false && $lineCount < 10) {
            $lineCount++;
            
            // Check for suspicious content
            if ($this->containsSuspiciousContent($line)) {
                fclose($handle);
                return [
                    'valid' => false,
                    'error' => 'File contains suspicious content'
                ];
            }

            // Count columns in first row (header)
            if ($lineCount === 1) {
                $headerCount = count(str_getcsv($line));
                if ($headerCount < 2) {
                    fclose($handle);
                    return [
                        'valid' => false,
                        'error' => 'CSV file must have at least 2 columns'
                    ];
                }
            }
        }

        fclose($handle);
        return ['valid' => true];
    }

    /**
     * Validate Excel content (basic check)
     */
    private function validateExcelContent(UploadedFile $file): array
    {
        try {
            // Try to read the file with a simple check
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file->getPathname());
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
            
            // Only read first worksheet and first few rows
            $spreadsheet = $reader->load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Check if file has data
            $highestRow = min($worksheet->getHighestRow(), 10); // Limit to first 10 rows
            $highestColumn = $worksheet->getHighestColumn();
            
            if ($highestRow < 1) {
                return [
                    'valid' => false,
                    'error' => 'Excel file appears to be empty'
                ];
            }

            // Check for suspicious content in first few cells
            for ($row = 1; $row <= min($highestRow, 5); $row++) {
                for ($col = 'A'; $col <= $highestColumn && $col <= 'J'; $col++) {
                    $cellValue = $worksheet->getCell($col . $row)->getCalculatedValue();
                    if ($this->containsSuspiciousContent($cellValue)) {
                        return [
                            'valid' => false,
                            'error' => 'File contains suspicious content'
                        ];
                    }
                }
            }

            return ['valid' => true];
            
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'Invalid Excel file format'
            ];
        }
    }

    /**
     * Check for suspicious content patterns
     */
    private function containsSuspiciousContent($content): bool
    {
        if (!is_string($content)) {
            $content = (string) $content;
        }

        $suspiciousPatterns = [
            // Script tags
            '/<script[^>]*>/i',
            '/<\/script>/i',
            // PHP tags
            '/<\?php/i',
            '/<\?=/i',
            // Executable extensions
            '/\.(exe|bat|cmd|com|pif|scr|vbs|js)$/i',
            // SQL injection patterns
            '/\b(union|select|insert|update|delete|drop|create|alter)\s+/i',
            // XSS patterns
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
            // File inclusion patterns
            '/\.\.\//i',
            '/\.\.\\\\/',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitize filename
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove path traversal attempts
        $filename = basename($filename);
        
        // Remove or replace dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Limit length
        if (strlen($filename) > 100) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 95 - strlen($extension)) . '.' . $extension;
        }
        
        return $filename;
    }
}