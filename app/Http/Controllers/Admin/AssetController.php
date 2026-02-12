<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Imports\AssetsImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SampleAssetsExport;

class AssetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $assets = Asset::with('category')->latest()->get();
        $categories = AssetCategory::orderBy('name')->get();
        return view('admin.assets.index', compact('assets', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $categories = AssetCategory::pluck('name', 'id');
        return view('admin.assets.create', compact('categories'));
    }
public function import(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:xlsx,xls,csv',
    ]);

    try {
        // Process import using maatwebsite/excel
        Excel::import(new AssetsImport, $request->file('file'));
        
        return back()->with('success', 'Assets imported successfully.');
    } catch (\Exception $e) {
        return back()->with('error', 'Import failed: ' . $e->getMessage());
    }
}
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'asset_code' => 'nullable|string|max:255|unique:assets,asset_code',
            'asset_category_id' => 'required|exists:asset_categories,id',
            'location' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'condition' => ['required', Rule::in(['Good', 'Fair', 'Needs Repair', 'Damaged', 'Missing'])],
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
        ]);

        $validated = $request->validate([
            'asset_category_id' => 'required|exists:asset_categories,id',
            'name' => 'required|string|max:255',
            'asset_tag' => 'required|string|max:100|unique:assets,asset_tag',
            'purchase_date' => 'required|date|before_or_equal:today',
            'purchase_price' => 'required|numeric|min:0',
            'condition' => 'required|in:new,good,fair,poor,damaged',
            'location' => 'nullable|string|max:255',
        ]);
        Asset::create($validated);
        return redirect()->route('admin.assets.index')->with('success', 'Asset created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Asset  $asset
     * @return \Illuminate\View\View
     */
    public function show(Asset $asset)
    {
        return view('admin.assets.show', compact('asset'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Asset  $asset
     * @return \Illuminate\View\View
     */
    public function edit(Asset $asset)
    {
        $categories = AssetCategory::pluck('name', 'id');
        return view('admin.assets.edit', compact('asset', 'categories'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Asset $asset)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'asset_code' => ['nullable','string','max:255', Rule::unique('assets')->ignore($asset->id)],
            'asset_category_id' => 'required|exists:asset_categories,id',
            'location' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'condition' => ['required', Rule::in(['Good', 'Fair', 'Needs Repair', 'Damaged', 'Missing'])],
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
        ]);

        $validated = $request->validate([
            'asset_category_id' => 'required|exists:asset_categories,id',
            'name' => 'required|string|max:255',
            'purchase_date' => 'required|date|before_or_equal:today',
            'purchase_price' => 'required|numeric|min:0',
            'condition' => 'required|in:new,good,fair,poor,damaged',
            'location' => 'nullable|string|max:255',
        ]);
        $asset->update($validated);
        return redirect()->route('admin.assets.index')->with('success', 'Asset updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Asset $asset)
    {
        $asset->delete();
        return redirect()->route('admin.assets.index')->with('success', 'Asset deleted successfully.');
    }

    /**
     * Handle bulk deletion of assets.
     */
public function bulkDestroy(Request $request)
{
    try {
        $assetIds = $request->input('asset_ids', []);
        
        if (empty($assetIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No assets selected for deletion.'
            ]);
        }

        $deletedCount = \App\Models\Asset::whereIn('id', $assetIds)->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deletedCount} assets deleted successfully."
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete assets: ' . $e->getMessage()
        ]);
    }
}
     /**
     * Handle the bulk import of assets from an Excel/CSV file.
     */
    public function importAssets(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new AssetsImport, $request->file('file'));
            
            return redirect()->route('admin.assets.index')
                             ->with('success', 'Assets imported successfully.');

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
             $failures = $e->failures();
             $errorMessages = [];
             foreach ($failures as $failure) {
                 $errorMessages[] = "Row " . $failure->row() . ": " . implode(", ", $failure->errors());
             }
             return redirect()->route('admin.assets.index')
                               ->with('error', 'Import failed. Please correct the following errors: ' . implode(' | ', $errorMessages));
        } catch (\Exception $e) {
            return redirect()->route('admin.assets.index')
                             ->with('error', 'An unexpected error occurred during import: ' . $e->getMessage());
        }
    }

    /**
     * Download the sample format file for bulk importing assets.
     */
    public function downloadSample()
    {
        return Excel::download(new SampleAssetsExport, 'sample_assets.xlsx');
    }

}
