<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssetCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetCategoryController extends Controller
{
    public function index()
    {
        $categories = AssetCategory::latest()->get();

        return view('admin.asset_categories.index', compact('categories'));
    }

    public function create()
    {
        // This method is no longer used, but we keep it for completeness
        return view('admin.asset_categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:asset_categories,name',
        ]);

        AssetCategory::create($validated); // ✅ SECURE

        return redirect()->route('admin.asset-categories.index')->with('success', 'Asset category created.');
    }

    public function edit(AssetCategory $assetCategory)
    {
        // This method is no longer used for displaying the form
    }

    public function update(Request $request, AssetCategory $assetCategory)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('asset_categories')->ignore($assetCategory->id)],
        ]);

        $assetCategory->update($validated); // ✅ SECURE

        return redirect()->route('admin.asset-categories.index')->with('success', 'Asset category updated.');
    }

    public function destroy(AssetCategory $assetCategory)
    {
        $assetCategory->delete();

        return redirect()->route('admin.asset-categories.index')->with('success', 'Asset category deleted.');
    }
}
