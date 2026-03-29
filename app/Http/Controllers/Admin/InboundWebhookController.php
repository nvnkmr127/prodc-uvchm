<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InboundWebhook;
use App\Models\Enquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class InboundWebhookController extends Controller
{
    public function index()
    {
        $webhooks = InboundWebhook::latest()->paginate(10);
        return view('admin.inbound_webhooks.index', compact('webhooks'));
    }

    public function create()
    {
        return view('admin.inbound_webhooks.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:inbound_webhooks,slug',
            'description' => 'nullable|string',
            'source_name' => 'nullable|string',
            'auto_followup_days' => 'required|integer|min:0',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(5);
        }

        $validated['secret_token'] = Str::random(32);
        $validated['created_by'] = Auth::id();

        InboundWebhook::create($validated);

        return redirect()->route('admin.inbound-webhooks.index')->with('success', 'Inbound Webhook created successfully.');
    }

    public function show(InboundWebhook $inboundWebhook)
    {
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
        $inboundWebhook->update([
            'mapping_rules' => $request->input('mapping', [])
        ]);

        return back()->with('success', 'Mapping updated successfully.');
    }

    public function toggle(InboundWebhook $inboundWebhook)
    {
        $inboundWebhook->update(['is_active' => !$inboundWebhook->is_active]);
        return back()->with('success', 'Status updated.');
    }

    public function destroy(InboundWebhook $inboundWebhook)
    {
        $inboundWebhook->delete();
        return redirect()->route('admin.inbound-webhooks.index')->with('success', 'Webhook deleted.');
    }

    public function bulkAction(Request $request)
    {
        $ids = explode(',', $request->input('webhook_ids', ''));
        $action = $request->input('action');

        if (empty($ids) || empty($action)) {
            return back()->with('error', 'No items or action selected.');
        }

        $webhooks = InboundWebhook::whereIn('id', $ids);

        switch ($action) {
            case 'activate':
                $webhooks->update(['is_active' => true]);
                $msg = 'Selected webhooks activated.';
                break;
            case 'deactivate':
                $webhooks->update(['is_active' => false]);
                $msg = 'Selected webhooks deactivated.';
                break;
            case 'delete':
                $webhooks->delete();
                $msg = 'Selected webhooks deleted.';
                break;
            default:
                return back()->with('error', 'Invalid action.');
        }

        return back()->with('success', $msg);
    }
}
