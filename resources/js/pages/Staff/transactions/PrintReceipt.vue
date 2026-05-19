<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';

interface Resident {
    name: string;
    email: string;
    purok?: string | null;
}

interface DocumentType {
    name: string;
    code: string;
}

interface Staff {
    name: string;
}

interface Transaction {
    id: number;
    transaction_id: string;
    title: string;
    fee_amount: string | number;
    amount_paid: string | number;
    payment_method: string | null;
    payment_reference: string | null;
    receipt_number: string | null;
    payment_date: string | null;
    resident: Resident | null;
    document_type: DocumentType | null;
    payment_verifier: Staff | null;
    staff: Staff | null;
}

interface Props {
    transaction: Transaction;
    barangayName: string;
    municipality: string;
    province: string;
    printedBy: string;
    printedAt: string;
}

const props = defineProps<Props>();

const formatPeso = (val: string | number | null): string => {
    if (val === null || val === undefined) return '₱0.00';
    return '₱' + Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const methodLabel = (m: string | null): string => {
    const map: Record<string, string> = {
        cash: 'Cash',
        gcash: 'GCash',
        paymaya: 'PayMaya',
        bank_transfer: 'Bank Transfer',
        check: 'Check',
    };
    return m ? (map[m] ?? m) : '—';
};

onMounted(() => {
    setTimeout(() => window.print(), 400);
});
</script>

<template>
    <Head title="Print Receipt" />

    <!-- Screen preview wrapper -->
    <div class="print-page-wrapper">
        <div class="receipt-sheet">

            <!-- Cut indicator top -->
            <div class="cut-line cut-line--top">
                <span class="cut-icon">✂</span>
                <span class="cut-label">CUT HERE</span>
                <span class="cut-icon">✂</span>
            </div>

            <!-- Receipt body -->
            <div class="receipt-body">

                <!-- Official Government Header -->
                <div class="gov-header">
                    <img src="/images/municipality-tago-seal.png" alt="Municipality of Tago Seal" class="gov-seal" />
                    <div class="gov-center">
                        <p class="gov-republic">Republic of the Philippines</p>
                        <p class="gov-province">Province of Surigao del Sur</p>
                        <p class="gov-municipality">{{ props.municipality }}</p>
                        <p class="gov-barangay">{{ props.barangayName }}</p>
                        <p class="gov-ornament">❧ &nbsp; ❧ &nbsp; ❧</p>
                        <p class="gov-office">Office of the Punong Barangay</p>
                        <p class="gov-doc-label">OFFICIAL RECEIPT</p>
                    </div>
                    <div class="gov-right">
                        <img src="/images/barangay-purisima-seal.png" alt="Barangay Purisima Seal" class="gov-seal" />
                        <div class="receipt-no-block">
                            <p class="receipt-no-label">Receipt No.</p>
                            <p class="receipt-no-value">{{ props.transaction.receipt_number ?? '—' }}</p>
                        </div>
                    </div>
                </div>
                <!-- Blue double rule -->
                <div class="rule-thick"></div>
                <div class="rule-thin"></div>

                <!-- Billed to / date row -->
                <div class="receipt-meta">
                    <div class="receipt-meta__left">
                        <p class="meta-label">Received from</p>
                        <p class="meta-value meta-value--bold">{{ props.transaction.resident?.name ?? 'Walk-in / Anonymous' }}</p>
                        <p v-if="props.transaction.resident?.purok" class="meta-sub">{{ props.transaction.resident.purok }}</p>
                    </div>
                    <div class="receipt-meta__right">
                        <div>
                            <p class="meta-label">Date</p>
                            <p class="meta-value">{{ props.transaction.payment_date ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="meta-label">Transaction ID</p>
                            <p class="meta-value meta-value--mono">{{ props.transaction.transaction_id }}</p>
                        </div>
                    </div>
                </div>

                <!-- Divider -->
                <div class="divider" />

                <!-- Description + payment info -->
                <div class="receipt-details">
                    <div class="receipt-details__left">
                        <p class="meta-label">Description</p>
                        <p class="meta-value meta-value--bold">
                            {{ props.transaction.document_type?.name ?? props.transaction.title }}
                        </p>
                        <p v-if="props.transaction.document_type?.code" class="meta-sub">
                            Code: {{ props.transaction.document_type.code }}
                        </p>

                        <p class="meta-label" style="margin-top: 8px;">Payment Method</p>
                        <p class="meta-value">{{ methodLabel(props.transaction.payment_method) }}</p>

                        <template v-if="props.transaction.payment_reference">
                            <p class="meta-label" style="margin-top: 6px;">Reference No.</p>
                            <p class="meta-value meta-value--mono">{{ props.transaction.payment_reference }}</p>
                        </template>
                    </div>

                    <div class="receipt-details__right">
                        <p class="amount-label">Fee Amount</p>
                        <p class="amount-fee">{{ formatPeso(props.transaction.fee_amount) }}</p>
                        <div class="amount-box">
                            <p class="amount-paid-label">AMOUNT PAID</p>
                            <p class="amount-paid-value">{{ formatPeso(props.transaction.amount_paid) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Divider -->
                <div class="divider" />

                <!-- Signatories -->
                <div class="signatories">
                    <div class="sig-col">
                        <p class="sig-label">Processed by:</p>
                        <div class="sig-line"></div>
                        <p class="sig-name">{{ props.transaction.staff?.name ?? '—' }}</p>
                        <p class="sig-title">Barangay Staff</p>
                    </div>
                    <div class="sig-col">
                        <p class="sig-label">Verified by:</p>
                        <div class="sig-line"></div>
                        <p class="sig-name">{{ props.transaction.payment_verifier?.name ?? '—' }}</p>
                        <p class="sig-title">Payment Officer</p>
                    </div>
                    <div class="sig-col sig-col--right">
                        <p class="sig-label">Printed by:</p>
                        <div class="sig-line"></div>
                        <p class="sig-name">{{ props.printedBy }}</p>
                        <p class="sig-title">Date: {{ props.printedAt }}</p>
                    </div>
                </div>

                <!-- Validity note -->
                <p class="validity-note">
                    This receipt is valid only when signed and stamped by an authorized officer. Not valid if altered.
                </p>

            </div><!-- /receipt-body -->

            <!-- Cut indicator bottom -->
            <div class="cut-line cut-line--bottom">
                <span class="cut-icon">✂</span>
                <span class="cut-label">CUT HERE</span>
                <span class="cut-icon">✂</span>
            </div>

        </div><!-- /receipt-sheet -->
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap');

/* ── Reset ──────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body { background: #e5e7eb; font-family: 'Arial', sans-serif; }

/* ── Screen wrapper ─────────────────────────────────────────── */
.print-page-wrapper {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
    padding: 2rem 1rem;
    background: #e5e7eb;
}

/* ── Receipt sheet — half short bond: 8.5 × 5.5 in ─────────── */
.receipt-sheet {
    width: 8.5in;
    min-height: 5.5in;
    background: #fff;
    box-shadow: 0 4px 24px rgba(0,0,0,0.18);
    display: flex;
    flex-direction: column;
}

/* ── Cut-line strips ────────────────────────────────────────── */
.cut-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 16px;
    background: #f9fafb;
    border-top: 2px dashed #9ca3af;
    border-bottom: 2px dashed #9ca3af;
}
.cut-line--top { border-top: none; }
.cut-line--bottom { border-bottom: none; }
.cut-icon { font-size: 13px; color: #6b7280; }
.cut-label {
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.15em;
    color: #6b7280;
    text-transform: uppercase;
}

/* ── Receipt body ───────────────────────────────────────────── */
.receipt-body {
    flex: 1;
    padding: 14px 28px 14px;
    border-left: 2px dashed #d1d5db;
    border-right: 2px dashed #d1d5db;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* ── Official government header ─────────────────────────────── */
.gov-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding-bottom: 6px;
}
.gov-seal {
    width: 62px;
    height: 62px;
    object-fit: contain;
    flex-shrink: 0;
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
}
.gov-center {
    flex: 1;
    text-align: center;
    line-height: 1.25;
}
.gov-republic  { font-size: 9px;  color: #374151; letter-spacing: 0.03em; }
.gov-province  { font-size: 9px;  color: #374151; }
.gov-municipality { font-size: 9px; color: #374151; }
.gov-barangay  { font-size: 13px; font-weight: 800; color: #111827; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 1px; }
.gov-ornament  { font-size: 10px; color: #374151; margin: 1px 0; }
.gov-office    { font-family: 'Great Vibes', cursive; font-size: 17px; color: #1e3a8a; line-height: 1.2; }
.gov-doc-label { font-size: 10px; font-weight: 800; color: #1d4ed8; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 2px; }

.gov-right {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
}
.receipt-no-block { text-align: center; }
.receipt-no-label { font-size: 8px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.08em; }
.receipt-no-value { font-size: 13px; font-weight: 800; color: #111827; font-family: monospace; }

/* ── Blue double rule ───────────────────────────────────────── */
.rule-thick {
    height: 4px;
    background: #1e3a8a;
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
}
.rule-thin {
    height: 1.5px;
    background: #3b82f6;
    margin-top: 2px;
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
}

/* ── Divider ─────────────────────────────────────────────────── */
.divider { border: none; border-top: 1px solid #e5e7eb; }

/* ── Meta row (received from / date) ────────────────────────── */
.receipt-meta {
    display: flex;
    justify-content: space-between;
    gap: 12px;
}
.receipt-meta__left { flex: 1; }
.receipt-meta__right {
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: flex-end;
    text-align: right;
    flex-shrink: 0;
}

/* ── Details row ────────────────────────────────────────────── */
.receipt-details {
    display: flex;
    justify-content: space-between;
    gap: 12px;
}
.receipt-details__left { flex: 1; }
.receipt-details__right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    text-align: right;
    flex-shrink: 0;
    min-width: 130px;
}

.amount-label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px; }
.amount-fee { font-size: 12px; color: #374151; margin-bottom: 8px; }
.amount-box {
    background: #1d4ed8;
    color: #fff;
    border-radius: 6px;
    padding: 8px 14px;
    text-align: center;
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
}
.amount-paid-label { font-size: 8px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; opacity: 0.85; }
.amount-paid-value { font-size: 20px; font-weight: 800; font-family: monospace; line-height: 1.1; margin-top: 2px; }

/* ── Signatories ────────────────────────────────────────────── */
.signatories {
    display: flex;
    gap: 20px;
    margin-top: auto;
    padding-top: 4px;
}
.sig-col { flex: 1; }
.sig-col--right { text-align: right; }
.sig-label { font-size: 8.5px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 18px; }
.sig-line { border-top: 1px solid #374151; margin-bottom: 3px; }
.sig-name { font-size: 10px; font-weight: 700; color: #111827; }
.sig-title { font-size: 8.5px; color: #6b7280; }

/* ── Shared typography ──────────────────────────────────────── */
.meta-label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #6b7280;
    margin-bottom: 2px;
}
.meta-value { font-size: 11px; color: #111827; }
.meta-value--bold { font-weight: 700; font-size: 12px; }
.meta-value--mono { font-family: monospace; font-size: 11px; }
.meta-sub { font-size: 9px; color: #6b7280; margin-top: 1px; }

/* ── Validity note ──────────────────────────────────────────── */
.validity-note {
    font-size: 8px;
    color: #9ca3af;
    text-align: center;
    font-style: italic;
    padding-top: 4px;
}

/* ── Print media ─────────────────────────────────────────────── */
@media print {
    @page {
        size: 8.5in 5.5in;
        margin: 0;
    }

    body { background: #fff !important; }

    .print-page-wrapper {
        display: block;
        padding: 0;
        min-height: unset;
        background: #fff !important;
    }

    .receipt-sheet {
        width: 100%;
        min-height: unset;
        box-shadow: none;
    }

    .amount-box,
    .rule-thick,
    .rule-thin,
    .gov-seal {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
}
</style>
