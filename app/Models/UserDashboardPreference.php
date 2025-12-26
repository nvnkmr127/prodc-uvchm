<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDashboardPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'dashboard_id',
        'layout_preferences',
        'widget_preferences',
        'filter_preferences',
        'is_customized',
        'last_accessed_at'
    ];

    protected $casts = [
        'layout_preferences' => 'array',
        'widget_preferences' => 'array',
        'filter_preferences' => 'array',
        'is_customized' => 'boolean',
        'last_accessed_at' => 'datetime'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dashboard()
    {
        return $this->belongsTo(Dashboard::class);
    }

    // Helper Methods
    public function updateLastAccessed()
    {
        $this->update(['last_accessed_at' => now()]);
    }

    public function hasCustomLayout()
    {
        return !empty($this->layout_preferences);
    }

    public function hasCustomWidgets()
    {
        return !empty($this->widget_preferences);
    }

    public function resetToDefault()
    {
        $this->update([
            'layout_preferences' => null,
            'widget_preferences' => null,
            'filter_preferences' => null,
            'is_customized' => false
        ]);
    }
}