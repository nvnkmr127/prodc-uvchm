<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\WebhookEnabled;

class LeaveBalance extends Model
{
    use WebhookEnabled;

    protected $fillable = ['user_id', 'leave_type_id', 'remaining_days', 'year'];
}
