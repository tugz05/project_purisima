<script setup lang="ts">
import { computed, onMounted } from 'vue';

interface Stats {
    total_transactions: { current: number; change: number | null };
    revenue: { current: number; change: number | null };
    completed: { current: number; change: number | null };
    pending: { current: number; change: number | null };
    completion_rate: number;
    total_residents: number;
    status_breakdown: {
        pending: number;
        in_progress: number;
        completed: number;
        rejected: number;
    };
}

interface Meta {
    period_label: string;
    date_range: string;
    generated_at: string;
}

interface DocType {
    label: string;
    count: number;
}

interface PaymentMethod {
    method: string;
    count: number;
    total: number;
}

interface RecentTransaction {
    id: number;
    transaction_id: string;
    resident_name: string;
    document_type: string;
    status: string;
    payment_status: string;
    fee_amount: number;
    created_at: string;
}

const props = defineProps<{
    period: string;
    meta: Meta;
    stats: Stats;
    completionRate: number;
    documentTypes: DocType[];
    paymentMethods: PaymentMethod[];
    recentTransactions: RecentTransaction[];
    printedBy: string;
    printedAt: string;
}>();

const totalDocTypeCount = computed(() => props.documentTypes.reduce((s, d) => s + d.count, 0));
const totalPaymentCount = computed(() => props.paymentMethods.reduce((s, p) => s + p.count, 0));
const totalPaymentAmount = computed(() => props.paymentMethods.reduce((s, p) => s + p.total, 0));

function fmt(n: number) {
    return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
}

