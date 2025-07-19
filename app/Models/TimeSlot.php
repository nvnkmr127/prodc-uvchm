<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\WebhookEnabled;

class TimeSlot extends Model
{
    use WebhookEnabled;
    use HasFactory;

    protected $fillable = [
        'start_time',
        'end_time',
    ];
}