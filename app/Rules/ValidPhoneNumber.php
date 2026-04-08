<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPhoneNumber implements ValidationRule
{
    private $allowInternational;

    private $requireCountryCode;

    public function __construct(bool $allowInternational = false, bool $requireCountryCode = false)
    {
        $this->allowInternational = $allowInternational;
        $this->requireCountryCode = $requireCountryCode;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // Allow empty values - use 'required' rule separately if needed
        }

        // Remove spaces, hyphens, and parentheses
        $cleanedValue = preg_replace('/[\s\-\(\)]/', '', $value);

        // Check for basic phone number patterns
        if ($this->allowInternational) {
            $this->validateInternationalPhone($cleanedValue, $fail, $attribute);
        } else {
            $this->validateIndianPhone($cleanedValue, $fail, $attribute);
        }
    }

    /**
     * Validate Indian phone numbers
     */
    private function validateIndianPhone(string $phone, Closure $fail, string $attribute): void
    {
        // Indian mobile numbers: 10 digits starting with 6,7,8,9
        // Landline: 10-11 digits with STD codes

        if (preg_match('/^[6-9]\d{9}$/', $phone)) {
            // Valid Indian mobile number
            return;
        }

        if (preg_match('/^0[1-9]\d{8,9}$/', $phone)) {
            // Valid Indian landline with STD code
            return;
        }

        if (preg_match('/^\+91[6-9]\d{9}$/', $phone)) {
            // Valid Indian mobile with country code
            return;
        }

        $fail('The :attribute must be a valid Indian phone number (10 digits starting with 6, 7, 8, or 9).');
    }

    /**
     * Validate international phone numbers
     */
    private function validateInternationalPhone(string $phone, Closure $fail, string $attribute): void
    {
        // Basic international phone validation
        if ($this->requireCountryCode) {
            // Must start with + and country code
            if (! preg_match('/^\+[1-9]\d{1,14}$/', $phone)) {
                $fail('The :attribute must be a valid international phone number with country code (e.g., +91xxxxxxxxxx).');

                return;
            }
        } else {
            // Allow various formats
            if (! preg_match('/^(\+[1-9]\d{1,14}|[6-9]\d{9}|0[1-9]\d{8,9})$/', $phone)) {
                $fail('The :attribute must be a valid phone number.');

                return;
            }
        }

        // Additional length validation
        $digitCount = strlen(preg_replace('/[^\d]/', '', $phone));
        if ($digitCount < 7 || $digitCount > 15) {
            $fail('The :attribute must contain between 7 and 15 digits.');
        }
    }

    /**
     * Create a rule for Indian phone numbers only
     */
    public static function indian(): self
    {
        return new self(false, false);
    }

    /**
     * Create a rule for international phone numbers
     */
    public static function international(bool $requireCountryCode = false): self
    {
        return new self(true, $requireCountryCode);
    }
}
