import { ref, computed } from 'vue';
import { Clock, CheckCircle, XCircle, AlertCircle } from 'lucide-vue-next';

export interface Transaction {
    id: number;
    transaction_id: string;
    type: string;
    title: string;
    description?: string;
    status: string;
    fee_amount: number | string;
    fee_paid: boolean;
    submitted_at: string;
    created_at: string;
    resident?: {
        name: string;
        first_name: string;
        last_name: string;
    };
    staff?: {
        name: string;
    };
}

export interface TransactionType {
    name: string;
    fee: number;
    required_documents: string[];
}

export interface TransactionStatistics {
    total: number;
    pending: number;
    in_progress: number;
    completed: number;
    rejected: number;
}

export interface TransactionFilters {
    status?: string;
    type?: string;
    staff_id?: string;
    search?: string;
}

export interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    in_progress: 'bg-blue-100 text-blue-800 border-blue-200',
    approved: 'bg-green-100 text-green-800 border-green-200',
    rejected: 'bg-red-100 text-red-800 border-red-200',
    completed: 'bg-green-100 text-green-800 border-green-200',
};

const statusIcons = {
    pending: Clock,
    in_progress: AlertCircle,
    approved: CheckCircle,
    rejected: XCircle,
    completed: CheckCircle,
};

export function useTransactions() {
    const selectedType = ref('');
    const sheetOpen = ref(false);

    const getStatusColor = (status: string) => {
        return statusColors[status as keyof typeof statusColors] || 'bg-gray-100 text-gray-800 border-gray-200';
    };

    const getStatusIcon = (status: string) => {
        return statusIcons[status as keyof typeof statusIcons] || Clock;
    };

    const getTypeName = (type: string, transactionTypes: Record<string, TransactionType>) => {
        return transactionTypes[type]?.name || type.replace('_', ' ').toUpperCase();
    };

    const formatFeeAmount = (amount: number | string) => {
        const numAmount = Number(amount);
        return isNaN(numAmount) ? '0.00' : numAmount.toFixed(2);
    };

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getStatusLabel = (status: string) => {
        const labels = {
            pending: 'Pending',
            in_progress: 'In Progress',
            approved: 'Approved',
            rejected: 'Rejected',
            completed: 'Completed',
        };
        return labels[status as keyof typeof labels] || status;
    };

    const isStatusPending = (status: string) => status === 'pending';
    const isStatusInProgress = (status: string) => status === 'in_progress';
    const isStatusCompleted = (status: string) => status === 'completed';
    const isStatusRejected = (status: string) => status === 'rejected';

    const canEditTransaction = (transaction: Transaction) => {
        return ['pending', 'in_progress'].includes(transaction.status);
    };

    const canCancelTransaction = (transaction: Transaction) => {
        return ['pending', 'in_progress'].includes(transaction.status);
    };

    const getTransactionProgress = (status: string) => {
        const progress = {
            pending: 25,
            in_progress: 50,
            approved: 75,
            completed: 100,
            rejected: 0,
        };
        return progress[status as keyof typeof progress] || 0;
    };

    return {
        // State
        selectedType,
        sheetOpen,

        // Status utilities
        getStatusColor,
        getStatusIcon,
        getStatusLabel,
        isStatusPending,
        isStatusInProgress,
        isStatusCompleted,
        isStatusRejected,

        // Transaction utilities
        getTypeName,
        formatFeeAmount,
        formatDate,
        canEditTransaction,
        canCancelTransaction,
        getTransactionProgress,
    };
}
