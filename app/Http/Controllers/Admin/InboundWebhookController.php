<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InboundWebhook;
use App\Models\Enquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InboundWebhookController extends Controller
{
    public function index()
    {
        $webhooks = InboundWebhook::latest()->paginate(10);
        return view('admin.inbound_webhooks.index', compact('webhooks'));
    }

    public function logs(InboundWebhook $inboundWebhook)
    {
        $logs = $inboundWebhook->logs()->with('enquiry')->latest()->paginate(50);
        return view('admin.inbound_webhooks.logs', compact('inboundWebhook', 'logs'));
    }

    public function create()
    {
        $counselors = \App\Models\User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'super-admin', 'college-admin', 'counselor']);
        })->where('status', 'active')->orderBy('name')->get();

        return view('admin.inbound_webhooks.create', compact('counselors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:inbound_webhooks,slug',
            'description' => 'nullable|string',
            'source_name' => 'nullable|string',
            'auto_followup_days' => 'required|integer|min:0',
            'auto_assign' => 'boolean',
            'assigned_to_user_id' => 'nullable|exists:users,id',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(5);
        }

        $validated['secret_token'] = Str::random(32);
        $validated['created_by'] = Auth::id();

        $webhook = InboundWebhook::create($validated);

        Log::channel('inbound-webhooks')->info('Inbound webhook created from admin panel', [
            'webhook_id' => $webhook->id,
            'slug' => $webhook->slug,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.inbound-webhooks.index')->with('success', 'Inbound Webhook created successfully.');
    }

    public function show(InboundWebhook $inboundWebhook)
    {
        $inboundWebhook->load(['logs' => function($query) {
            $query->latest()->limit(50);
        }, 'logs.enquiry']);

        // Define fields available for mapping
        $enquiryFields = [
            'student_name' => 'Student Name',
            'phone_number' => 'Phone Number',
            'course_name'  => 'Course Name',
            'gender'       => 'Gender',
            'date_of_birth'=> 'Date of Birth',
            'address'      => 'Address',
            'education_qualification' => 'Education Qualification',
            'referral_name' => 'Referral Name',
            'notes'        => 'Notes/Comments',
            'email'        => 'Email Address',
        ];

        return view('admin.inbound_webhooks.show', compact('inboundWebhook', 'enquiryFields'));
    }

    public function updateMapping(Request $request, InboundWebhook $inboundWebhook)
    {
        $mapping = $request->input('mapping', []);

        $inboundWebhook->update([
            'mapping_rules' => $mapping
        ]);

        Log::channel('inbound-webhooks')->info('Inbound webhook mapping updated', [
            'webhook_id' => $inboundWebhook->id,
            'slug' => $inboundWebhook->slug,
            'updated_by' => Auth::id(),
            'mapping_keys' => array_keys($mapping),
        ]);

        return back()->with('success', 'Mapping updated successfully.');
    }

    public function toggle(InboundWebhook $inboundWebhook)
    {
        $inboundWebhook->update(['is_active' => !$inboundWebhook->is_active]);

        Log::channel('inbound-webhooks')->info('Inbound webhook status toggled', [
            'webhook_id' => $inboundWebhook->id,
            'slug' => $inboundWebhook->slug,
            'is_active' => (bool) $inboundWebhook->fresh()->is_active,
            'updated_by' => Auth::id(),
        ]);

        return back()->with('success', 'Status updated.');
    }

    public function destroy(InboundWebhook $inboundWebhook)
    {
        $webhookContext = [
            'webhook_id' => $inboundWebhook->id,
            'slug' => $inboundWebhook->slug,
            'deleted_by' => Auth::id(),
        ];

        $inboundWebhook->delete();

        Log::channel('inbound-webhooks')->warning('Inbound webhook deleted from admin panel', $webhookContext);

        return redirect()->route('admin.inbound-webhooks.index')->with('success', 'Webhook deleted.');
    }

    public function bulkAction(Request $request)
    {
        $ids = array_values(array_filter(array_map('trim', explode(',', $request->input('webhook_ids', '')))));
        $action = $request->input('action');

        if (empty($ids) || empty($action)) {
            return back()->with('error', 'No items or action selected.');
        }

        $webhooks = InboundWebhook::whereIn('id', $ids);
        $affectedCount = 0;

        switch ($action) {
            case 'activate':
                $affectedCount = $webhooks->update(['is_active' => true]);
                $msg = 'Selected webhooks activated.';
                break;
            case 'deactivate':
                $affectedCount = $webhooks->update(['is_active' => false]);
                $msg = 'Selected webhooks deactivated.';
                break;
            case 'delete':
                $affectedCount = $webhooks->delete();
                $msg = 'Selected webhooks deleted.';
                break;
            default:
                return back()->with('error', 'Invalid action.');
        }

        Log::channel('inbound-webhooks')->info('Inbound webhook bulk action executed', [
            'action' => $action,
            'selected_ids' => $ids,
            'affected_count' => $affectedCount,
            'updated_by' => Auth::id(),
        ]);

        return back()->with('success', $msg);
    }
}
