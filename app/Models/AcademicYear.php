<?php

namespace App\Models;

use App\Traits\WebhookEnabled;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use HasFactory;
    use WebhookEnabled;

    protected $fillable = ['name', 'start_date', 'end_date', 'is_current'];
}
