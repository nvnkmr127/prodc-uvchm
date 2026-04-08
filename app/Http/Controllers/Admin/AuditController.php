<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Audit;
use App\Models\AuditItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditController extends Controller
{
    public function index()
    {
        $audits = Audit::with('user')->latest()->get();

        return view('admin.audits.index', compact('audits'));
    }

    public function store(Request $request)
    {
        $newAudit = Audit::create([
            'audit_date' => Carbon::now(),
            'audited_by_user_id' => Auth::id(),
            'status' => 'In Progress',
        ]);

        return redirect()->route('admin.audits.show', $newAudit)
            ->with('success', 'New audit session #'.$newAudit->id.' has been started!');
    }

    public function show(Request $request, Audit $audit)
    {
        $audit->load('items');
        $categories = AssetCategory::orderBy('name')->get();

        $query = Asset::query()->with('category');

        if ($request->filled('category_id')) {
            $query->where('asset_category_id', $request->category_id);
        }
        if ($request->filled('location')) {
            $query->where('location', 'LIKE', '%'.addcslashes($request->location, '%_\\').'%');
        }

        $assets = $query->get();

        // Get all items already audited in this session for quick lookup
        $audit_items = $audit->items->keyBy('asset_id');

        return view('admin.audits.show', compact('audit', 'assets', 'categories', 'audit_items'));
    }

    // This method saves the status for a single asset in the audit
    public function saveItemStatus(Request $request, Audit $audit)
    {
        $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'status' => 'required|in:Found,Missing,Damaged',
        ]);

        // Don't allow changes if audit is already complete
        if ($audit->status == 'Completed') {
            return redirect()->back()->with('error', 'This audit is already completed and cannot be modified.');
        }

        AuditItem::updateOrCreate(
            ['audit_id' => $audit->id, 'asset_id' => $request->asset_id],
            ['status' => $request->status]
        );

        return redirect()->back()->with('success', 'Asset status updated.');
    }

    // This method finalizes the audit
    public function complete(Audit $audit)
    {
        if ($audit->status == 'Completed') {
            return redirect()->back()->with('error', 'This audit is already completed.');
        }

        // Optional: Update the main asset condition based on audit results
        $auditItems = $audit->items()->whereIn('status', ['Missing', 'Damaged'])->get();
        foreach ($auditItems as $item) {
            $asset = Asset::find($item->asset_id);
            if ($asset) {
                $asset->condition = $item->status; // Update condition to 'Missing' or 'Damaged'
                $asset->save();
            }
        }

        $audit->status = 'Completed';
        $audit->save();

        return redirect()->route('admin.audits.index')->with('success', 'Audit #'.$audit->id.' has been completed.');
    }
}
