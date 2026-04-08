<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IdCardTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IdCardTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templates = IdCardTemplate::latest()->get();

        return view('admin.id_card_templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.id_card_templates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // FIXED: Single consistent validation
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:id_card_templates,name',
            'content' => 'required|string|min:10', // Ensure meaningful content
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        // Set default values if not provided
        $validated['is_active'] = $validated['is_active'] ?? true;

        IdCardTemplate::create($validated);

        return redirect()->route('admin.id-card-templates.index')
            ->with('success', 'ID Card Template created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(IdCardTemplate $idCardTemplate)
    {
        return view('admin.id_card_templates.show', compact('idCardTemplate'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(IdCardTemplate $idCardTemplate)
    {
        return view('admin.id_card_templates.edit', compact('idCardTemplate'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, IdCardTemplate $idCardTemplate)
    {
        // FIXED: Single consistent validation with proper unique rule
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('id_card_templates')->ignore($idCardTemplate->id)],
            'content' => 'required|string|min:10',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $idCardTemplate->update($validated);

        return redirect()->route('admin.id-card-templates.index')
            ->with('success', 'ID Card Template updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IdCardTemplate $idCardTemplate)
    {
        // Check if template is being used before deletion
        if ($idCardTemplate->idCards()->count() > 0) {
            return redirect()->route('admin.id-card-templates.index')
                ->with('error', 'Cannot delete template. It is being used by existing ID cards.');
        }

        $idCardTemplate->delete();

        return redirect()->route('admin.id-card-templates.index')
            ->with('success', 'ID Card Template deleted successfully.');
    }

    /**
     * Toggle template active status
     */
    public function toggleStatus(IdCardTemplate $idCardTemplate)
    {
        $idCardTemplate->update(['is_active' => ! $idCardTemplate->is_active]);

        $status = $idCardTemplate->is_active ? 'activated' : 'deactivated';

        return redirect()->route('admin.id-card-templates.index')
            ->with('success', "Template {$status} successfully.");
    }
}
