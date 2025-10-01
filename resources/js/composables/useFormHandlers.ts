import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

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
            submitted_documents: {} as Record<string, File[]>,
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
        }
    };

    // File upload functions
    const addSubmittedDocument = (form: any, documentType: string, file: File) => {
        if (!form.submitted_documents[documentType]) {
            form.submitted_documents[documentType] = [];
        }
        form.submitted_documents[documentType].push(file);
    };

    const removeSubmittedDocument = (form: any, documentType: string, index: number) => {
        if (form.submitted_documents[documentType]) {
            form.submitted_documents[documentType].splice(index, 1);
        }
    };

    const addMultipleSubmittedDocuments = (form: any, documentType: string, files: FileList) => {
        if (!form.submitted_documents[documentType]) {
            form.submitted_documents[documentType] = [];
        }
        Array.from(files).forEach(file => {
            form.submitted_documents[documentType].push(file);
        });
    };

    const submitTransactionCreate = (
        form: any,
        url: string,
        onSuccess?: () => void
    ) => {
        // Create a FormData object to handle file uploads properly
        const formData = new FormData();

        // Add basic form fields
        formData.append('type', form.type);
        formData.append('title', form.title);
        formData.append('description', form.description || '');
        formData.append('fee_amount', form.fee_amount.toString());

        // Add required documents array
        if (form.required_documents && Array.isArray(form.required_documents)) {
            form.required_documents.forEach((doc: string, index: number) => {
                formData.append(`required_documents[${index}]`, doc);
            });
        }

        // Add submitted documents organized by document type
        Object.keys(form.submitted_documents).forEach(docType => {
            if (form.submitted_documents[docType] && Array.isArray(form.submitted_documents[docType])) {
                form.submitted_documents[docType].forEach((file: File, index: number) => {
                    // Send files with document type as the key
                    formData.append(`submitted_documents[${docType}][${index}]`, file);
                });
            }
        });

        // Debug: Log the form data before submission
        console.log('Form data being submitted:', {
            type: form.type,
            title: form.title,
            description: form.description,
            required_documents: form.required_documents,
            submitted_documents: form.submitted_documents,
            fee_amount: form.fee_amount
        });

        // Use Inertia's post method with FormData
        form.post(url, {
            data: formData,
            forceFormData: true,
            onSuccess: () => {
                toast('Transaction Created', {
                    description: 'Your document request has been submitted successfully.',
                    action: {
                        label: 'View',
                        onClick: () => {
                            // Could navigate to transaction details if needed
                        }
                    }
                });
                if (onSuccess) onSuccess();
                form.reset();
            },
            onError: (errors: any) => {
                console.error('Submission errors:', errors);
                toast('Submission Failed', {
                    description: 'Failed to submit your document request. Please try again.',
                });
            }
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

        // Filter forms
        createFilterForm,
        applyFilters,
        createDebouncedSearch,
        clearSearch,

        // Profile forms
        createProfileForm,
    };
}
