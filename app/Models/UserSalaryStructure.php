<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\WebhookEnabled;

class UserSalaryStructure extends Model
{
    use WebhookEnabled;
    use HasFactory;

    protected $fillable = ['user_id', 'salary_component_id', 'amount'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salaryComponent(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class);
    }
}