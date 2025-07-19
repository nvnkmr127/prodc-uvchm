<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\WebhookEnabled;

class LeaveApplication extends Model
{
    use WebhookEnabled;

    protected $fillable = ['user_id', 'leave_type_id', 'start_date', 'end_date', 'reason', 'status', 'approved_by', 'admin_notes'];
}
