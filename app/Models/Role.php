<?php

namespace App\Models;

use App\Traits\WebhookEnabled;
use Spatie\Permission\Models\Role as SpatieRole;

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
