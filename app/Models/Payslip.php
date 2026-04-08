<?php

namespace App\Models;

use App\Traits\WebhookEnabled;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use HasFactory;
    use WebhookEnabled;

    protected $fillable = ['user_id', 'month', 'year', 'gross_salary', 'total_deductions', 'net_salary', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
