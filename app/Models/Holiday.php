<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\WebhookEnabled;

class Holiday extends Model
{
    use WebhookEnabled;
    use HasFactory;

    protected $fillable = [
        'name',
        'date',
    ];
}