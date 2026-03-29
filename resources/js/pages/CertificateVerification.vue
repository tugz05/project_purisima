<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { AlertCircle, CheckCircle2, FileText } from 'lucide-vue-next';

interface Props {
    valid: boolean;
    documentTypeName?: string;
    requestorDisplay?: string;
    status?: string;
    issuedAt?: string | null;
    referenceLabel?: string;
}

const props = defineProps<Props>();

const issuedReadable = computed((): string | null => {
    if (!props.issuedAt) {
        return null;
    }
    try {
        return new Date(props.issuedAt).toLocaleString(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short',
        });
    } catch {
        return props.issuedAt;
    }
});

const statusLabel = computed((): string => {
    const s = props.status ?? '';
    return s === '' ? '—' : s.replace(/_/g, ' ');
});
</script>

<template>
    <Head title="Certificate verification" />

    <div class="min-h-screen bg-gradient-to-b from-slate-50 to-slate-100 px-4 py-10 text-slate-900">
        <div class="mx-auto max-w-lg">
            <div class="mb-6 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-teal-100 text-teal-800">
                    <FileText class="h-6 w-6" />
                </div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-900">Barangay certificate verification</h1>
                <p class="mt-1 text-sm text-slate-600">Official record check for documents issued through this system.</p>
            </div>

            <div
                v-if="!valid"
                class="rounded-2xl border border-amber-200 bg-white p-6 shadow-sm"
                role="status"
            >
                <div class="flex gap-3">
                    <AlertCircle class="h-6 w-6 shrink-0 text-amber-600" />
                    <div>
                        <p class="font-medium text-slate-900">No matching certificate</p>
                        <p class="mt-1 text-sm leading-relaxed text-slate-600">
                            This link does not match a certificate on file. The code may be incorrect, or the document may not have been saved to the
                            barangay records yet.
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="rounded-2xl border border-teal-200 bg-white p-6 shadow-sm"
                role="status"
            >
                <div class="flex gap-3 border-b border-slate-100 pb-4">
                    <CheckCircle2 class="h-6 w-6 shrink-0 text-teal-600" />
                    <div>
                        <p class="font-medium text-slate-900">Certificate on file</p>
                        <p class="mt-1 text-sm text-slate-600">
                            The following information matches a transaction in Barangay Purisima records.
                        </p>
                    </div>
                </div>

                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Document type</dt>
                        <dd class="mt-0.5 font-medium text-slate-900">{{ documentTypeName }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Requestor</dt>
                        <dd class="mt-0.5 text-slate-900">{{ requestorDisplay }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Record status</dt>
                        <dd class="mt-0.5 capitalize text-slate-900">{{ statusLabel }}</dd>
                    </div>
                    <div v-if="issuedReadable">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Document last updated</dt>
                        <dd class="mt-0.5 text-slate-900">{{ issuedReadable }}</dd>
                    </div>
                    <div v-if="referenceLabel">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Internal reference</dt>
                        <dd class="mt-0.5 font-mono text-xs text-slate-700">{{ referenceLabel }}</dd>
                    </div>
                </dl>

                <p class="mt-5 text-xs leading-relaxed text-slate-500">
                    This page confirms that a certificate with this verification code exists in the system. For legal interpretation or contested cases,
                    contact the Barangay office with this reference.
                </p>
            </div>

            <p class="mt-8 text-center text-sm text-slate-500">
                <Link href="/" class="font-medium text-teal-700 underline-offset-2 hover:underline">Back to home</Link>
            </p>
        </div>
    </div>
</template>
