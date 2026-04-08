<?php

namespace App\Models;

use App\Traits\WebhookEnabled;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseTerm extends Model
{
    use HasFactory;
    use WebhookEnabled;

    protected $fillable = ['course_id', 'name', 'type', 'sequence'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
