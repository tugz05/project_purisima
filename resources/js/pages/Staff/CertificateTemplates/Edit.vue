<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Save, Eye, Plus, X, FileText, Tag } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import StaffLayout from '@/layouts/staff/Layout.vue';

interface DocumentType {
    id: number;
    name: string;
    code: string;
}

interface RequiredField {
    name: string;
    label: string;
    type: 'text' | 'textarea' | 'date' | 'time' | 'number' | 'email';
    required: boolean;
    placeholder?: string;
}

interface CertificateTemplate {
    id: number;
    name: string;
    description?: string;
    template_content: string;
    required_fields?: RequiredField[];
    is_active: boolean;
    is_default: boolean;
    sort_order: number;
}

interface Props {
    documentType: DocumentType;
    template: CertificateTemplate;
    requiredFields: RequiredField[];
}

const props = defineProps<Props>();

const form = useForm({
    name: props.template.name,
    description: props.template.description || '',
    template_content: props.template.template_content,
    required_fields: (props.template.required_fields || props.requiredFields) as RequiredField[],
    is_active: props.template.is_active,
    is_default: props.template.is_default,
    sort_order: props.template.sort_order,
});

// Fields are now managed by CertificateTemplateEditor component

const inferFieldType = (tag: string): RequiredField['type'] => {
    if (tag.includes('date')) return 'date';
    if (tag.includes('time')) return 'time';
    if (tag.includes('age') || tag.includes('number')) return 'number';
    if (tag.includes('email')) return 'email';
    if (tag.includes('description') || tag.includes('purpose') || tag.includes('notes')) return 'textarea';
    return 'text';
};

const addField = () => {
    form.required_fields.push({
        name: '',
        label: '',
        type: 'text',
        required: true,
        placeholder: '',
    });
};

const removeField = (index: number) => {
    form.required_fields.splice(index, 1);
};

// Tag insertion is now handled by CertificateTemplateEditor component

const submit = () => {
    form.put(`/staff/document-types/${props.documentType.id}/certificate-templates/${props.template.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Template updated successfully');
        },
        onError: () => {
            toast.error('Failed to update template');
        },
    });
};

// Fields are managed by CertificateTemplateEditor component
</script>

<template>
    <Head :title="`Edit Template - ${template.name}`" />

    <StaffLayout>
        <div class="bg-gradient-to-br from-gray-50 to-blue-50/30 min-h-full w-full">
            <div class="mx-auto w-full px-2 sm:px-4 lg:px-6 py-2 md:py-4 max-w-full">
                <!-- Header -->
                <div class="relative overflow-hidden bg-gradient-to-r from-teal-600 via-emerald-600 to-green-600 shadow-xl mb-6 rounded-2xl">
                    <div class="relative px-4 sm:px-6 lg:px-8 py-8">
                        <div class="flex items-center gap-4 mb-4">
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="router.visit(`/staff/document-types/${documentType.id}/certificate-templates`)"
                                class="text-white hover:bg-white/20"
                            >
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back
                            </Button>
                        </div>
                        <h1 class="text-3xl sm:text-4xl font-bold text-white mb-2">
                            Edit Certificate Template
                        </h1>
                        <p class="text-teal-100 text-sm sm:text-base">
                            For <strong>{{ documentType.name }}</strong>
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Basic Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Basic Information</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label for="name">Template Name *</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="e.g., Standard Certification Template"
                                    :class="{ 'border-red-500': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <Label for="description">Description</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Template description..."
                                    rows="3"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Template Content -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="h-5 w-5" />
                                Template Content
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label for="template-content">Template Content *</Label>
                                <Textarea
                                    id="template-content"
                                    v-model="form.template_content"
                                    placeholder="Enter template content. Use {{tag}} for placeholders like {{name}}, {{date}}, {{purok}}, etc."
                                    rows="15"
                                    class="font-mono text-sm"
                                    :class="{ 'border-red-500': form.errors.template_content }"
                                />
                                <p v-if="form.errors.template_content" class="text-red-500 text-xs mt-1">
                                    {{ form.errors.template_content }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Use double curly braces for tags: <code v-pre>{{name}}</code>, <code v-pre>{{date}}</code>, <code v-pre>{{purok}}</code>, etc.
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Required Fields -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Required Fields</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex justify-between items-center">
                                <p class="text-sm text-gray-600">
                                    Define the fields that will be shown when requesting this document
                                </p>
                                <Button type="button" variant="outline" size="sm" @click="addField">
                                    <Plus class="h-4 w-4 mr-1" />
                                    Add Field
                                </Button>
                            </div>

                            <div v-if="form.required_fields.length === 0" class="text-center py-8 text-gray-500">
                                <p>No fields defined. Fields will be auto-generated from template tags.</p>
                            </div>

                            <div v-else class="space-y-4">
                                <div
                                    v-for="(field, index) in form.required_fields"
                                    :key="index"
                                    class="p-4 border border-gray-200 rounded-lg space-y-3"
                                >
                                    <div class="flex justify-between items-start">
                                        <h4 class="font-medium">Field {{ index + 1 }}</h4>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            @click="removeField(index)"
                                            class="text-red-600 hover:text-red-700"
                                        >
                                            <X class="h-4 w-4" />
                                        </Button>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <Label>Field Name (Tag)</Label>
                                            <Input v-model="field.name" placeholder="e.g., name" />
                                        </div>
                                        <div>
                                            <Label>Label</Label>
                                            <Input v-model="field.label" placeholder="e.g., Full Name" />
                                        </div>
                                        <div>
                                            <Label>Type</Label>
                                            <select v-model="field.type" class="w-full rounded-md border border-gray-200 px-3 py-2">
                                                <option value="text">Text</option>
                                                <option value="textarea">Textarea</option>
                                                <option value="date">Date</option>
                                                <option value="time">Time</option>
                                                <option value="number">Number</option>
                                                <option value="email">Email</option>
                                            </select>
                                        </div>
                                        <div>
                                            <Label>Placeholder</Label>
                                            <Input v-model="field.placeholder" placeholder="Enter placeholder text" />
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            :id="`required-${index}`"
                                            v-model="field.required"
                                            class="h-4 w-4 rounded border-gray-300"
                                        />
                                        <Label :for="`required-${index}`" class="cursor-pointer">Required</Label>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Settings -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Settings</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center space-x-2">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    v-model="form.is_active"
                                    class="h-4 w-4 rounded border-gray-300"
                                />
                                <Label for="is_active" class="cursor-pointer">Active</Label>
                            </div>

                            <div class="flex items-center space-x-2">
                                <input
                                    type="checkbox"
                                    id="is_default"
                                    v-model="form.is_default"
                                    class="h-4 w-4 rounded border-gray-300"
                                />
                                <Label for="is_default" class="cursor-pointer">Set as Default Template</Label>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            @click="router.visit(`/staff/document-types/${documentType.id}/certificate-templates`)"
                        >
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="form.processing" class="bg-teal-600 hover:bg-teal-700">
                            <Save class="h-4 w-4 mr-2" />
                            {{ form.processing ? 'Updating...' : 'Update Template' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </StaffLayout>
</template>

