<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Audit;
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

    // This method finalizes the audit
}
