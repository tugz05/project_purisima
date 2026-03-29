<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import StaffLayout from '@/layouts/staff/Layout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { FileCheck, Loader2, Printer, Save, Sparkles, FileText } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';
import staff from '@/routes/staff';
import { finalize, generateAi, loadTemplate, print, schema } from '@/routes/staff/certificates/manual';

interface DocumentTypeOption {
    id: number;
    code: string;
    name: string;
    description: string | null;
}

interface SchemaField {
    name: string;
    label: string;
    type: string;
    required: boolean;
    placeholder?: string;
    options?: string[];
}

const REQUESTOR_NAME_KEYS = ['name', 'full_name', 'requestor_name', 'applicant_name'] as const;

interface Props {
    documentTypes: DocumentTypeOption[];
}

const props = defineProps<Props>();

const { staffManualCertificateBreadcrumbs } = useBreadcrumbs();

const selectedDocumentTypeId = ref<string>('');
const schemaLoading = ref(false);
const schemaError = ref<string | null>(null);
/** Matches PrintCertificate.vue: clearance vs standard (no DB certificate_templates row required). */
const printLayout = ref<'clearance' | 'standard' | ''>('');
const templateName = ref<string | null>(null);
const fieldsSource = ref<'layout_only' | 'layout_with_extras' | ''>('');
const extraFieldCount = ref(0);
const fields = ref<SchemaField[]>([]);
const fieldValues = ref<Record<string, string>>({});
const documentContent = ref('');
const officerOfTheDay = ref('');

const loadingTemplate = ref(false);
const loadingAi = ref(false);

const saveForm = useForm({
    document_type_id: 0,
    template_id: null as number | null,
    field_values: {} as Record<string, string>,
    document_content: '',
    officer_of_the_day: null as string | null,
});

const selectedDocName = computed(() => {
    if (!selectedDocumentTypeId.value) {
        return '';
    }
    const id = Number(selectedDocumentTypeId.value);
    return props.documentTypes.find((d) => d.id === id)?.name ?? '';
});

function getCsrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

async function postJson(url: string, body: Record<string, unknown>): Promise<Response> {
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    });
}

function validateRequiredFields(): boolean {
    for (const f of fields.value) {
        if (!f.required) {
            continue;
        }
        const v = String(fieldValues.value[f.name] ?? '').trim();
        if (v === '') {
            toast.error(`Please fill in: ${f.label}`);
            return false;
        }
    }
    return true;
}

async function fetchSchema(documentTypeId: number): Promise<void> {
    schemaLoading.value = true;
    schemaError.value = null;
    fields.value = [];
    fieldValues.value = {};
    printLayout.value = '';
    templateName.value = null;
    fieldsSource.value = '';
    extraFieldCount.value = 0;
    documentContent.value = '';

    try {
        const url = schema.url({ query: { document_type_id: documentTypeId } });
        const res = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        const data = (await res.json()) as {
            error?: string;
            fields?: SchemaField[];
            fields_source?: 'layout_only' | 'layout_with_extras';
            extra_field_count?: number;
            print_layout?: 'clearance' | 'standard';
            template?: { id: number | null; name: string };
        };

        if (!res.ok) {
            schemaError.value = data.error ?? 'Could not load certificate fields.';
            return;
        }

        fields.value = data.fields ?? [];
        printLayout.value = data.print_layout === 'clearance' || data.print_layout === 'standard' ? data.print_layout : '';
        templateName.value = data.template?.name ?? null;
        fieldsSource.value =
            data.fields_source === 'layout_with_extras' || data.fields_source === 'layout_only' ? data.fields_source : 'layout_only';
        extraFieldCount.value = typeof data.extra_field_count === 'number' ? data.extra_field_count : 0;

        const next: Record<string, string> = {};
        for (const f of fields.value) {
            next[f.name] = '';
        }
        fieldValues.value = next;
    } catch {
        schemaError.value = 'Network error loading fields.';
    } finally {
        schemaLoading.value = false;
    }
}

watch(selectedDocumentTypeId, (id) => {
    if (!id) {
        schemaError.value = null;
        fields.value = [];
        printLayout.value = '';
        templateName.value = null;
        fieldsSource.value = '';
        extraFieldCount.value = 0;
        fieldValues.value = {};
        return;
    }
    void fetchSchema(Number(id));
});

