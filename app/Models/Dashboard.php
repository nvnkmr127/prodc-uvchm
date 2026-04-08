<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class Dashboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'role_id',
        'layout',
        'config',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'layout' => 'array',
        'config' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($dashboard) {
            if (empty($dashboard->slug)) {
                $dashboard->slug = Str::slug($dashboard->name);
            }
        });
    }

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function widgets()
    {
        if (! class_exists('App\\Models\\DashboardWidget')) {
            return $this->hasMany(UserDashboardPreference::class)->whereRaw('1 = 0');
        }

        return $this->hasMany('App\\Models\\DashboardWidget')->orderBy('order');
    }

    public function activeWidgets()
    {
        if (! class_exists('App\\Models\\DashboardWidget')) {
            return $this->hasMany(UserDashboardPreference::class)->whereRaw('1 = 0');
        }

        return $this->hasMany('App\\Models\\DashboardWidget')->where('is_visible', true)->orderBy('order');
    }

    public function userPreferences()
    {
        return $this->hasMany(UserDashboardPreference::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForRole($query, $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Helper Methods
    public function getWidgetCount()
    {
        return $this->widgets()->count();
    }

    public function canBeViewedBy($user)
    {
        return $user->hasRole($this->role->name) || $user->hasPermissionTo('view all dashboards');
    }

    public function canBeEditedBy($user)
    {
        return $user->hasPermissionTo('edit dashboards') &&
               ($user->hasRole($this->role->name) || $user->hasRole('super-admin'));
    }
}
