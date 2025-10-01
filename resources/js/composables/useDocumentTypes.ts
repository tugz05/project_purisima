import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

export interface DocumentType {
    id: number;
    code: string;
    name: string;
    description?: string;
    fee_amount: number;
    required_documents?: string[];
    processing_steps?: string[];
    processing_days: number;
    is_active: boolean;
    requires_payment: boolean;
    requires_approval: boolean;
    category?: string;
    sort_order: number;
    notes?: string;
    transactions_count?: number;
    created_at: string;
    updated_at: string;
    formatted_fee?: string;
    is_free?: boolean;
}

export interface DocumentTypeFormData {
    name: string;
    description: string;
    fee_amount: number;
    required_documents: string[];
    document_templates: File[];
    processing_steps: string[];
    processing_days: number;
    is_active: boolean;
    requires_payment: boolean;
    requires_approval: boolean;
    category: string;
    sort_order: number;
    notes: string;
}

export const useDocumentTypes = () => {
    // State for delete dialog
    const showDeleteDialog = ref(false);
    const documentTypeToDelete = ref<DocumentType | null>(null);
    const deleteForm = useForm({});

    // State for undo operations
    const lastDeletedDocumentType = ref<DocumentType | null>(null);
    const lastCreatedDocumentType = ref<DocumentType | null>(null);
    const lastEditedDocumentType = ref<{ original: DocumentType; updated: DocumentType } | null>(null);

    // Create form for new document types
    const createDocumentTypeForm = (initialData: Partial<DocumentTypeFormData> = {}) => {
        return useForm<DocumentTypeFormData>({
            name: initialData.name || '',
            description: initialData.description || '',
            fee_amount: initialData.fee_amount || 0,
            required_documents: initialData.required_documents || [''],
            document_templates: initialData.document_templates || [],
            processing_steps: initialData.processing_steps || [''],
            processing_days: initialData.processing_days || 1,
            is_active: initialData.is_active ?? true,
            requires_payment: initialData.requires_payment ?? true,
            requires_approval: initialData.requires_approval ?? false,
            category: initialData.category || '',
            sort_order: initialData.sort_order || 0,
            notes: initialData.notes || '',
        });
    };

    // Edit form for existing document types
    const createEditForm = (documentType: DocumentType) => {
        return useForm<DocumentTypeFormData>({
            name: documentType.name,
            description: documentType.description || '',
            fee_amount: documentType.fee_amount,
            required_documents: documentType.required_documents || [''],
            processing_steps: documentType.processing_steps || [''],
            processing_days: documentType.processing_days,
            is_active: documentType.is_active,
            requires_payment: documentType.requires_payment,
            requires_approval: documentType.requires_approval,
            category: documentType.category || '',
            sort_order: documentType.sort_order,
            notes: documentType.notes || '',
        });
    };

    // Delete operations
    const openDeleteDialog = (documentType: DocumentType) => {
        documentTypeToDelete.value = documentType;
        showDeleteDialog.value = true;
    };

    const confirmDelete = () => {
        if (documentTypeToDelete.value) {
            const documentType = documentTypeToDelete.value;
            lastDeletedDocumentType.value = documentType;

            deleteForm.delete(`/staff/document-types/${documentType.id}`, {
                onSuccess: () => {
                    closeDeleteDialog();
                    toast('Document Type Deleted', {
                        description: `${documentType.name} has been permanently deleted from the system.`,
                        action: {
                            label: 'Undo',
                            onClick: () => undoDelete(documentType),
                        },
                    });
                },
                onError: () => {
                    toast('Delete Failed', {
                        description: 'Failed to delete the document type. Please try again.',
                    });
                }
            });
        }
    };

    const closeDeleteDialog = () => {
        showDeleteDialog.value = false;
        documentTypeToDelete.value = null;
    };

    // Toggle status
    const toggleStatus = (documentType: DocumentType) => {
        const form = useForm({});
        const originalStatus = documentType.is_active;

        form.post(`/staff/document-types/${documentType.id}/toggle-status`, {
            onSuccess: () => {
                const newStatus = !originalStatus;
                toast('Status Updated', {
                    description: `${documentType.name} is now ${newStatus ? 'active' : 'inactive'}.`,
                    action: {
                        label: 'Undo',
                        onClick: () => undoStatusToggle(documentType.id, originalStatus),
                    },
                });
            },
            onError: () => {
                toast('Status Update Failed', {
                    description: 'Failed to update the document type status. Please try again.',
                });
            }
        });
    };

    // Form helpers for dynamic arrays
    const addRequiredDocument = (form: any) => {
        form.required_documents.push('');
    };

    const removeRequiredDocument = (form: any, index: number) => {
        form.required_documents.splice(index, 1);
    };

    const addProcessingStep = (form: any) => {
        form.processing_steps.push('');
    };

    const removeProcessingStep = (form: any, index: number) => {
        form.processing_steps.splice(index, 1);
    };

    // File upload functions
    const addDocumentTemplate = (form: any, files: FileList) => {
        Array.from(files).forEach(file => {
            form.document_templates.push(file);
        });
    };

    const removeDocumentTemplate = (form: any, index: number) => {
        form.document_templates.splice(index, 1);
    };

    // Form submission
    const submitCreateForm = (form: any) => {
        form.post('/staff/document-types', {
            onSuccess: (page: any) => {
                // Get the document type from session flash data or from the page props
                const newDocumentType = page.props.documentType || page.props.documentTypes?.data?.[0];
                if (newDocumentType) {
                    lastCreatedDocumentType.value = newDocumentType;
                    toast('Document Type Created', {
                        description: `${newDocumentType.name} has been successfully created.`,
                        action: {
                            label: 'Undo',
                            onClick: () => undoCreate(newDocumentType),
                        },
                    });
                } else {
                    // Fallback toast if we can't get the document type
                    toast('Document Type Created', {
                        description: 'Document type has been successfully created.',
                    });
                }
            },
            onError: () => {
                toast('Create Failed', {
                    description: 'Failed to create the document type. Please try again.',
                });
            }
        });
    };

    const submitEditForm = (form: any, documentTypeId: number) => {
        const originalData = { ...form.data() };

        form.put(`/staff/document-types/${documentTypeId}`, {
            onSuccess: (page: any) => {
                const updatedDocumentType = page.props.documentType;
                if (updatedDocumentType) {
                    lastEditedDocumentType.value = {
                        original: { ...originalData, id: documentTypeId } as DocumentType,
                        updated: updatedDocumentType
                    };
                    toast('Document Type Updated', {
                        description: `${updatedDocumentType.name} has been successfully updated.`,
                        action: {
                            label: 'Undo',
                            onClick: () => undoEdit(documentTypeId, originalData),
                        },
                    });
                } else {
                    // Fallback toast if we can't get the updated document type
                    toast('Document Type Updated', {
                        description: 'Document type has been successfully updated.',
                    });
                }
            },
            onError: () => {
                toast('Update Failed', {
                    description: 'Failed to update the document type. Please try again.',
                });
            }
        });
    };

    // Reset form to initial values
    const resetForm = (form: any, initialData: DocumentTypeFormData) => {
        Object.keys(initialData).forEach(key => {
            form[key] = initialData[key as keyof DocumentTypeFormData];
        });
    };

    // Undo functions
    const undoDelete = (documentType: DocumentType) => {
        const form = useForm(documentType);
        form.post('/staff/document-types', {
            onSuccess: () => {
                toast('Document Type Restored', {
                    description: `${documentType.name} has been restored successfully.`,
                });
                lastDeletedDocumentType.value = null;
            },
            onError: () => {
                toast('Restore Failed', {
                    description: 'Failed to restore the document type. Please try again.',
                });
            }
        });
    };

    const undoCreate = (documentType: DocumentType) => {
        const form = useForm({});
        form.delete(`/staff/document-types/${documentType.id}`, {
            onSuccess: () => {
                toast('Document Type Removed', {
                    description: `${documentType.name} has been removed successfully.`,
                });
                lastCreatedDocumentType.value = null;
            },
            onError: () => {
                toast('Remove Failed', {
                    description: 'Failed to remove the document type. Please try again.',
                });
            }
        });
    };

    const undoEdit = (documentTypeId: number, originalData: any) => {
        const form = useForm(originalData);
        form.put(`/staff/document-types/${documentTypeId}`, {
            onSuccess: () => {
                toast('Changes Reverted', {
                    description: 'Document type changes have been reverted successfully.',
                });
                lastEditedDocumentType.value = null;
            },
            onError: () => {
                toast('Revert Failed', {
                    description: 'Failed to revert the changes. Please try again.',
                });
            }
        });
    };

    const undoStatusToggle = (documentTypeId: number, originalStatus: boolean) => {
        const form = useForm({});
        form.post(`/staff/document-types/${documentTypeId}/toggle-status`, {
            onSuccess: () => {
                toast('Status Reverted', {
                    description: 'Document type status has been reverted successfully.',
                });
            },
            onError: () => {
                toast('Status Revert Failed', {
                    description: 'Failed to revert the status. Please try again.',
                });
            }
        });
    };

    return {
        // State
        showDeleteDialog,
        documentTypeToDelete,
        deleteForm,
        lastDeletedDocumentType,
        lastCreatedDocumentType,
        lastEditedDocumentType,

        // Form creation
        createDocumentTypeForm,
        createEditForm,

        // Delete operations
        openDeleteDialog,
        confirmDelete,
        closeDeleteDialog,

        // Status operations
        toggleStatus,

        // Form helpers
        addRequiredDocument,
        removeRequiredDocument,
        addProcessingStep,
        removeProcessingStep,
        addDocumentTemplate,
        removeDocumentTemplate,

        // Form submission
        submitCreateForm,
        submitEditForm,

        // Undo operations
        undoDelete,
        undoCreate,
        undoEdit,
        undoStatusToggle,

        // Utilities
        resetForm,
    };
};
