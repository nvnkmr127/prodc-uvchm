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
    public function getNextCounselorId(): ?int
    {
        // Get all active users with the 'counselor' role (lowercase), ordered by their creation date.
        // FIXED: Changed 'is_active' to 'status' based on your User model and migration
        $counselors = User::role('counselor')->where('status', 'active')->orderBy('id')->get();

        if ($counselors->isEmpty()) {
            // No counselors available to assign the lead to.
            return null;
        }

        // Find out who was assigned the last lead.
        // We store the ID of the last assigned user in the settings table.
        $lastAssignedId = Setting::where('key', 'last_assigned_counselor_id')->value('value');

        if (!$lastAssignedId) {
            // If no one has been assigned before, assign to the first counselor.
            $nextCounselor = $counselors->first();
        } else {
            // Find the index of the last assigned counselor in our list.
            $lastIndex = $counselors->search(fn($counselor) => $counselor->id == $lastAssignedId);

            if ($lastIndex === false || $lastIndex >= ($counselors->count() - 1)) {
                // If the last assignee is not found or was the last in the list, loop back to the first.
                $nextCounselor = $counselors->first();
            } else {
                // Assign to the next counselor in the list.
                $nextCounselor = $counselors[$lastIndex + 1];
            }
        }
        
        // Update the settings table with the ID of the counselor we are about to assign.
        Setting::updateOrCreate(
            ['key' => 'last_assigned_counselor_id'],
            ['value' => $nextCounselor->id]
        );

        return $nextCounselor->id;
    }
}