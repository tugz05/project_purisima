<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Models\DocumentType;
use App\Services\CertificateTemplateService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CertificateTemplateController extends Controller
{
    protected $templateService;

    public function __construct(CertificateTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Display templates for a specific document type
     */
    public function index(Request $request, DocumentType $documentType)
    {
        $filters = [
            'search' => $request->get('search'),
            'active' => $request->get('active') !== null ? filter_var($request->get('active'), FILTER_VALIDATE_BOOLEAN) : null,
        ];

        $templates = $this->templateService->getTemplatesForDocumentType($documentType->id, $filters);

        return Inertia::render('Staff/CertificateTemplates/Index', [
            'documentType' => $documentType,
            'templates' => $templates,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new template
     */
    public function create(DocumentType $documentType)
    {
        return Inertia::render('Staff/CertificateTemplates/Create', [
            'documentType' => $documentType,
        ]);
    }

    /**
     * Store a newly created template
     */
    public function store(Request $request, DocumentType $documentType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_content' => 'required|string',
            'required_fields' => 'nullable|array',
            'required_fields.*.name' => 'required|string',
            'required_fields.*.label' => 'required|string',
            'required_fields.*.type' => 'required|string|in:text,textarea,date,time,number,email',
            'required_fields.*.required' => 'boolean',
            'required_fields.*.placeholder' => 'nullable|string',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['document_type_id'] = $documentType->id;

        $this->templateService->create($validated);

        return redirect()->route('staff.document-types.certificate-templates.index', $documentType)
            ->with('success', 'Certificate template created successfully.');
    }

    /**
     * Display the specified template
     */
    public function show(DocumentType $documentType, CertificateTemplate $certificateTemplate)
    {
        $requiredFields = $this->templateService->getRequiredFields($certificateTemplate);

        return Inertia::render('Staff/CertificateTemplates/Show', [
            'documentType' => $documentType,
            'template' => $certificateTemplate,
            'requiredFields' => $requiredFields,
        ]);
    }

    /**
     * Show the form for editing the specified template
     */
    public function edit(DocumentType $documentType, CertificateTemplate $certificateTemplate)
    {
        $requiredFields = $this->templateService->getRequiredFields($certificateTemplate);

        return Inertia::render('Staff/CertificateTemplates/Edit', [
            'documentType' => $documentType,
            'template' => $certificateTemplate,
            'requiredFields' => $requiredFields,
        ]);
    }

    /**
     * Update the specified template
     */
    public function update(Request $request, DocumentType $documentType, CertificateTemplate $certificateTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_content' => 'required|string',
            'required_fields' => 'nullable|array',
            'required_fields.*.name' => 'required|string',
            'required_fields.*.label' => 'required|string',
            'required_fields.*.type' => 'required|string|in:text,textarea,date,time,number,email',
            'required_fields.*.required' => 'boolean',
            'required_fields.*.placeholder' => 'nullable|string',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $this->templateService->update($certificateTemplate, $validated);

        return redirect()->route('staff.document-types.certificate-templates.index', $documentType)
            ->with('success', 'Certificate template updated successfully.');
    }

    /**
     * Remove the specified template
     */
    public function destroy(DocumentType $documentType, CertificateTemplate $certificateTemplate)
    {
        $this->templateService->delete($certificateTemplate);

        return redirect()->route('staff.document-types.certificate-templates.index', $documentType)
            ->with('success', 'Certificate template deleted successfully.');
    }

    /**
     * Set template as default
     */
    public function setDefault(DocumentType $documentType, CertificateTemplate $certificateTemplate)
    {
        $this->templateService->setAsDefault($certificateTemplate);

        return redirect()->back()
            ->with('success', 'Template set as default successfully.');
    }

    /**
     * Preview template with sample data
     */
    public function preview(DocumentType $documentType, CertificateTemplate $certificateTemplate, Request $request)
    {
        $sampleData = $request->get('data', []);
        
        // Add default values for common tags
        $defaults = [
            'date' => now()->format('F d, Y'),
            'time' => now()->format('h:i A'),
            'year' => now()->year,
            'month' => now()->format('F'),
            'day' => now()->day,
            'barangay' => 'Barangay Purisima',
            'municipality' => 'Tago',
            'province' => 'Surigao del Sur',
        ];

        $data = array_merge($defaults, $sampleData);
        $processedContent = $this->templateService->processTemplate($certificateTemplate, $data);

        return response()->json([
            'content' => $processedContent,
        ]);
    }

    /**
     * Get required fields for a template
     */
    public function getFields(DocumentType $documentType, CertificateTemplate $certificateTemplate)
    {
        $fields = $this->templateService->getRequiredFields($certificateTemplate);

        return response()->json([
            'fields' => $fields,
        ]);
    }
}
