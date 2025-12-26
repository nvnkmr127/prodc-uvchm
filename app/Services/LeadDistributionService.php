<?php

namespace App\Services;

use App\Models\User;
use App\Models\Setting;

class LeadDistributionService
{
    /**
     * Assigns the next available counselor in a round-robin sequence.
     *
     * @return int|null The ID of the next counselor, or null if none are available.
     */
    /**
     * Assigns the next available user with the given roles in a round-robin sequence.
     */
    private function getNextUserForRole(array $roles, string $settingKey): ?int
    {
        // Get all active users with the specified roles, ordered by ID
        $users = User::whereHas('roles', function ($q) use ($roles) {
            $q->whereIn('name', $roles);
        })->where('status', 'active')->orderBy('id')->get();

        if ($users->isEmpty()) {
            return null;
        }

        // Find out who was assigned last
        $lastAssignedId = Setting::where('key', $settingKey)->value('value');

        if (!$lastAssignedId) {
            $nextUser = $users->first();
        } else {
            $lastIndex = $users->search(fn($u) => $u->id == $lastAssignedId);

            if ($lastIndex === false || $lastIndex >= ($users->count() - 1)) {
                $nextUser = $users->first();
            } else {
                $nextUser = $users[$lastIndex + 1];
            }
        }

        // Update the settings table
        Setting::updateOrCreate(
            ['key' => $settingKey],
            ['value' => $nextUser->id]
        );

        return $nextUser->id;
    }

    /**
     * Assigns the next available counselor.
     */
    public function getNextCounselorId(): ?int
    {
        return $this->getNextUserForRole(['counselor', 'Counselor'], 'last_assigned_counselor_id');
    }

    /**
     * Assigns the next available college admin.
     */
    public function getNextCollegeAdminId(): ?int
    {
        return $this->getNextUserForRole(['college-admin', 'College-admin'], 'last_assigned_college_admin_id');
    }
}