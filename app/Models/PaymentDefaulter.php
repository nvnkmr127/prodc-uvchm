<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PaymentDefaulter extends Model
{
    protected $fillable = [
        'student_id',
        'defaulter_category',
        'total_overdue_amount',
        'overdue_days',
        'overdue_invoice_count',
        'current_status',
        'assigned_to',
        'next_action_date',
        'notes',
        'escalation_level',
        'last_contact_date',
        'contact_attempts',
        'resolution_date',
        'contact_history'
    ];

    protected $casts = [
        'total_overdue_amount' => 'decimal:2',
        'next_action_date' => 'date',
        'last_contact_date' => 'date',
        'resolution_date' => 'date',
        'notes' => 'array',
        'contact_history' => 'array'
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('current_status', '!=', 'resolved');
    }

    public function scopeResolved($query)
    {
        return $query->where('current_status', 'resolved');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('defaulter_category', $category);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeNeedingAction($query)
    {
        return $query->where('next_action_date', '<=', today())
                    ->where('current_status', '!=', 'resolved');
    }

    // Accessors
    public function getCategoryBadgeAttribute(): string
    {
        return match($this->defaulter_category) {
            'mild' => 'badge-info',
            'moderate' => 'badge-warning',
            'severe' => 'badge-danger',
            'chronic' => 'badge-dark',
            default => 'badge-secondary'
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->current_status) {
            'active' => 'badge-warning',
            'contact_pending' => 'badge-info',
            'payment_promised' => 'badge-primary',
            'escalated' => 'badge-danger',
            'resolved' => 'badge-success',
            'suspended' => 'badge-dark',
            default => 'badge-secondary'
        };
    }

    public function getUrgencyLevelAttribute(): string
    {
        if ($this->defaulter_category === 'chronic' || $this->overdue_days > 90) {
            return 'critical';
        } elseif ($this->defaulter_category === 'severe' || $this->overdue_days > 60) {
            return 'high';
        } elseif ($this->defaulter_category === 'moderate' || $this->overdue_days > 30) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    public function getPriorityScoreAttribute(): int
    {
        $score = 0;
        
        // Category weight
        $score += match($this->defaulter_category) {
            'chronic' => 40,
            'severe' => 30,
            'moderate' => 20,
            'mild' => 10,
            default => 0
        };
        
        // Amount weight
        if ($this->total_overdue_amount > 50000) $score += 30;
        elseif ($this->total_overdue_amount > 25000) $score += 20;
        elseif ($this->total_overdue_amount > 10000) $score += 10;
        
        // Days overdue weight
        if ($this->overdue_days > 90) $score += 20;
        elseif ($this->overdue_days > 60) $score += 15;
        elseif ($this->overdue_days > 30) $score += 10;
        
        // Contact attempts penalty
        $score -= min($this->contact_attempts * 2, 10);
        
        return max(0, $score);
    }

    // Methods
    public function addNote(string $note, int $userId = null): void
    {
        $notes = $this->notes ?? [];
        $notes[] = [
            'note' => $note,
            'added_by' => $userId ?? auth()->id(),
            'added_by_name' => $userId ? User::find($userId)->name : auth()->user()->name,
            'added_at' => now()->toISOString()
        ];
        
        $this->update(['notes' => $notes]);
    }

    public function recordContact(string $method, string $outcome, string $notes = null): void
    {
        $history = $this->contact_history ?? [];
        $history[] = [
            'method' => $method,
            'outcome' => $outcome,
            'notes' => $notes,
            'contacted_by' => auth()->id(),
            'contacted_by_name' => auth()->user()->name,
            'contacted_at' => now()->toISOString()
        ];
        
        $this->update([
            'contact_history' => $history,
            'contact_attempts' => $this->contact_attempts + 1,
            'last_contact_date' => today()
        ]);
    }

    public function markResolved(string $resolution_note = null): void
    {
        $this->update([
            'current_status' => 'resolved',
            'resolution_date' => today()
        ]);
        
        if ($resolution_note) {
            $this->addNote("Resolved: " . $resolution_note);
        }
    }

    public function escalate(int $newLevel = null): void
    {
        $level = $newLevel ?? ($this->escalation_level + 1);
        
        $this->update([
            'escalation_level' => $level,
            'current_status' => 'escalated'
        ]);
        
        $this->addNote("Escalated to level {$level}");
    }
}

// PaymentReminderLog Model
class PaymentReminderLog extends Model
{
    protected $fillable = [
        'payment_reminder_id',
        'action',
        'details',
        'metadata',
        'performed_by'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function paymentReminder(): BelongsTo
    {
        return $this->belongsTo(PaymentReminder::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}

// PaymentReminderTemplate Model
class PaymentReminderTemplate extends Model
{
    protected $fillable = [
        'name',
        'reminder_type',
        'channel',
        'subject',
        'message_template',
        'available_variables',
        'is_active',
        'is_default'
    ];

    protected $casts = [
        'available_variables' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForType($query, $type)
    {
        return $query->where('reminder_type', $type);
    }

    public function scopeForChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    public function renderMessage(array $variables): string
    {
        $message = $this->message_template;
        
        foreach ($variables as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        return $message;
    }
}