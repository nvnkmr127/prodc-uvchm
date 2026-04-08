<?php

namespace App\Models;

use App\Traits\WebhookEnabled;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    use HasFactory;
    use WebhookEnabled;

    protected $fillable = ['visitor_name', 'phone_number', 'purpose_of_visit', 'check_in_time', 'check_out_time', 'notes'];
}
