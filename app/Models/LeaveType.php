<?php

namespace App\Models;

use App\Traits\WebhookEnabled;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use WebhookEnabled;

    protected $fillable = ['name', 'days_per_year'];
}
