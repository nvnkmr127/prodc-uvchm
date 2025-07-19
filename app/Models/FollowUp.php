<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Traits\WebhookEnabled;

class FollowUp extends Model
{
    use WebhookEnabled;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id', 
        'notes', 
        'followable_id', 
        'followable_type'
    ];

    /**
     * Get the parent followable model (Enquiry or Admission).
     */
    public function followable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created the follow-up note.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
