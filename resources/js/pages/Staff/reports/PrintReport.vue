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
        <!-- Print button (hidden when printing) -->
        <div class="no-print action-bar">
            <div class="action-inner">
                <span class="action-label">{{ meta.period_label }} Report &mdash; {{ meta.date_range }}</span>
                <button class="print-btn" @click="printPage">🖨 Print / Save as PDF</button>
            </div>
        </div>

        <div class="page">
            <!-- Header -->
            <header class="report-header">
                <div class="header-logo">
                    <img src="/images/logo.png" alt="Barangay Logo" class="logo-img" />
                </div>
                <div class="header-text">
                    <p class="header-line">Republic of the Philippines</p>
                    <p class="header-line">Province of Rizal &bull; Municipality of San Mateo</p>
                    <h1 class="header-barangay">Barangay Purisima</h1>
                    <h2 class="header-report-title">{{ meta.period_label }} Transaction Report</h2>
                    <p class="header-range">{{ meta.date_range }}</p>
                </div>
            </header>

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
                            <td>{{ statusLabel(key) }}</td>
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

            <!-- Footer -->
            <footer class="report-footer">
                <p>Generated by Barangay Purisima Management System on {{ meta.generated_at }}</p>
                <p>This is a computer-generated report and is valid without a signature.</p>
            </footer>
        </div>
    </div>
</template>

<style scoped>
/* ── Base ──────────────────────────────────────────────────────── */
.report-root {
    background: #f3f4f6;
    min-height: 100vh;
    font-family: 'Georgia', serif;
    color: #111;
}

/* ── Action bar (screen only) ─────────────────────────────────── */
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
.action-label {
    color: #cbd5e1;
    font-size: 13px;
    font-family: sans-serif;
}
.print-btn {
    background: #3b82f6;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 7px 18px;
    font-size: 13px;
    font-family: sans-serif;
    cursor: pointer;
    transition: background 0.15s;
}
.print-btn:hover {
    background: #2563eb;
}

/* ── Page ──────────────────────────────────────────────────────── */
.page {
    max-width: 900px;
    margin: 28px auto;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 48px 56px 40px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
}

/* ── Header ────────────────────────────────────────────────────── */
.report-header {
    display: flex;
    align-items: center;
    gap: 24px;
    margin-bottom: 20px;
}
.logo-img {
    width: 80px;
    height: 80px;
    object-fit: contain;
    flex-shrink: 0;
}
.header-text {
    flex: 1;
    text-align: center;
}
.header-line {
    font-size: 12px;
    color: #555;
    margin: 0;
    line-height: 1.5;
}
.header-barangay {
    font-size: 22px;
    font-weight: 700;
    margin: 4px 0 2px;
    color: #1e3a5f;
}
.header-report-title {
    font-size: 15px;
    font-weight: 600;
    margin: 2px 0;
    color: #374151;
}
.header-range {
    font-size: 12px;
    color: #666;
    margin: 0;
}

/* ── Divider ───────────────────────────────────────────────────── */
.divider {
    border: none;
    border-top: 2px solid #1e3a5f;
    margin: 16px 0 24px;
}

/* ── Sections ──────────────────────────────────────────────────── */
.section {
    margin-bottom: 28px;
}
.section-title {
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #1e3a5f;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 5px;
    margin-bottom: 10px;
}

/* ── Tables ────────────────────────────────────────────────────── */
.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.data-table th {
    background: #f1f5f9;
    font-weight: 600;
    padding: 7px 10px;
    text-align: left;
    border: 1px solid #e2e8f0;
    font-size: 12px;
}
.data-table td {
    padding: 6px 10px;
    border: 1px solid #e2e8f0;
    vertical-align: top;
}
.data-table tr:nth-child(even) td {
    background: #f9fafb;
}
.data-table .right {
    text-align: right;
}
.data-table .change {
    font-size: 12px;
    color: #6b7280;
}
.data-table .total-row td {
    background: #eff6ff;
    border-top: 2px solid #bfdbfe;
}
.data-table .empty-row {
    text-align: center;
    color: #9ca3af;
    font-style: italic;
    padding: 14px;
}
.small-text.data-table th,
.small-text.data-table td {
    font-size: 11.5px;
    padding: 5px 8px;
}
.mono {
    font-family: 'Courier New', monospace;
    font-size: 11px;
}
.nowrap {
    white-space: nowrap;
}

/* ── Footer ────────────────────────────────────────────────────── */
.report-footer {
    margin-top: 32px;
    border-top: 1px solid #e5e7eb;
    padding-top: 12px;
    text-align: center;
    font-size: 11px;
    color: #9ca3af;
    line-height: 1.6;
}

/* ── Print ──────────────────────────────────────────────────────── */
@media print {
    .no-print {
        display: none !important;
    }
    .report-root {
        background: #fff;
        min-height: unset;
    }
    .page {
        margin: 0;
        border: none;
        border-radius: 0;
        box-shadow: none;
        padding: 24px 32px 20px;
        max-width: 100%;
    }
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
    .section {
        page-break-inside: avoid;
    }
}
</style>
