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
            '504B0708',  // ZIP spanned archive
        ],
        'xls' => [
            'D0CF11E0A1B11AE1', // OLE2 signature
            '09080600',          // Alternative XLS signature
            'FD377A58',           // Alternative XLS signature
        ],
        'csv' => [
            // CSV files are plain text, so we'll validate content structure
        ],
    ];

    /**
     * Validate uploaded file for security
     */
    public function validateFile(UploadedFile $file, array $allowedTypes = ['xlsx', 'xls', 'csv']): array
    {
        try {
            // Basic validation
            $basicValidation = $this->performBasicValidation($file, $allowedTypes);
            if (! $basicValidation['valid']) {
                return $basicValidation;
            }

            // Magic byte validation
            $magicByteValidation = $this->validateMagicBytes($file);
            if (! $magicByteValidation['valid']) {
                return $magicByteValidation;
            }

            // Content structure validation
            $contentValidation = $this->validateFileContent($file);
            if (! $contentValidation['valid']) {
                return $contentValidation;
            }

            return ['valid' => true, 'message' => 'File validation passed'];

        } catch (\Exception $e) {
            Log::error('File validation error:', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'error' => 'File validation failed due to security concerns',
            ];
        }
    }
}
