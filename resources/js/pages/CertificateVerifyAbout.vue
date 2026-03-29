<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileSearch } from 'lucide-vue-next';
</script>

<template>
    <Head title="Certificate verification" />

    <div class="min-h-screen bg-slate-50 px-4 py-10 text-slate-900">
        <div class="mx-auto max-w-lg rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="mb-4 flex justify-center text-teal-700">
                <FileSearch class="h-10 w-10" />
            </div>
            <h1 class="text-center text-xl font-semibold text-slate-900">Certificate QR codes</h1>
            <p class="mt-4 text-sm leading-relaxed text-slate-600">
                <strong class="text-slate-800">Draft printouts</strong> (before the certificate is saved as a barangay transaction) may show a QR
                that opens this page. A unique verification code is only issued after staff save the certificate to transactions and print from the
                transaction record.
            </p>
            <p class="mt-3 text-sm leading-relaxed text-slate-600">
                To confirm a certificate against barangay records, use the QR printed on a <strong class="text-slate-800">saved</strong> document, or
                contact the Barangay office with your reference details.
            </p>
            <p class="mt-6 text-center text-sm">
                <Link href="/" class="font-medium text-teal-700 underline-offset-2 hover:underline">Back to home</Link>
            </p>
        </div>
    </div>
</template>
