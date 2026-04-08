<?php

namespace App\Models;

use App\Traits\WebhookEnabled;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use WebhookEnabled;

    protected $fillable = ['user_id', 'leave_type_id', 'remaining_days', 'year'];
}
