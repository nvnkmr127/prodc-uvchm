<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Http\Request;

class AssetReportController extends Controller
{
    public function index(Request $request)
    {
        // Start with a query builder instance
        $query = Asset::query()->with('category');

        // If a category filter is applied, add it to the query
        if ($request->filled('category_id')) {
            $query->where('asset_category_id', $request->category_id);
        }

        // If a condition filter is applied, add it to the query
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        // Execute the query to get the filtered assets
        $assets = $query->latest()->get();

        // Get all categories for the filter dropdown
        $categories = AssetCategory::orderBy('name')->get();

        return view('admin.reports.assets.index', compact('assets', 'categories'));
    }
}
