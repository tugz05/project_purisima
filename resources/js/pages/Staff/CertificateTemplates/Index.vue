<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Plus, Search, Eye, Edit, Trash2, FileText, Star, ArrowLeft } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import StaffLayout from '@/layouts/staff/Layout.vue';

interface CertificateTemplate {
    id: number;
    name: string;
    description?: string;
    is_active: boolean;
    is_default: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
}

interface DocumentType {
    id: number;
    name: string;
    code: string;
}

interface Props {
    documentType: DocumentType;
    templates: CertificateTemplate[];
    filters: {
        search?: string;
        active?: boolean;
    };
}

const props = defineProps<Props>();

// Search functionality
const searchQuery = ref(props.filters.search || '');
const filteredTemplates = computed(() => {
    let filtered = props.templates;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(t =>
            t.name.toLowerCase().includes(query) ||
            (t.description && t.description.toLowerCase().includes(query))
        );
    }

    return filtered;
});

// Dialog states
const deleteDialogOpen = ref(false);
const templateToDelete = ref<CertificateTemplate | null>(null);

const openDeleteDialog = (template: CertificateTemplate) => {
    templateToDelete.value = template;
    deleteDialogOpen.value = true;
};

const deleteTemplate = () => {
    if (!templateToDelete.value) return;

    router.delete(
        `/staff/document-types/${props.documentType.id}/certificate-templates/${templateToDelete.value.id}`,
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Template deleted successfully');
                deleteDialogOpen.value = false;
                templateToDelete.value = null;
            },
            onError: () => {
                toast.error('Failed to delete template');
            },
        }
    );
};

const setAsDefault = (template: CertificateTemplate) => {
    router.post(
        `/staff/document-types/${props.documentType.id}/certificate-templates/${template.id}/set-default`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Template set as default');
            },
            onError: () => {
                toast.error('Failed to set template as default');
            },
        }
    );
};
</script>

