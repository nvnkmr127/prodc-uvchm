<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Enquiry;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendFollowUpReminders extends Command
{
    protected $signature = 'app:send-follow-up-reminders';
    protected $description = 'Sends daily follow-up reminders (System Push Only) to assigned counselors.';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $this->info('Checking for follow-ups scheduled for today...');

        // Find all enquiries with a follow-up date for today
        $enquiriesDueToday = Enquiry::whereDate('next_follow_up_date', Carbon::today())
                                    ->whereNotNull('assigned_to_user_id')
                                    ->with('assignedTo')
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
                $count = $userEnquiries->count();

                // Send System Notification (In-App Push) ONLY
                try {
                    $this->notificationService->send([
                        'title' => 'Follow-up Reminder',
                        'message' => "You have {$count} follow-ups scheduled for today.",
                        'type' => 'warning',     
                        'category' => 'general',
                        'priority' => 'high',   
                        'users' => [$user->id],  
                        
                        // [UPDATED] Point to the correct calendar route (/admin/calendar)
                        'action_url' => route('admin.calendar.index'), 
                        
                        'action_text' => 'View Calendar',
                        'requires_action' => true,
                        'play_sound' => true     
                    ]);
                    
                    $this->info("✅ System Notification sent to {$user->name} ({$count} enquiries).");

                } catch (\Exception $e) {
                    $this->error("❌ Failed to send system notification to {$user->name}: " . $e->getMessage());
                    Log::error("FollowUpReminder System Notification Error for {$user->id}: " . $e->getMessage());
                }
            }
        }

        $this->info('All reminders processed.');
        return 0;
    }
}