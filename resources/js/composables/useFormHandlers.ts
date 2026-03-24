import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { deletePendingUpload, uploadPendingFile } from '@/utils/uploadPendingFile';

export interface TransactionDocumentUploadSlot {
    id: string;
    name: string;
    progress: number;
    error?: string;
}

export interface FormOptions {
    preserveState?: boolean;
    preserveScroll?: boolean;
    onSuccess?: () => void;
    onError?: (errors: any) => void;
    onFinish?: () => void;
}

export function useFormHandlers() {
    const uploadProgress = ref<number | null>(null);
    const isDragging = ref(false);

    const transactionDocumentUploadSlots = ref<Record<string, TransactionDocumentUploadSlot[]>>({});

    const clearTransactionDocumentUploadSlots = (): void => {
        transactionDocumentUploadSlots.value = {};
    };

    const syncTransactionUploadIdsFromSlots = (form: { submitted_document_upload_ids: Record<string, string[]> }): void => {
        const ids: Record<string, string[]> = {};
        Object.keys(transactionDocumentUploadSlots.value).forEach((doc) => {
            const rowIds = transactionDocumentUploadSlots.value[doc]
                .map((s) => s.id)
                .filter((id) => id !== '');
            if (rowIds.length > 0) {
                ids[doc] = rowIds;
            }
        });
        form.submitted_document_upload_ids = ids;
    };

    // File handling utilities
    const handleFileSelection = (file: File | null, form: any, fieldName: string = 'photo') => {
        if (file) {
            form[fieldName] = file;
            uploadProgress.value = null;
        }
    };

    const handleInputChange = (e: Event, form: any, fieldName: string = 'photo') => {
        const target = e.target as HTMLInputElement;
        const file = target.files && target.files[0] ? target.files[0] : null;
        handleFileSelection(file, form, fieldName);
    };

    const handleDrop = (e: DragEvent, form: any, fieldName: string = 'photo') => {
        e.preventDefault();
        isDragging.value = false;

        const files = e.dataTransfer?.files;
        if (files && files.length > 0) {
            handleFileSelection(files[0], form, fieldName);
        }
    };

    const handleDragOver = (e: DragEvent) => {
        e.preventDefault();
        isDragging.value = true;
    };

    const handleDragLeave = () => {
        isDragging.value = false;
    };

    // Form submission utilities
    const submitForm = (
        form: any,
        url: string,
        method: 'post' | 'put' | 'patch' | 'delete' = 'post',
        options: FormOptions = {}
    ) => {
        const defaultOptions = {
            preserveState: false,
            preserveScroll: false,
            onSuccess: () => {},
            onError: () => {},
            onFinish: () => {},
        };

        const mergedOptions = { ...defaultOptions, ...options };

        switch (method) {
            case 'post':
                form.post(url, mergedOptions);
                break;
            case 'put':
                form.put(url, mergedOptions);
                break;
            case 'patch':
                form.patch(url, mergedOptions);
                break;
            case 'delete':
                form.delete(url, mergedOptions);
                break;
        }
    };

    // Transaction form utilities
    const createTransactionForm = () => {
        return useForm({
            type: '',
            title: '',
            description: '',
            required_documents: [] as string[],
            required_fields: {} as Record<string, string>,
            submitted_documents: {} as Record<string, File[]>,
            submitted_document_upload_ids: {} as Record<string, string[]>,
            fee_amount: 0,
        });
    };

    const updateFormForTransactionType = (
        type: string,
        form: any,
        transactionTypes: Record<string, any>
    ) => {
        if (type && transactionTypes[type]) {
            const typeInfo = transactionTypes[type];
            form.type = type;
            form.title = typeInfo.name;
            form.required_documents = typeInfo.required_documents;
            form.fee_amount = typeInfo.fee;
            form.submitted_documents = {};
            form.submitted_document_upload_ids = {};
            clearTransactionDocumentUploadSlots();
        }
    };

    const addMultipleSubmittedDocuments = async (form: any, documentType: string, files: FileList | File[]) => {
        const list = Array.from(files as File[]);
        if (!transactionDocumentUploadSlots.value[documentType]) {
            transactionDocumentUploadSlots.value[documentType] = [];
        }
        if (!form.submitted_document_upload_ids[documentType]) {
            form.submitted_document_upload_ids[documentType] = [];
        }

        for (const file of list) {
            const slotIndex = transactionDocumentUploadSlots.value[documentType].length;
            transactionDocumentUploadSlots.value[documentType].push({
                id: '',
                name: file.name,
                progress: 0,
            });

            try {
                const result = await uploadPendingFile(file, 'transaction_submission', (p) => {
                    const row = transactionDocumentUploadSlots.value[documentType][slotIndex];
                    if (row) {
                        row.progress = p;
                    }
                });
                const row = transactionDocumentUploadSlots.value[documentType][slotIndex];
                if (row) {
                    row.id = result.id;
                    row.progress = 100;
                }
            } catch {
                transactionDocumentUploadSlots.value[documentType].splice(slotIndex, 1);
                toast.error('Upload failed', {
                    description: file.name,
                });
            }
        }

        form.submitted_documents[documentType] = [];
        syncTransactionUploadIdsFromSlots(form);
    };

    const addSubmittedDocument = async (form: any, documentType: string, file: File) => {
        await addMultipleSubmittedDocuments(form, documentType, [file]);
    };

    const removeSubmittedDocument = async (form: any, documentType: string, index: number) => {
        const slots = transactionDocumentUploadSlots.value[documentType];
        if (!slots || !slots[index]) {
            return;
        }
        const slot = slots[index];
        if (slot.id) {
            try {
                await deletePendingUpload(slot.id);
            } catch {
                /* still remove from UI */
            }
        }
        slots.splice(index, 1);
        if (slots.length === 0) {
            delete transactionDocumentUploadSlots.value[documentType];
        }
        if (form.submitted_documents[documentType]) {
            form.submitted_documents[documentType].splice(index, 1);
        }
        syncTransactionUploadIdsFromSlots(form);
    };

    const submitTransactionCreate = (
        form: any,
        url: string,
        onSuccess?: () => void
    ) => {
        syncTransactionUploadIdsFromSlots(form);

        const formData = new FormData();

        formData.append('type', form.type);
        formData.append('title', form.title);
        formData.append('description', form.description || '');
        formData.append('fee_amount', form.fee_amount.toString());

        if (form.required_documents && Array.isArray(form.required_documents)) {
            form.required_documents.forEach((doc: string, index: number) => {
                formData.append(`required_documents[${index}]`, doc);
            });
        }

        if (form.required_fields && typeof form.required_fields === 'object') {
            Object.keys(form.required_fields).forEach((key) => {
                const value = form.required_fields[key];
                formData.append(`required_fields[${key}]`, value ?? '');
            });
        }

        Object.keys(form.submitted_document_upload_ids || {}).forEach((docType) => {
            const ids = form.submitted_document_upload_ids[docType];
            if (!Array.isArray(ids)) {
                return;
            }
            ids.forEach((id: string, index: number) => {
                if (id) {
                    formData.append(`submitted_document_upload_ids[${docType}][${index}]`, id);
                }
            });
        });

        Object.keys(form.submitted_documents).forEach((docType) => {
            const hasIds = (form.submitted_document_upload_ids[docType] || []).length > 0;
            if (hasIds) {
                return;
            }
            const docFiles = form.submitted_documents[docType];
            if (docFiles && Array.isArray(docFiles)) {
                docFiles.forEach((file: File, index: number) => {
                    formData.append(`submitted_documents[${docType}][${index}]`, file);
                });
            }
        });

        form.post(url, {
            data: formData,
            forceFormData: true,
            onSuccess: () => {
                toast('Transaction Created', {
                    description: 'Your document request has been submitted successfully.',
                    action: {
                        label: 'View',
                        onClick: () => {
                            //
                        },
                    },
                });
                if (onSuccess) {
                    onSuccess();
                }
                form.reset();
                clearTransactionDocumentUploadSlots();
            },
            onError: () => {
                toast('Submission Failed', {
                    description: 'Failed to submit your document request. Please try again.',
                });
            },
        });
    };

    const submitTransactionUpdate = (
        form: any,
        url: string,
        onSuccess?: () => void
    ) => {
        form.put(url, {
            onSuccess: () => {
                toast('Transaction Updated', {
                    description: 'Your document request has been updated successfully.',
                    action: {
                        label: 'View',
                        onClick: () => {
                            // Could navigate to transaction details if needed
                        }
                    }
                });
                if (onSuccess) onSuccess();
            },
            onError: () => {
                toast('Update Failed', {
                    description: 'Failed to update your document request. Please try again.',
                });
            }
        });
    };

    const submitTransactionDelete = (
        form: any,
        url: string,
        transactionTitle: string,
        onSuccess?: () => void
    ) => {
        form.delete(url, {
            onSuccess: () => {
                toast('Transaction Deleted', {
                    description: `"${transactionTitle}" has been deleted successfully.`,
                    action: {
                        label: 'Undo',
                        onClick: () => {
                            // Could implement undo functionality if needed
                        }
                    }
                });
                if (onSuccess) onSuccess();
            },
            onError: () => {
                toast('Delete Failed', {
                    description: 'Failed to delete the document request. Please try again.',
                });
            }
        });
    };

    // Filter form utilities
    const createFilterForm = (initialFilters: Record<string, any> = {}) => {
        return useForm({
            status: initialFilters.status || 'all',
            type: initialFilters.type || 'all',
            staff_id: initialFilters.staff_id || '',
            search: initialFilters.search || '',
            sort: initialFilters.sort || 'created_at',
            direction: initialFilters.direction || 'desc',
            page: initialFilters.page || 1,
        });
    };

    const applyFilters = (form: any, url: string, options: FormOptions = {}) => {
        const status = form.status === 'all' ? '' : form.status;
        const type = form.type === 'all' ? '' : form.type;

        const filterForm = useForm({
            status,
            type,
            staff_id: form.staff_id,
            search: form.search,
            sort: form.sort,
            direction: form.direction,
        });

        filterForm.get(url, {
            preserveState: options.preserveState || false,
            preserveScroll: options.preserveScroll || false,
            ...options,
        });
    };

    // Debounced search
    const createDebouncedSearch = (
        searchQuery: any,
        form: any,
        url: string,
        delay: number = 300
    ) => {
        let searchTimeout: number;

        watch(searchQuery, (newValue) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                form.search = newValue;
                form.get(url, {
                    preserveState: true,
                    preserveScroll: true,
                });
            }, delay);
        });
    };

    const clearSearch = (searchQuery: any, form: any, url: string) => {
        searchQuery.value = '';
        form.search = '';
        form.get(url);
    };

    // Profile form utilities
    const createProfileForm = (user: any) => {
        return useForm({
            first_name: user?.first_name || '',
            middle_name: user?.middle_name || '',
            last_name: user?.last_name || '',
            phone: user?.phone || '',
            birth_date: user?.birth_date || '',
            sex: user?.sex || '',
            civil_status: user?.civil_status || '',
            occupation: user?.occupation || '',
            purok: user?.purok || '',
            photo: null as File | null,
        });
    };

    return {
        // State
        uploadProgress,
        isDragging,

        // File handling
        handleFileSelection,
        handleInputChange,
        handleDrop,
        handleDragOver,
        handleDragLeave,

        // Form submission
        submitForm,

        // Transaction forms
        createTransactionForm,
        updateFormForTransactionType,
        submitTransactionCreate,
        submitTransactionUpdate,
        submitTransactionDelete,
        addSubmittedDocument,
        removeSubmittedDocument,
        addMultipleSubmittedDocuments,
        transactionDocumentUploadSlots,
        clearTransactionDocumentUploadSlots,

        // Filter forms
        createFilterForm,
        applyFilters,
        createDebouncedSearch,
        clearSearch,

        // Profile forms
        createProfileForm,
    };
}