function statusLabel(s: string) {
    return s.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function changeText(change: number | null): string {
    if (change === null) return '—';
    return `${change >= 0 ? '+' : ''}${change}%`;
}

function printPage() {
    window.print();
}

onMounted(() => {
    document.title = `Barangay Report — ${props.meta.period_label}`;
});
</script>

<template>
    <div class="report-root">
        <!-- Screen action bar -->
        <div class="no-print action-bar">
            <div class="action-inner">
                <span class="action-label">{{ meta.period_label }} Report — {{ meta.date_range }}</span>
                <button class="print-btn" @click="printPage">🖨 Print / Save as PDF</button>
            </div>
        </div>

        <div class="page">

            <!-- ── Official Header ─────────────────────────────────────────── -->
            <header class="gov-header">
                <img src="/images/municipality-tago-seal.png" alt="Municipality of Tago Official Seal" class="gov-seal" />
                <div class="gov-center">
                    <p class="gov-line">Republic of the Philippines</p>
                    <p class="gov-line">Province of Surigao del Sur</p>
                    <p class="gov-line">Municipality of Tago</p>
                    <p class="gov-barangay">BARANGAY PURISIMA</p>
                    <p class="gov-ornament">❧ ❧ ❧</p>
                    <p class="gov-office">Office of the Punong Barangay</p>
                </div>
                <img src="/images/barangay-purisima-seal.png" alt="Barangay Purisima Seal" class="gov-seal" />
            </header>

            <!-- Blue double rule -->
            <div class="header-rule">
                <div class="rule-thick" />
                <div class="rule-thin" />
            </div>

            <!-- Report title block -->
            <div class="report-title-block">
                <h2 class="report-title">{{ meta.period_label }} Transaction Report</h2>
                <p class="report-range">{{ meta.date_range }}</p>
            </div>

            <hr class="divider" />

            <!-- Summary KPIs -->
            <section class="section">
                <h3 class="section-title">Summary</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th class="right">Value</th>
                            <th class="right">vs Previous Period</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Total Transactions</td>
                            <td class="right">{{ stats.total_transactions.current }}</td>
                            <td class="right change">{{ changeText(stats.total_transactions.change) }}</td>
                        </tr>
                        <tr>
                            <td>Completed Transactions</td>
                            <td class="right">{{ stats.completed.current }}</td>
                            <td class="right change">{{ changeText(stats.completed.change) }}</td>
                        </tr>
                        <tr>
                            <td>Pending / In-Progress</td>
                            <td class="right">{{ stats.pending.current }}</td>
                            <td class="right change">{{ changeText(stats.pending.change) }}</td>
                        </tr>
                        <tr>
                            <td>Total Revenue Collected</td>
                            <td class="right">₱ {{ fmt(stats.revenue.current) }}</td>
                            <td class="right change">{{ changeText(stats.revenue.change) }}</td>
                        </tr>
                        <tr>
                            <td>Completion Rate</td>
                            <td class="right">{{ completionRate }}%</td>
                            <td class="right">—</td>
                        </tr>
                        <tr>
                            <td>Total Registered Residents</td>
                            <td class="right">{{ stats.total_residents }}</td>
                            <td class="right">—</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- Status Breakdown -->
            <section class="section">
                <h3 class="section-title">Transaction Status Breakdown</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th class="right">Count</th>
                            <th class="right">% of Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(count, key) in stats.status_breakdown" :key="key">
                            <td>{{ statusLabel(String(key)) }}</td>
                            <td class="right">{{ count }}</td>
                            <td class="right">
                                {{ stats.total_transactions.current > 0
                                    ? ((count / stats.total_transactions.current) * 100).toFixed(1) + '%'
                                    : '—' }}
                            </td>
                        </tr>
                        <tr class="total-row">
                            <td><strong>Total</strong></td>
                            <td class="right"><strong>{{ stats.total_transactions.current }}</strong></td>
                            <td class="right"><strong>100%</strong></td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- Document Type Breakdown -->
            <section class="section" v-if="documentTypes.length">
                <h3 class="section-title">Document Type Breakdown</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Document Type</th>
                            <th class="right">Requests</th>
                            <th class="right">% Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="doc in documentTypes" :key="doc.label">
                            <td>{{ doc.label }}</td>
                            <td class="right">{{ doc.count }}</td>
                            <td class="right">
                                {{ totalDocTypeCount > 0
                                    ? ((doc.count / totalDocTypeCount) * 100).toFixed(1) + '%'
                                    : '—' }}
                            </td>
                        </tr>
                        <tr class="total-row">
                            <td><strong>Total</strong></td>
                            <td class="right"><strong>{{ totalDocTypeCount }}</strong></td>
                            <td class="right"><strong>100%</strong></td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- Payment Method Breakdown -->
            <section class="section" v-if="paymentMethods.length">
                <h3 class="section-title">Payment Method Breakdown</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th class="right">Transactions</th>
                            <th class="right">% of Paid</th>
                            <th class="right">Total Collected</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="pm in paymentMethods" :key="pm.method">
                            <td>{{ pm.method }}</td>
                            <td class="right">{{ pm.count }}</td>
                            <td class="right">
                                {{ totalPaymentCount > 0
                                    ? ((pm.count / totalPaymentCount) * 100).toFixed(1) + '%'
                                    : '—' }}
                            </td>
                            <td class="right">₱ {{ fmt(pm.total) }}</td>
                        </tr>
                        <tr class="total-row">
                            <td><strong>Total</strong></td>
                            <td class="right"><strong>{{ totalPaymentCount }}</strong></td>
                            <td class="right"><strong>100%</strong></td>
                            <td class="right"><strong>₱ {{ fmt(totalPaymentAmount) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- Recent Transactions -->
            <section class="section">
                <h3 class="section-title">Recent Transactions (up to 20)</h3>
                <table class="data-table small-text">
                    <thead>
                        <tr>
                            <th>Ref. No.</th>
                            <th>Resident</th>
                            <th>Document</th>
                            <th class="right">Fee</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="tx in recentTransactions" :key="tx.id">
                            <td class="mono">{{ tx.transaction_id }}</td>
                            <td>{{ tx.resident_name }}</td>
                            <td>{{ tx.document_type }}</td>
                            <td class="right">₱ {{ fmt(tx.fee_amount) }}</td>
                            <td>{{ statusLabel(tx.status) }}</td>
                            <td>{{ statusLabel(tx.payment_status) }}</td>
                            <td class="nowrap">{{ tx.created_at }}</td>
                        </tr>
                        <tr v-if="!recentTransactions.length">
                            <td colspan="7" class="empty-row">No transactions for this period.</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- ── Signatories ──────────────────────────────────────────────── -->
            <div class="signatories">
                <div class="signatory-col">
                    <p class="sig-label">Prepared by:</p>
                    <div class="sig-line" />
                    <p class="sig-name">{{ printedBy }}</p>
                    <p class="sig-role">Barangay Staff</p>
                </div>
                <div class="signatory-col signatory-col--center">
                    <p class="sig-label">Noted by:</p>
                    <div class="sig-line" />
                    <p class="sig-name">EMMANUEL P. ISIANG</p>
                    <p class="sig-role">Punong Barangay</p>
                </div>
                <div class="signatory-col signatory-col--right">
                    <p class="sig-label">Date Printed:</p>
                    <p class="sig-date">{{ printedAt }}</p>
                </div>
            </div>

            <!-- Page footer -->
            <footer class="report-footer">
                <p>Generated by Barangay Purisima Management System · {{ meta.generated_at }}</p>
                <p>This is a computer-generated report and is valid without a signature.</p>
            </footer>
        </div>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap');

/* ── Reset ──────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ── Base ──────────────────────────────────────────────────── */
.report-root {
    background: #f3f4f6;
    min-height: 100vh;
    font-family: 'Georgia', serif;
    color: #111;
}

/* ── Action bar ─────────────────────────────────────────────── */
.action-bar {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #1e3a5f;
    padding: 10px 24px;
}
.action-inner {
    max-width: 900px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.action-label { color: #cbd5e1; font-size: 13px; font-family: sans-serif; }
.print-btn {
    background: #3b82f6;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 7px 18px;
    font-size: 13px;
    font-family: sans-serif;
    cursor: pointer;
}
.print-btn:hover { background: #2563eb; }

/* ── Page ───────────────────────────────────────────────────── */
.page {
    max-width: 900px;
    margin: 28px auto;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 36px 48px 32px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
}

/* ── Official Government Header ─────────────────────────────── */
.gov-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding-bottom: 10px;
}
.gov-seal {
    width: 90px;
    height: 90px;
    object-fit: contain;
    flex-shrink: 0;
}
.gov-center {
    flex: 1;
    text-align: center;
}
.gov-line {
    font-size: 12px;
    color: #333;
    line-height: 1.6;
    font-family: Arial, sans-serif;
}
.gov-barangay {
    font-size: 17px;
    font-weight: 800;
    color: #1e3a5f;
    letter-spacing: 0.04em;
    margin-top: 2px;
    font-family: Arial, sans-serif;
}
.gov-ornament {
    font-size: 10px;
    color: #1e3a5f;
    letter-spacing: 4px;
    margin: 2px 0;
}
.gov-office {
    font-family: 'Great Vibes', cursive;
    font-size: 22px;
    color: #1e3a5f;
    line-height: 1.3;
}

/* ── Header rule (blue double line) ─────────────────────────── */
.header-rule {
    display: flex;
    flex-direction: column;
    gap: 3px;
    margin: 6px 0 10px;
}
.rule-thick {
    height: 4px;
    background: #1e3a5f;
    border-radius: 1px;
}
.rule-thin {
    height: 1.5px;
    background: #3b82f6;
    border-radius: 1px;
}

/* ── Report title block ─────────────────────────────────────── */
.report-title-block {
    text-align: center;
    margin-bottom: 10px;
}
.report-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e3a5f;
    font-family: Arial, sans-serif;
}
.report-range {
    font-size: 12px;
    color: #555;
    margin-top: 2px;
    font-family: Arial, sans-serif;
}

/* ── Divider ────────────────────────────────────────────────── */
.divider {
    border: none;
    border-top: 1px solid #d1d5db;
    margin: 12px 0 20px;
}

/* ── Sections ───────────────────────────────────────────────── */
.section { margin-bottom: 24px; }
.section-title {
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #1e3a5f;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 4px;
    margin-bottom: 8px;
    font-family: Arial, sans-serif;
}

/* ── Tables ─────────────────────────────────────────────────── */
.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
}
.data-table th {
    background: #f1f5f9;
    font-weight: 700;
    padding: 6px 10px;
    text-align: left;
    border: 1px solid #e2e8f0;
    font-size: 11.5px;
    font-family: Arial, sans-serif;
}
.data-table td {
    padding: 5px 10px;
    border: 1px solid #e2e8f0;
    vertical-align: top;
}
.data-table tr:nth-child(even) td { background: #f9fafb; }
.data-table .right { text-align: right; }
.data-table .change { font-size: 11px; color: #6b7280; }
.data-table .total-row td { background: #eff6ff; border-top: 2px solid #bfdbfe; }
.data-table .empty-row { text-align: center; color: #9ca3af; font-style: italic; padding: 14px; }
.small-text.data-table th,
.small-text.data-table td { font-size: 11px; padding: 4px 7px; }
.mono { font-family: 'Courier New', monospace; font-size: 10.5px; }
.nowrap { white-space: nowrap; }

/* ── Signatories ─────────────────────────────────────────────── */
.signatories {
    display: flex;
    gap: 0;
    margin-top: 36px;
    padding-top: 16px;
    border-top: 1px solid #d1d5db;
}
.signatory-col {
    flex: 1;
}
.signatory-col--center {
    text-align: center;
    border-left: 1px dashed #d1d5db;
    border-right: 1px dashed #d1d5db;
    padding: 0 16px;
}
.signatory-col--right {
    text-align: right;
    padding-left: 16px;
}
.sig-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #6b7280;
    font-family: Arial, sans-serif;
    margin-bottom: 28px;
}
.sig-line {
    border-top: 1px solid #374151;
    margin-bottom: 4px;
}
.sig-name {
    font-size: 12px;
    font-weight: 700;
    color: #111827;
    font-family: Arial, sans-serif;
}
.sig-role {
    font-size: 10.5px;
    color: #6b7280;
    font-family: Arial, sans-serif;
}
.sig-date {
    font-size: 12px;
    font-weight: 600;
    color: #111827;
    font-family: Arial, sans-serif;
    margin-top: 4px;
}

/* ── Page footer ─────────────────────────────────────────────── */
.report-footer {
    margin-top: 24px;
    border-top: 1px solid #e5e7eb;
    padding-top: 10px;
    text-align: center;
    font-size: 10px;
    color: #9ca3af;
    line-height: 1.7;
    font-family: Arial, sans-serif;
}

/* ── Print ───────────────────────────────────────────────────── */
@media print {
    @page { size: A4; margin: 14mm 16mm 14mm; }

    .no-print { display: none !important; }

    body { background: #fff !important; }

    .report-root { background: #fff; min-height: unset; }

    .page {
        margin: 0;
        border: none;
        border-radius: 0;
        box-shadow: none;
        padding: 0;
        max-width: 100%;
    }

    .gov-seal { width: 80px; height: 80px; }

    .data-table th {
        background: #e2e8f0 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .data-table tr:nth-child(even) td {
        background: #f9fafb !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .data-table .total-row td {
        background: #dbeafe !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .rule-thick, .rule-thin {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .section { page-break-inside: avoid; }
    .signatories { page-break-inside: avoid; }
}
</style>
