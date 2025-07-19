<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReminderTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'reminder_type',
        'channel',
        'subject_template',
        'message_template',
        'available_variables',
        'is_active',
        'is_default',
        'character_limit',
        'template_settings',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'available_variables' => 'array',
        'template_settings' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean'
    ];

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for default templates
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope for specific reminder type
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('reminder_type', $type);
    }

    /**
     * Scope for specific channel
     */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Render message with variables
     */
    public function renderMessage(array $variables = []): string
    {
        $message = $this->message_template;
        
        foreach ($variables as $key => $value) {
            $placeholder = '{' . $key . '}';
            $message = str_replace($placeholder, $value, $message);
        }
        
        return $message;
    }

    /**
     * Render subject with variables (for email templates)
     */
    public function renderSubject(array $variables = []): ?string
    {
        if (!$this->subject_template) {
            return null;
        }
        
        $subject = $this->subject_template;
        
        foreach ($variables as $key => $value) {
            $placeholder = '{' . $key . '}';
            $subject = str_replace($placeholder, $value, $subject);
        }
        
        return $subject;
    }

    /**
     * Get available variables for this template
     */
    public function getAvailableVariables(): array
    {
        return $this->available_variables ?? [
            'student_name',
            'enrollment_number',
            'fee_type',
            'amount',
            'due_date',
            'days_overdue',
            'total_amount_due',
            'course_name',
            'batch_name',
            'college_name',
            'contact_number',
            'contact_email',
            'final_deadline'
        ];
    }

    /**
     * Validate message length for SMS/WhatsApp
     */
    public function validateMessageLength(string $message): bool
    {
        if (!$this->character_limit) {
            return true;
        }
        
        return strlen($message) <= $this->character_limit;
    }

    /**
     * Get creator relationship
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get updater relationship
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set created_by and updated_by
        static::creating(function ($template) {
            if (auth()->check()) {
                $template->created_by = auth()->id();
                $template->updated_by = auth()->id();
            }
        });

        static::updating(function ($template) {
            if (auth()->check()) {
                $template->updated_by = auth()->id();
            }
        });

        // Ensure only one default template per type/channel combination
        static::saving(function ($template) {
            if ($template->is_default) {
                // Remove default flag from other templates of same type/channel
                static::where('reminder_type', $template->reminder_type)
                    ->where('channel', $template->channel)
                    ->where('id', '!=', $template->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Get character limit with default fallback
     */
    public function getCharacterLimitAttribute($value)
    {
        if ($value) {
            return $value;
        }

        // Default character limits based on channel
        $defaults = [
            'sms' => 160,
            'whatsapp' => 4096,
            'email' => null
        ];

        return $defaults[$this->channel] ?? null;
    }

    /**
     * Get template preview with sample data
     */
    public function getPreview(): array
    {
        $sampleVariables = [
            'student_name' => 'John Doe',
            'enrollment_number' => 'EN001234',
            'fee_type' => 'Tuition Fee',
            'amount' => '50,000.00',
            'due_date' => '15 Aug 2024',
            'days_overdue' => '5',
            'total_amount_due' => '75,000.00',
            'course_name' => 'Computer Science',
            'batch_name' => 'CS-2024',
            'college_name' => 'ABC College',
            'contact_number' => '+91-1234567890',
            'contact_email' => 'accounts@college.edu',
            'final_deadline' => '20 Aug 2024'
        ];

        return [
            'subject' => $this->renderSubject($sampleVariables),
            'message' => $this->renderMessage($sampleVariables),
            'character_count' => strlen($this->renderMessage($sampleVariables)),
            'within_limit' => $this->validateMessageLength($this->renderMessage($sampleVariables))
        ];
    }
}