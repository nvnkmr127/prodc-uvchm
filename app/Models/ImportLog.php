<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'batch_name',
        'course_name',
        'user_id',
        'user_name',
        'import_type',
        'status',
        'auto_create_invoices',
        'total_rows',
        'imported_count',
        'skipped_count',
        'rejected_count',
        'invoices_created',
        'invoice_errors_count',
        'settings',
        'summary',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'auto_create_invoices' => 'boolean',
        'settings' => 'array',
        'summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(ImportLogDetail::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Accessors
    public function getSuccessRateAttribute()
    {
        if ($this->total_rows === 0) {
            return 0;
        }
        
        return round(($this->imported_count / $this->total_rows) * 100, 2);
    }

    public function getDurationAttribute()
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }
        
        return $this->started_at->diffForHumans($this->completed_at, true);
    }

    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'completed' => 'success',
            'processing' => 'warning',
            'failed' => 'danger',
            default => 'secondary'
        };
    }

    public function getInvoiceSuccessRateAttribute()
    {
        if ($this->imported_count === 0) {
            return 0;
        }
        
        return round(($this->invoices_created / $this->imported_count) * 100, 2);
    }

    // Helper methods
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }

    public function markAsFailed()
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now()
        ]);
    }

    public function getImportedStudents()
    {
        return $this->details()
                   ->where('status', 'imported')
                   ->whereNotNull('student_id')
                   ->with('student')
                   ->get();
    }

    public function getRejectedRows()
    {
        return $this->details()
                   ->where('status', 'rejected')
                   ->get();
    }

    public function getErrorRows()
    {
        return $this->details()
                   ->where('status', 'error')
                   ->get();
    }
}

// ImportLogDetail Model
class ImportLogDetail extends Model
{
    protected $fillable = [
        'import_log_id',
        'row_data',
        'student_id',
        'student_name',
        'status',
        'message',
        'processed_at',
    ];

    protected $casts = [
        'row_data' => 'array',
        'processed_at' => 'datetime',
    ];

    // Relationships
    public function importLog(): BelongsTo
    {
        return $this->belongsTo(ImportLog::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // Scopes
    public function scopeImported($query)
    {
        return $query->where('status', 'imported');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeErrors($query)
    {
        return $query->where('status', 'error');
    }

    public function scopeSkipped($query)
    {
        return $query->where('status', 'skipped');
    }

    // Accessors
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'imported' => 'success',
            'skipped' => 'warning',
            'rejected' => 'danger',
            'error' => 'danger',
            default => 'secondary'
        };
    }

    public function getFormattedRowDataAttribute()
    {
        return collect($this->row_data)->map(function ($value, $key) {
            return ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
        })->implode(', ');
    }
}