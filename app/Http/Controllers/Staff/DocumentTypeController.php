<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Services\DocumentTypeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocumentTypeController extends Controller
{
    protected $documentTypeService;

    public function __construct(DocumentTypeService $documentTypeService)
    {
        $this->documentTypeService = $documentTypeService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->get('search'),
            'active' => $request->get('active') !== null ? filter_var($request->get('active'), FILTER_VALIDATE_BOOLEAN) : null,
            'category' => $request->get('category'),
        ];

        $documentTypes = $this->documentTypeService->getAll($filters);

        return Inertia::render('Staff/DocumentTypes/Index', [
            'documentTypes' => $documentTypes,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Staff/DocumentTypes/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:document_types,code',
            'description' => 'nullable|string',
            'fee_amount' => 'required|numeric|min:0',
            'required_documents' => 'nullable|array',
            'required_documents.*' => 'string',
            'required_fields' => 'nullable|array',
            'required_fields.*' => 'string',
            'processing_steps' => 'nullable|array',
            'processing_steps.*' => 'string',
            'processing_days' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'requires_payment' => 'boolean',
            'requires_approval' => 'boolean',
            'category' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $this->documentTypeService->create($validated);

        return redirect()->route('staff.document-types.index')
            ->with('success', 'Document type created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(DocumentType $documentType)
    {
        return Inertia::render('Staff/DocumentTypes/Show', [
            'documentType' => $documentType->load('transactions'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DocumentType $documentType)
    {
        return Inertia::render('Staff/DocumentTypes/Edit', [
            'documentType' => $documentType,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DocumentType $documentType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:document_types,code,' . $documentType->id,
            'description' => 'nullable|string',
            'fee_amount' => 'required|numeric|min:0',
            'required_documents' => 'nullable|array',
            'required_documents.*' => 'string',
            'required_fields' => 'nullable|array',
            'required_fields.*' => 'string',
            'processing_steps' => 'nullable|array',
            'processing_steps.*' => 'string',
            'processing_days' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'requires_payment' => 'boolean',
            'requires_approval' => 'boolean',
            'category' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $this->documentTypeService->update($documentType, $validated);

        return redirect()->route('staff.document-types.index')
            ->with('success', 'Document type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DocumentType $documentType)
    {
        try {
            $this->documentTypeService->delete($documentType);

            return redirect()->route('staff.document-types.index')
                ->with('success', 'Document type deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('staff.document-types.index')
                ->with('error', $e->getMessage());
        }
    }
}

