<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentTypeRequest;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocumentTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DocumentType::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by category
        if ($request->has('category') && $request->category && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $documentTypes = $query->ordered()->paginate(5);

        // Get categories for filter dropdown
        $categories = DocumentType::distinct()
            ->whereNotNull('category')
            ->pluck('category')
            ->sort()
            ->values();

        return Inertia::render('Staff/DocumentTypes/Index', [
            'documentTypes' => [
                'data' => $documentTypes->items(),
                'current_page' => $documentTypes->currentPage(),
                'last_page' => $documentTypes->lastPage(),
                'per_page' => $documentTypes->perPage(),
                'total' => $documentTypes->total(),
                'from' => $documentTypes->firstItem(),
                'to' => $documentTypes->lastItem(),
            ],
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'status']),
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
    public function store(DocumentTypeRequest $request)
    {
        $data = $request->validated();

        // Handle document template uploads
        if ($request->hasFile('document_templates')) {
            $templates = [];
            foreach ($request->file('document_templates') as $file) {
                $path = $file->store('document-templates', 'public');
                $templates[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ];
            }
            $data['document_templates'] = $templates;
        }

        $documentType = DocumentType::create($data);

        return redirect()->route('staff.document-types.index')
            ->with('success', 'Document type created successfully.')
            ->with('documentType', $documentType);
    }

    /**
     * Display the specified resource.
     */
    public function show(DocumentType $documentType)
    {
        $documentType->load('transactions.resident');

        return Inertia::render('Staff/DocumentTypes/Show', [
            'documentType' => $documentType,
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
    public function update(DocumentTypeRequest $request, DocumentType $documentType)
    {
        $data = $request->validated();

        // Handle document template uploads
        if ($request->hasFile('document_templates')) {
            $templates = [];
            foreach ($request->file('document_templates') as $file) {
                $path = $file->store('document-templates', 'public');
                $templates[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ];
            }
            $data['document_templates'] = $templates;
        }

        $documentType->update($data);

        return redirect()->route('staff.document-types.index')
            ->with('success', 'Document type updated successfully.')
            ->with('documentType', $documentType->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DocumentType $documentType)
    {
        // Check if document type has transactions
        if ($documentType->transactions()->count() > 0) {
            return redirect()->route('staff.document-types.index')
                ->with('error', 'Cannot delete document type that has existing transactions.');
        }

        $documentType->delete();

        return redirect()->route('staff.document-types.index')
            ->with('success', 'Document type deleted successfully.');
    }

    /**
     * Toggle the active status of a document type
     */
    public function toggleStatus(DocumentType $documentType)
    {
        $documentType->update([
            'is_active' => !$documentType->is_active,
        ]);

        $status = $documentType->is_active ? 'activated' : 'deactivated';

        return redirect()->route('staff.document-types.index')
            ->with('success', "Document type {$status} successfully.");
    }
}
