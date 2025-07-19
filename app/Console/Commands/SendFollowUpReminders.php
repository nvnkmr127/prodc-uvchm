<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Enquiry;
use App\Models\User;
use App\Notifications\FollowUpReminder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

class SendFollowUpReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-follow-up-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends daily follow-up reminders to assigned counselors for enquiries due today.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for follow-ups scheduled for today...');

        // Find all enquiries with a follow-up date for today
        $enquiriesDueToday = Enquiry::whereDate('next_follow_up_date', Carbon::today())
                                    ->whereNotNull('assigned_to_user_id')
                                    ->with('assignedTo') // Eager load the assigned user
                                    ->get();

        if ($enquiriesDueToday->isEmpty()) {
            $this->info('No follow-ups scheduled for today. Exiting.');
            return 0;
        }

        // Group the enquiries by the assigned user's ID
        $enquiriesByUser = $enquiriesDueToday->groupBy('assigned_to_user_id');
        
        $this->info("Found {$enquiriesDueToday->count()} follow-ups for {$enquiriesByUser->count()} users.");

        foreach ($enquiriesByUser as $userId => $userEnquiries) {
            $user = $userEnquiries->first()->assignedTo;

            if ($user) {
                // Send a single notification to the user with their collection of enquiries
                $user->notify(new FollowUpReminder($userEnquiries));
                $this->info("Reminder sent to {$user->name} for {$userEnquiries->count()} enquiries.");
            }
        }

        $this->info('All follow-up reminders have been sent successfully.');
        return 0;
    }
}
