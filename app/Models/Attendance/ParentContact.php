<?php

namespace App\Models\Attendance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Student;
use Illuminate\Support\Facades\Crypt;

class ParentContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'contact_type',
        'contact_name',
        'relationship',
        'primary_phone',
        'secondary_phone',
        'email',
        'whatsapp_number',
        'preferred_language',
        'notification_preferences',
        'emergency_contact',
        'is_active',
        'verified_at',
        'last_contacted_at',
        'contact_attempts',
        'delivery_failures',
        'blocked_until',
        'notes'
    ];

    protected $casts = [
        'notification_preferences' => 'array',
        'emergency_contact' => 'boolean',
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
        'last_contacted_at' => 'datetime',
        'blocked_until' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'primary_phone',
        'secondary_phone',
        'whatsapp_number'
    ];

    /**
     * Get the student this contact belongs to
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Scope for active contacts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('blocked_until')
                          ->orWhere('blocked_until', '<', now());
                    });
    }

    /**
     * Scope for emergency contacts
     */
    public function scopeEmergency($query)
    {
        return $query->where('emergency_contact', true);
    }

    /**
     * Scope for verified contacts
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    /**
     * Scope for specific notification type preference
     */
    public function scopeWithNotificationPreference($query, string $type)
    {
        return $query->whereJsonContains('notification_preferences->channels', $type);
    }

    /**
     * Scope for contacts that accept attendance notifications
     */
    public function scopeAttendanceNotifications($query)
    {
        return $query->whereJsonContains('notification_preferences->attendance_alerts', true);
    }

    /**
     * Get encrypted phone number
     */
    public function getEncryptedPrimaryPhoneAttribute()
    {
        return $this->primary_phone ? Crypt::encrypt($this->primary_phone) : null;
    }

    /**
     * Set encrypted phone number
     */
    public function setPrimaryPhoneAttribute($value)
    {
        $this->attributes['primary_phone'] = $value ? Crypt::encrypt($value) : null;
    }

    /**
     * Get decrypted phone number
     */
    public function getDecryptedPrimaryPhoneAttribute()
    {
        try {
            return $this->primary_phone ? Crypt::decrypt($this->primary_phone) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get masked phone number for display
     */
    public function getMaskedPhoneAttribute(): string
    {
        $phone = $this->getDecryptedPrimaryPhoneAttribute();
        if (!$phone) return 'Not provided';
        
        $length = strlen($phone);
        if ($length <= 4) return $phone;
        
        return substr($phone, 0, 2) . str_repeat('*', $length - 4) . substr($phone, -2);
    }

    /**
     * Check if contact accepts specific notification type
     */
    public function acceptsNotification(string $type): bool
    {
        $preferences = $this->notification_preferences ?? [];
        
        return match($type) {
            'attendance' => $preferences['attendance_alerts'] ?? true,
            'absence' => $preferences['absence_alerts'] ?? true,
            'late_arrival' => $preferences['late_arrival_alerts'] ?? true,
            'monthly_report' => $preferences['monthly_reports'] ?? true,
            'emergency' => $preferences['emergency_alerts'] ?? true,
            'fee_reminder' => $preferences['fee_reminders'] ?? false,
            'exam_notification' => $preferences['exam_notifications'] ?? true,
            default => false,
        };
    }

    /**
     * Get preferred notification channels for contact
     */
    public function getPreferredChannels(): array
    {
        $preferences = $this->notification_preferences ?? [];
        return $preferences['channels'] ?? ['sms'];
    }

    /**
     * Check if contact can receive notifications
     */
    public function canReceiveNotifications(): bool
    {
        return $this->is_active 
            && ($this->blocked_until === null || $this->blocked_until->isPast())
            && $this->delivery_failures < 5;
    }

    /**
     * Record successful notification delivery
     */
    public function recordSuccessfulContact(string $channel = null): void
    {
        $this->update([
            'last_contacted_at' => now(),
            'delivery_failures' => 0,
            'contact_attempts' => $this->contact_attempts + 1,
        ]);
    }

    /**
     * Record failed notification delivery
     */
    public function recordFailedContact(string $reason = null): void
    {
        $failures = $this->delivery_failures + 1;
        
        $updateData = [
            'delivery_failures' => $failures,
            'contact_attempts' => $this->contact_attempts + 1,
        ];

        // Block contact if too many failures
        if ($failures >= 5) {
            $updateData['blocked_until'] = now()->addDays(7);
            $updateData['is_active'] = false;
        } elseif ($failures >= 3) {
            $updateData['blocked_until'] = now()->addHours(24);
        }

        if ($reason) {
            $updateData['notes'] = ($this->notes ? $this->notes . "\n" : '') . 
                                 now()->format('Y-m-d H:i:s') . ": Failed delivery - {$reason}";
        }

        $this->update($updateData);
    }

    /**
     * Verify contact information
     */
    public function markAsVerified(): void
    {
        $this->update([
            'verified_at' => now(),
            'is_active' => true,
            'delivery_failures' => 0,
            'blocked_until' => null,
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreferences(array $preferences): void
    {
        $currentPreferences = $this->notification_preferences ?? [];
        $updatedPreferences = array_merge($currentPreferences, $preferences);
        
        $this->update(['notification_preferences' => $updatedPreferences]);
    }

    /**
     * Get contact information for notifications
     */
    public function getContactInfoForChannel(string $channel): ?string
    {
        return match($channel) {
            'sms' => $this->getDecryptedPrimaryPhoneAttribute(),
            'whatsapp' => $this->whatsapp_number,
            'email' => $this->email,
            'call' => $this->getDecryptedPrimaryPhoneAttribute(),
            default => null,
        };
    }

    /**
     * Get all contacts for a student with notification preferences
     */
    public static function getNotificationContactsForStudent(int $studentId, string $notificationType = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::where('student_id', $studentId)
                      ->active()
                      ->orderBy('emergency_contact', 'desc')
                      ->orderBy('contact_type');

        if ($notificationType) {
            $query->where(function ($q) use ($notificationType) {
                $q->whereJsonContains("notification_preferences->{$notificationType}", true)
                  ->orWhereNull('notification_preferences');
            });
        }

        return $query->get();
    }

    /**
     * Get relationship badge
     */
    public function getRelationshipBadgeAttribute(): array
    {
        return match($this->relationship) {
            'father' => ['text' => 'Father', 'class' => 'primary'],
            'mother' => ['text' => 'Mother', 'class' => 'info'],
            'guardian' => ['text' => 'Guardian', 'class' => 'success'],
            'sibling' => ['text' => 'Sibling', 'class' => 'warning'],
            'other' => ['text' => 'Other', 'class' => 'secondary'],
            default => ['text' => 'Unknown', 'class' => 'dark'],
        };
    }

    /**
     * Get status badge
     */
    public function getStatusBadgeAttribute(): array
    {
        if (!$this->is_active) {
            return ['text' => 'Inactive', 'class' => 'secondary'];
        }
        
        if ($this->blocked_until && $this->blocked_until->isFuture()) {
            return ['text' => 'Blocked', 'class' => 'danger'];
        }
        
        if ($this->delivery_failures >= 3) {
            return ['text' => 'Issues', 'class' => 'warning'];
        }
        
        if ($this->verified_at) {
            return ['text' => 'Verified', 'class' => 'success'];
        }
        
        return ['text' => 'Active', 'class' => 'info'];
    }

    /**
     * Clean up blocked contacts that should be unblocked
     */
    public static function unblockExpiredContacts(): int
    {
        return static::where('blocked_until', '<', now())
                    ->update([
                        'blocked_until' => null,
                        'is_active' => true,
                    ]);
    }
}