<template>
    <Head :title="`Certificate Templates - ${documentType.name}`" />

    <StaffLayout>
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-2 sm:px-4 lg:px-6 py-2 md:py-4 max-w-full">
                <!-- Header -->
                <div class="relative overflow-hidden bg-gradient-to-r from-teal-600 via-emerald-600 to-green-600 shadow-xl mb-6 rounded-2xl">
                    <div class="absolute inset-0 bg-black/10"></div>
                    <div class="relative px-4 sm:px-6 lg:px-8 py-8">
                        <div class="flex items-center gap-4 mb-4">
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="router.visit('/staff/document-types')"
                                class="text-white hover:bg-white/20"
                            >
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back to Document Types
                            </Button>
                        </div>
                        <h1 class="text-3xl sm:text-4xl font-bold text-white mb-2">
                            Certificate Templates
                        </h1>
                        <p class="text-teal-100 text-sm sm:text-base">
                            Manage certificate/permit templates for <strong>{{ documentType.name }}</strong>
                        </p>
                    </div>
                </div>

                <!-- Actions Bar -->
                <div class="flex flex-col sm:flex-row gap-4 mb-6">
                    <div class="flex-1 relative">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search templates..."
                            class="pl-10 border-gray-200"
                        />
                    </div>
                    <Button
                        @click="router.visit(`/staff/document-types/${documentType.id}/certificate-templates/create`)"
                        class="bg-teal-600 hover:bg-teal-700"
                    >
                        <Plus class="h-4 w-4 mr-2" />
                        Create Template
                    </Button>
                </div>

                <!-- Templates List -->
                <div v-if="filteredTemplates.length > 0" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <Card
                            v-for="template in filteredTemplates"
                            :key="template.id"
                            class="group shadow-sm border-gray-200 hover:shadow-lg hover:border-teal-300 transition-all duration-200 flex flex-col h-full"
                        >
                            <CardContent class="p-4 flex flex-col h-full">
                                <!-- Header -->
                                <div class="mb-4 flex-shrink-0">
                                    <div class="flex items-start justify-between mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900 group-hover:text-teal-600 transition-colors line-clamp-2 min-h-[3.5rem]">
                                            {{ template.name }}
                                        </h3>
                                        <div class="flex gap-1 flex-shrink-0 ml-2">
                                            <Badge
                                                v-if="template.is_default"
                                                class="text-xs font-medium bg-yellow-500 text-white"
                                            >
                                                <Star class="h-3 w-3 mr-1" />
                                                Default
                                            </Badge>
                                            <Badge
                                                :class="template.is_active ? 'bg-green-500' : 'bg-gray-400'"
                                                class="text-xs font-medium text-white"
                                            >
                                                {{ template.is_active ? 'Active' : 'Inactive' }}
                                            </Badge>
                                        </div>
                                    </div>
                                    <p v-if="template.description" class="text-sm text-gray-600 line-clamp-2">
                                        {{ template.description }}
                                    </p>
                                    <p v-else class="text-sm text-gray-400 italic">
                                        No description
                                    </p>
                                </div>

                                <!-- Actions -->
                                <div class="flex gap-2 pt-2 border-t border-gray-100 mt-auto flex-shrink-0">
                                    <Button
                                        @click="router.visit(`/staff/document-types/${documentType.id}/certificate-templates/${template.id}`)"
                                        variant="outline"
                                        size="sm"
                                        class="flex-1 h-8 text-blue-600 border-blue-200 hover:bg-blue-50"
                                    >
                                        <Eye class="h-3 w-3 mr-1" />
                                        View
                                    </Button>
                                    <Button
                                        @click="router.visit(`/staff/document-types/${documentType.id}/certificate-templates/${template.id}/edit`)"
                                        variant="outline"
                                        size="sm"
                                        class="flex-1 h-8 text-green-600 border-green-200 hover:bg-green-50"
                                    >
                                        <Edit class="h-3 w-3 mr-1" />
                                        Edit
                                    </Button>
                                    <Button
                                        v-if="!template.is_default"
                                        @click="setAsDefault(template)"
                                        variant="outline"
                                        size="sm"
                                        class="h-8 px-3 text-yellow-600 border-yellow-200 hover:bg-yellow-50"
                                        title="Set as default"
                                    >
                                        <Star class="h-3 w-3" />
                                    </Button>
                                    <Button
                                        @click="openDeleteDialog(template)"
                                        variant="outline"
                                        size="sm"
                                        class="h-8 px-3 text-red-600 border-red-200 hover:bg-red-50"
                                    >
                                        <Trash2 class="h-3 w-3" />
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-16">
                    <div class="max-w-md mx-auto">
                        <div class="w-20 h-20 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <FileText class="h-10 w-10 text-teal-600" />
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">
                            {{ searchQuery ? 'No templates found' : 'No templates yet' }}
                        </h3>
                        <p class="text-gray-600 mb-8">
                            {{ searchQuery
                                ? 'Try adjusting your search criteria'
                                : 'Create your first certificate template to get started' }}
                        </p>
                        <Button
                            v-if="!searchQuery"
                            @click="router.visit(`/staff/document-types/${documentType.id}/certificate-templates/create`)"
                            class="bg-teal-600 hover:bg-teal-700"
                        >
                            <Plus class="h-4 w-4 mr-2" />
                            Create Template
                        </Button>
                    </div>
                </div>

                <!-- Delete Confirmation Dialog -->
                <Dialog v-model:open="deleteDialogOpen">
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Delete Template</DialogTitle>
                            <DialogDescription>
                                Are you sure you want to delete "{{ templateToDelete?.name }}"? This action cannot be undone.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button variant="outline" @click="deleteDialogOpen = false">
                                Cancel
                            </Button>
                            <Button variant="destructive" @click="deleteTemplate">
                                Delete
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    </StaffLayout>
</template>

