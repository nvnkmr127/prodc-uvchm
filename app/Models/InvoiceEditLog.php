<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceEditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'user_id',
        'user_name',
        'action',
        'previous_state',
        'new_state',
        'changes',
        'notes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'previous_state' => 'array',
        'new_state' => 'array',
        'changes' => 'array',
    ];

    // Relationships
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeEdits($query)
    {
        return $query->where('action', 'edit');
    }

    public function scopeReverts($query)
    {
        return $query->where('action', 'revert');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForInvoice($query, $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    // Accessors
    public function getActionBadgeClassAttribute()
    {
        return match($this->action) {
            'edit' => 'primary',
            'revert' => 'warning',
            'create' => 'success',
            default => 'secondary'
        };
    }

    public function getActionIconAttribute()
    {
        return match($this->action) {
            'edit' => 'fas fa-edit',
            'revert' => 'fas fa-undo',
            'create' => 'fas fa-plus',
            default => 'fas fa-file'
        };
    }

    public function getFormattedChangesAttribute()
    {
        if (empty($this->changes)) {
            return 'No changes recorded';
        }

        $formatted = [];

        foreach ($this->changes as $field => $change) {
            if ($field === 'items') {
                $formatted[] = $this->formatItemChanges($change);
            } else {
                $formatted[] = $this->formatFieldChange($field, $change);
            }
        }

        return implode('<br>', $formatted);
    }

    public function getTotalAmountChangeAttribute()
    {
        if (!isset($this->changes['total_amount'])) {
            return null;
        }

        $from = $this->changes['total_amount']['from'] ?? 0;
        $to = $this->changes['total_amount']['to'] ?? 0;
        $difference = $to - $from;

        return [
            'from' => $from,
            'to' => $to,
            'difference' => $difference,
            'percentage' => $from > 0 ? round(($difference / $from) * 100, 2) : 0
        ];
    }

    public function getHasSignificantChangesAttribute()
    {
        $significantFields = ['total_amount', 'due_date', 'items'];
        
        foreach ($significantFields as $field) {
            if (isset($this->changes[$field])) {
                return true;
            }
        }

        return false;
    }

    // Helper methods
    private function formatFieldChange($field, $change)
    {
        $fieldName = ucfirst(str_replace('_', ' ', $field));
        $from = $change['from'] ?? 'null';
        $to = $change['to'] ?? 'null';

        if (in_array($field, ['total_amount', 'concession_amount'])) {
            $from = '₹' . number_format($from, 2);
            $to = '₹' . number_format($to, 2);
        }

        return "<strong>{$fieldName}:</strong> {$from} → {$to}";
    }

    private function formatItemChanges($itemChanges)
    {
        $changes = [];

        if (!empty($itemChanges['added'])) {
            $added = collect($itemChanges['added'])->pluck('fee_category_name')->implode(', ');
            $changes[] = "<strong>Added items:</strong> {$added}";
        }

        if (!empty($itemChanges['removed'])) {
            $removed = collect($itemChanges['removed'])->pluck('fee_category_name')->implode(', ');
            $changes[] = "<strong>Removed items:</strong> {$removed}";
        }

        if (!empty($itemChanges['modified'])) {
            foreach ($itemChanges['modified'] as $mod) {
                $name = $mod['fee_category_name'];
                $fromAmount = '₹' . number_format($mod['previous']['amount'], 2);
                $toAmount = '₹' . number_format($mod['new']['amount'], 2);
                $changes[] = "<strong>Modified {$name}:</strong> {$fromAmount} → {$toAmount}";
            }
        }

        return implode('<br>', $changes);
    }

    public function canRevert()
    {
        // Can revert if this is not the latest edit and the invoice hasn't been paid since this edit
        $latestEdit = static::where('invoice_id', $this->invoice_id)
                           ->latest()
                           ->first();

        return $latestEdit && $latestEdit->id !== $this->id;
    }

    public function getChangesSummary()
    {
        $summary = [
            'fields_changed' => count($this->changes),
            'amount_changed' => isset($this->changes['total_amount']),
            'items_changed' => isset($this->changes['items']),
            'dates_changed' => isset($this->changes['issue_date']) || isset($this->changes['due_date']),
            'concession_changed' => isset($this->changes['concession_amount']) || isset($this->changes['concession_notes'])
        ];

        return $summary;
    }
}