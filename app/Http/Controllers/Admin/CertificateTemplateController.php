<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CertificateTemplateController extends Controller
{
    public function index() 
    { 
        $templates = CertificateTemplate::latest()->get(); 
        return view('admin.certificate_templates.index', compact('templates')); 
    }

    public function create() 
    { 
        return view('admin.certificate_templates.create'); 
    }

    public function show(CertificateTemplate $certificateTemplate)
    {
        return view('admin.certificate_templates.show', compact('certificateTemplate'));
    }

    public function edit(CertificateTemplate $certificateTemplate) 
    { 
        return view('admin.certificate_templates.edit', compact('certificateTemplate')); 
    }

    public function store(Request $request) 
    {
        // FIXED: Single consistent validation
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:certificate_templates,name',
            'body' => 'required|string|min:10', // Ensure meaningful content
            'description' => 'nullable|string|max:500',
        ]);
        
        CertificateTemplate::create($validated);
        return redirect()->route('admin.certificate-templates.index')
            ->with('success', 'Certificate template created successfully.');
    }

    public function update(Request $request, CertificateTemplate $certificateTemplate) 
    {
        // FIXED: Single consistent validation with proper unique rule
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('certificate_templates')->ignore($certificateTemplate->id)],
            'body' => 'required|string|min:10',
            'description' => 'nullable|string|max:500',
        ]);
        
        $certificateTemplate->update($validated);
        return redirect()->route('admin.certificate-templates.index')
            ->with('success', 'Certificate template updated successfully.');
    }

    public function destroy(CertificateTemplate $certificateTemplate) 
    {
        // Check if template is being used before deletion
        if ($certificateTemplate->certificates()->count() > 0) {
            return redirect()->route('admin.certificate-templates.index')
                ->with('error', 'Cannot delete template. It is being used by existing certificates.');
        }

        $certificateTemplate->delete();
        return redirect()->route('admin.certificate-templates.index')
            ->with('success', 'Certificate template deleted successfully.');
    }
}