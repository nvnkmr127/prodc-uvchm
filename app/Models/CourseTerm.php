<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\WebhookEnabled;

class CourseTerm extends Model
{
    use WebhookEnabled;
    use HasFactory;
    protected $fillable = ['course_id', 'name', 'type', 'sequence'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}