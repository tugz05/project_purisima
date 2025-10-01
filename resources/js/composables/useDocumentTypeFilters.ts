import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

export interface DocumentTypeFilters {
    search?: string;
    category?: string;
    status?: string;
    page?: number;
}

export const useDocumentTypeFilters = (initialFilters: DocumentTypeFilters = {}) => {
    // Search state
    const searchQuery = ref(initialFilters.search || '');

    // Filter form
    const filterForm = useForm({
        search: initialFilters.search || '',
        category: initialFilters.category || 'all',
        status: initialFilters.status || 'all',
        page: initialFilters.page || 1,
    });

    // Apply filters
    const applyFilters = () => {
        filterForm.get('/staff/document-types', {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Clear all filters
    const clearFilters = () => {
        searchQuery.value = '';
        filterForm.search = '';
        filterForm.category = 'all';
        filterForm.status = 'all';
        filterForm.page = 1;
        applyFilters();
    };

    // Debounced search
    const createDebouncedSearch = (delay: number = 300) => {
        let searchTimeout: number;

        watch(searchQuery, (newValue) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterForm.search = newValue;
                filterForm.page = 1; // Reset to first page when searching
                applyFilters();
            }, delay);
        });
    };

    // Pagination helpers
    const goToPage = (page: number) => {
        filterForm.page = page;
        applyFilters();
    };

    const goToNextPage = (currentPage: number, lastPage: number) => {
        if (currentPage < lastPage) {
            goToPage(currentPage + 1);
        }
    };

    const goToPreviousPage = (currentPage: number) => {
        if (currentPage > 1) {
            goToPage(currentPage - 1);
        }
    };

    // Category options
    const categoryOptions = [
        { value: 'all', label: 'All Categories' },
        { value: 'personal', label: 'Personal' },
        { value: 'business', label: 'Business' },
        { value: 'legal', label: 'Legal' },
        { value: 'other', label: 'Other' },
    ];

    // Status options
    const statusOptions = [
        { value: 'all', label: 'All Status' },
        { value: 'active', label: 'Active' },
        { value: 'inactive', label: 'Inactive' },
    ];

    return {
        // State
        searchQuery,
        filterForm,

        // Methods
        applyFilters,
        clearFilters,
        createDebouncedSearch,

        // Pagination
        goToPage,
        goToNextPage,
        goToPreviousPage,

        // Options
        categoryOptions,
        statusOptions,
    };
};