const applyLoadTemplate = async (): Promise<void> => {
    if (!selectedDocumentTypeId.value || !printLayout.value) {
        toast.error('Select a document type first.');
        return;
    }
    if (!validateRequiredFields()) {
        return;
    }

    loadingTemplate.value = true;
    try {
        const res = await postJson(loadTemplate.url(), {
            document_type_id: Number(selectedDocumentTypeId.value),
            field_values: fieldValues.value,
        });
        const data = (await res.json()) as { content?: string; template_name?: string; error?: string };

        if (!res.ok) {
            toast.error(data.error ?? 'Failed to load default body');
            return;
        }
        if (data.content) {
            documentContent.value = data.content;
            toast.success(`Default body "${data.template_name ?? 'default'}" inserted into the editor`);
        } else {
            toast.error('No body content was returned');
        }
    } catch {
        toast.error('Failed to insert default body');
    } finally {
        loadingTemplate.value = false;
    }
};

const applyGenerateAi = async (): Promise<void> => {
    if (!selectedDocumentTypeId.value || !printLayout.value) {
        toast.error('Select a document type first.');
        return;
    }
    if (!validateRequiredFields()) {
        return;
    }

    loadingAi.value = true;
    try {
        const res = await postJson(generateAi.url(), {
            document_type_id: Number(selectedDocumentTypeId.value),
            field_values: fieldValues.value,
            current_content: documentContent.value || null,
        });
        const data = (await res.json()) as { content?: string; error?: string };

        if (!res.ok) {
            toast.error(data.error ?? 'AI generation failed');
            return;
        }
        if (data.content) {
            documentContent.value = data.content;
            toast.success('Certificate body generated (same AI as transaction certificates)');
        } else {
            toast.error('AI returned no content');
        }
    } catch {
        toast.error('AI generation failed');
    } finally {
        loadingAi.value = false;
    }
};

