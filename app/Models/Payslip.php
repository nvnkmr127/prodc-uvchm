<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\WebhookEnabled;

class Payslip extends Model
{
    use WebhookEnabled;
    use HasFactory;

    protected $fillable = ['user_id', 'month', 'year', 'gross_salary', 'total_deductions', 'net_salary', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}