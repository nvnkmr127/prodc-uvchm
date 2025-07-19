<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\WebhookEnabled;

class LeaveType extends Model
{
    use WebhookEnabled;

   protected $fillable = ['name', 'days_per_year'];
}
