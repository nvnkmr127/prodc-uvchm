<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens; // Add this import
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\WebhookEnabled;

class User extends Authenticatable
{
    use WebhookEnabled;

    use HasFactory, Notifiable, HasRoles, HasApiTokens; // Add HasApiTokens trait

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
   protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'email_verified_at'
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    

    /**
     * Get the student profile associated with the user.
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }
    
 /**
     * Get the user's status badge color
     */
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'active' => 'success',
            'inactive' => 'secondary',
            'suspended' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Check if user is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is suspended
     */
    public function isSuspended()
    {
        return $this->status === 'suspended';
    }
    
    public function dashboardPreferences()
{
    return $this->hasMany(UserDashboardPreference::class);
}

public function getDefaultDashboard()
{
    $userRoles = $this->getRoleNames();
    
    if ($userRoles->isEmpty()) {
        return null;
    }
    
    // Get the first role's default dashboard
    $roleName = $userRoles->first();
    $role = Role::where('name', $roleName)->first();
    
    if (!$role) {
        return null;
    }
    
    return Dashboard::where('role_id', $role->id)
                   ->where('is_default', true)
                   ->where('is_active', true)
                   ->first();
}

public function getDashboardForRole($roleName = null)
{
    if (!$roleName) {
        $roleName = $this->getRoleNames()->first();
    }
    
    $role = Role::where('name', $roleName)->first();
    
    if (!$role) {
        return null;
    }
    
    // Check for user-customized dashboard first
    $preference = $this->dashboardPreferences()
                      ->whereHas('dashboard', function ($query) use ($role) {
                          $query->where('role_id', $role->id);
                      })
                      ->orderBy('last_accessed_at', 'desc')
                      ->first();
    
    if ($preference) {
        return $preference->dashboard;
    }
    
    // Fall back to default dashboard for role
    return Dashboard::where('role_id', $role->id)
                   ->where('is_default', true)
                   ->where('is_active', true)
                   ->first();
}

    /**
     * Get the subjects assigned to this faculty member.
     */
public function subjects()
{
    return $this->belongsToMany(Subject::class, 'subject_user', 'user_id', 'subject_id')
                ->withTimestamps();
}

    /**
     * Get the salary structure for this user.
     */
    public function salaryStructure()
    {
        return $this->hasMany(UserSalaryStructure::class);
    }
}