const openPrint = (): void => {
    if (!selectedDocumentTypeId.value) {
        toast.error('Select a document type');
        return;
    }
    const html = String(documentContent.value || '').trim();
    if (html === '') {
        toast.error('Add certificate body content first (insert default body and/or generate with AI)');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = print.url();
    form.target = '_blank';
    form.style.display = 'none';

    const tokenInput = document.createElement('input');
    tokenInput.type = 'hidden';
    tokenInput.name = '_token';
    tokenInput.value = getCsrfToken();

    const documentTypeInput = document.createElement('input');
    documentTypeInput.type = 'hidden';
    documentTypeInput.name = 'document_type_id';
    documentTypeInput.value = String(selectedDocumentTypeId.value);

    const contentInput = document.createElement('textarea');
    contentInput.name = 'content';
    contentInput.value = documentContent.value;

    const officerInput = document.createElement('input');
    officerInput.type = 'hidden';
    officerInput.name = 'officer_of_the_day';
    officerInput.value = officerOfTheDay.value.trim();

    form.append(tokenInput, documentTypeInput, contentInput, officerInput);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

function saveFormGeneralError(): string {
    const err = (saveForm.errors as Record<string, unknown>).error;
    return typeof err === 'string' ? err : '';
}

function hasRequestorNameFilled(): boolean {
    for (const key of REQUESTOR_NAME_KEYS) {
        if (String(fieldValues.value[key] ?? '').trim() !== '') {
            return true;
        }
    }
    return false;
}

const canSaveCertificate = computed(() => {
    return (
        Boolean(selectedDocumentTypeId.value) &&
        Boolean(printLayout.value) &&
        String(documentContent.value || '').trim() !== '' &&
        hasRequestorNameFilled()
    );
});

const saveCertificate = (): void => {
    if (!selectedDocumentTypeId.value || !printLayout.value) {
        toast.error('Select a document type first.');
        return;
    }
    if (!validateRequiredFields()) {
        return;
    }
    if (!String(documentContent.value || '').trim()) {
        toast.error('Add certificate body content in the editor before saving.');
        return;
    }

    saveForm.document_type_id = Number(selectedDocumentTypeId.value);
    saveForm.template_id = null;
    saveForm.field_values = { ...fieldValues.value };
    saveForm.document_content = documentContent.value;
    saveForm.officer_of_the_day = officerOfTheDay.value.trim() || null;

    saveForm.post(finalize.url(), {
        preserveScroll: true,
        onError: () => {
            toast.error('Could not save the certificate. Fix any errors and try again.');
        },
    });
};

const inputClass = 'border-gray-200 focus:border-blue-500 focus:ring-blue-500';
const selectTriggerClass = 'h-10 w-full border-gray-200 focus:border-blue-500 focus:ring-blue-500';
const fieldStack = 'flex flex-col gap-2';
</script>

<template>
    <Head title="Walk-in certificate" />

    <StaffLayout :breadcrumbs="staffManualCertificateBreadcrumbs">
        <div class="min-h-full w-full bg-gradient-to-br from-gray-50 to-teal-50/20">
            <div class="mx-auto w-full max-w-[100rem] space-y-6 px-4 py-4 sm:px-6 lg:px-8 md:py-6">
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-teal-600 via-emerald-600 to-cyan-600 shadow-xl">
                    <div class="absolute inset-0 bg-black/10" />
                    <div class="relative px-5 py-6 sm:px-8 sm:py-8">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1 text-white">
                                <div class="flex max-w-4xl flex-col gap-2">
                                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl lg:text-4xl">Walk-in certificate</h1>
                                    <p class="text-sm leading-relaxed text-teal-50 sm:text-base">
                                        Pick a document type to load the matching official print layout (Barangay clearance or standard certificate—the
                                        same shells as the print preview page). Build the body, then use Save certificate to create the walk-in
                                        transaction or print a draft anytime.
                                    </p>
                                </div>
                            </div>
                            <Button
                                as-child
                                variant="secondary"
                                class="h-10 shrink-0 border-white/30 bg-white/15 text-white hover:bg-white/25"
                            >
                                <a :href="staff.transactions.index().url">Back to transactions</a>
                            </Button>
                        </div>
                    </div>
                </div>

                <div v-if="props.documentTypes.length === 0" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    No active document types were found. Add document types in staff settings first.
                </div>

                <div v-else class="grid grid-cols-1 gap-6 xl:grid-cols-12">
                    <Card class="border-gray-200 shadow-lg xl:col-span-5">
                        <CardHeader class="gap-2 border-b border-gray-100">
                            <CardTitle class="flex items-center gap-2 text-lg text-gray-900">
                                <FileCheck class="h-5 w-5 text-teal-600" />
                                1. Document type &amp; fields
                            </CardTitle>
                            <CardDescription>
                                Every document type always shows the base fields for its print layout (standard or barangay clearance). Extra fields
                                configured on the document type appear below those and are included when you insert the default body, generate with AI,
                                or save—same AI engine as transaction certificates.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6 pt-2 pb-6">
                            <div :class="fieldStack">
                                <Label class="text-sm font-medium text-gray-700" for="doc_type">Document type</Label>
                                <Select v-model="selectedDocumentTypeId">
                                    <SelectTrigger id="doc_type" :class="selectTriggerClass">
                                        <SelectValue placeholder="Select document type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="dt in props.documentTypes" :key="dt.id" :value="String(dt.id)">
                                            {{ dt.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="templateName" class="text-xs text-gray-500">Print layout: {{ templateName }}</p>
                            </div>

                            <div
                                v-if="!schemaLoading && !schemaError && fields.length > 0 && fieldsSource === 'layout_only'"
                                class="rounded-lg border border-amber-200/90 bg-amber-50 px-3 py-2.5 text-xs leading-relaxed text-amber-950"
                            >
                                <p class="font-semibold text-amber-950">Want more fields for permits or special certificates?</p>
                                <p class="mt-1 text-amber-900/95">
                                    Open
                                    <a href="/staff/document-types" class="font-medium text-amber-950 underline underline-offset-2"
                                        >Document types</a
                                    >, edit this document type, and under
                                    <strong>Questions for residents</strong>
                                    add entries with <em>new</em> field keys (for example
                                    <code class="rounded bg-amber-100/90 px-1 py-0.5 font-mono text-[0.7rem]">business_name</code>,
                                    <code class="rounded bg-amber-100/90 px-1 py-0.5 font-mono text-[0.7rem]">dti_registration_no</code>). Do not reuse
                                    keys that already exist above (<code class="font-mono text-[0.7rem]">name</code>,
                                    <code class="font-mono text-[0.7rem]">address</code>,
                                    <code class="font-mono text-[0.7rem]">purpose</code>, etc.)—those stay as the layout defaults.
                                </p>
                            </div>

                            <div
                                v-else-if="!schemaLoading && !schemaError && fieldsSource === 'layout_with_extras' && extraFieldCount > 0"
                                class="rounded-lg border border-teal-200/90 bg-teal-50/80 px-3 py-2 text-xs text-teal-950"
                            >
                                <span class="font-medium">{{ extraFieldCount }} extra field(s)</span>
                                from this document type’s settings are included below and sent to AI with the base fields.
                            </div>

                            <div v-if="schemaLoading" class="flex items-center gap-2 text-sm text-gray-600">
                                <Loader2 class="h-4 w-4 animate-spin" />
                                Loading field definitions…
                            </div>
                            <p v-else-if="schemaError" class="text-sm text-red-600">{{ schemaError }}</p>

                            <div v-else-if="fields.length > 0" class="space-y-4">
                                <div
                                    v-for="field in fields"
                                    :key="field.name"
                                    :class="fieldStack"
                                >
                                    <Label class="text-sm font-medium text-gray-700" :for="`f-${field.name}`">
                                        {{ field.label }}
                                        <span v-if="field.required" class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        v-if="field.type === 'text' || field.type === 'number' || field.type === 'email' || field.type === 'time'"
                                        :id="`f-${field.name}`"
                                        v-model="fieldValues[field.name]"
                                        :type="field.type === 'number' ? 'number' : field.type === 'email' ? 'email' : field.type === 'time' ? 'time' : 'text'"
                                        :placeholder="field.placeholder"
                                        :class="inputClass"
                                    />
                                    <Input
                                        v-else-if="field.type === 'date'"
                                        :id="`f-${field.name}`"
                                        v-model="fieldValues[field.name]"
                                        type="date"
                                        :class="inputClass"
                                    />
                                    <select
                                        v-else-if="field.type === 'select'"
                                        :id="`f-${field.name}`"
                                        v-model="fieldValues[field.name]"
                                        class="h-10 w-full rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-900 shadow-xs focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    >
                                        <option value="">Select…</option>
                                        <option v-for="opt in field.options ?? []" :key="opt" :value="opt">
                                            {{ opt }}
                                        </option>
                                    </select>
                                    <Textarea
                                        v-else
                                        :id="`f-${field.name}`"
                                        v-model="fieldValues[field.name]"
                                        :placeholder="field.placeholder"
                                        rows="3"
                                        :class="inputClass"
                                    />
                                </div>
                            </div>

                            <div :class="fieldStack">
                                <Label class="text-sm font-medium text-gray-700" for="ootd">Officer of the Day (optional, for print layout)</Label>
                                <Input
                                    id="ootd"
                                    v-model="officerOfTheDay"
                                    type="text"
                                    placeholder="Leave empty for standard Punong Barangay signing line only"
                                    :class="inputClass"
                                />
                            </div>

                            <div class="flex flex-col gap-2 border-t border-gray-100 pt-4 sm:flex-row sm:flex-wrap">
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="border-teal-300 text-teal-800 hover:bg-teal-50"
                                    :disabled="loadingTemplate || loadingAi || !printLayout"
                                    @click="applyLoadTemplate"
                                >
                                    <Loader2 v-if="loadingTemplate" class="mr-2 h-4 w-4 animate-spin" />
                                    <FileText v-else class="mr-2 h-4 w-4" />
                                    Insert default body
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="border-blue-300 text-blue-800 hover:bg-blue-50"
                                    :disabled="loadingTemplate || loadingAi || !printLayout"
                                    @click="applyGenerateAi"
                                >
                                    <Loader2 v-if="loadingAi" class="mr-2 h-4 w-4 animate-spin" />
                                    <Sparkles v-else class="mr-2 h-4 w-4" />
                                    Generate with AI (manual)
                                </Button>
                                <Button
                                    type="button"
                                    class="bg-teal-600 hover:bg-teal-700"
                                    :disabled="!documentContent.trim()"
                                    @click="openPrint"
                                >
                                    <Printer class="mr-2 h-4 w-4" />
                                    Print preview (draft)
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-gray-200 shadow-lg xl:col-span-7">
                        <CardHeader class="gap-2 border-b border-gray-100">
                            <CardTitle class="text-lg text-gray-900">2. Certificate body (print area only)</CardTitle>
                            <CardDescription>
                                {{ selectedDocName ? `Editing content for ${selectedDocName}.` : 'Select a document type to begin.' }}
                                Edit only the main text that appears under “TO WHOM IT MAY CONCERN:” on the print preview—letterhead, document title,
                                salutation, signatures, and validity notes are added automatically there. Load default or AI fills that body from your
                                fields. Save certificate stores this HTML with the walk-in transaction (title and fee use the document type defaults).
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6 pb-6 pt-2">
                            <div class="overflow-hidden rounded-lg border-2 border-gray-200 bg-white shadow-inner">
                                <RichTextEditor v-model="documentContent" class="min-h-[480px]" />
                            </div>

                            <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-gray-50/80 p-4 sm:flex-row sm:items-start sm:justify-between sm:p-5">
                                <div class="min-w-0 text-sm text-gray-600">
                                    <p class="font-medium text-gray-900">Save certificate</p>
                                    <p class="mt-1 text-xs">
                                        Stores this draft as a walk-in transaction (requestor name and address come from section 1). You can keep
                                        editing on the transaction page.
                                    </p>
                                </div>
                                <div class="flex shrink-0 flex-col items-stretch gap-2 sm:items-end">
                                    <Button
                                        type="button"
                                        class="min-w-[200px] bg-blue-600 hover:bg-blue-700"
                                        :disabled="!canSaveCertificate || saveForm.processing"
                                        @click="saveCertificate"
                                    >
                                        <Loader2 v-if="saveForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                                        <Save v-else class="mr-2 h-4 w-4" />
                                        Save certificate
                                    </Button>
                                    <InputError :message="saveForm.errors['field_values.name']" />
                                    <p v-if="saveFormGeneralError()" class="text-sm text-red-600">{{ saveFormGeneralError() }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </StaffLayout>
</template>
