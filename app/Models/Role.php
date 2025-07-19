<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use App\Traits\WebhookEnabled;

class Role extends SpatieRole
{
    use WebhookEnabled;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'guard_name',
    ];
}
