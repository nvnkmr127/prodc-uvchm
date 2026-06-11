<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdSequence extends Model
{
    protected $fillable = [
        'entity_type',
        'prefix',
        'last_number'
    ];
}
