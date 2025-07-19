<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\WebhookEnabled;

class Expense extends Model
{
    use WebhookEnabled;
    use HasFactory;
    protected $fillable = ['expense_category_id', 'description', 'amount', 'expense_date', 'vendor'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}