import { computed } from 'vue';

export function useUtils() {
    // Date formatting
    const formatDate = (dateString: string, options: Intl.DateTimeFormatOptions = {}) => {
        const date = new Date(dateString);
        const defaultOptions: Intl.DateTimeFormatOptions = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        };

        return date.toLocaleDateString('en-US', { ...defaultOptions, ...options });
    };

    const formatDateShort = (dateString: string) => {
        return formatDate(dateString, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const formatTime = (dateString: string) => {
        return formatDate(dateString, {
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const formatDateTime = (dateString: string) => {
        return formatDate(dateString, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    // Currency formatting
    const formatCurrency = (amount: number | string, currency: string = 'PHP') => {
        const numAmount = Number(amount);
        if (isNaN(numAmount)) return '₱0.00';

        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: currency,
        }).format(numAmount);
    };

    const formatPeso = (amount: number | string) => {
        const numAmount = Number(amount);
        if (isNaN(numAmount)) return '₱0.00';

        return `₱${numAmount.toFixed(2)}`;
    };

    // String utilities
    const capitalize = (str: string) => {
        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
    };

    const capitalizeWords = (str: string) => {
        return str.replace(/\b\w/g, l => l.toUpperCase());
    };

    const slugify = (str: string) => {
        return str
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    };

    const truncate = (str: string, length: number = 50, suffix: string = '...') => {
        if (str.length <= length) return str;
        return str.substring(0, length) + suffix;
    };

    // Number utilities
    const formatNumber = (num: number | string) => {
        const number = Number(num);
        if (isNaN(number)) return '0';

        return new Intl.NumberFormat('en-US').format(number);
    };

    const formatPercentage = (value: number, total: number) => {
        if (total === 0) return '0%';
        const percentage = (value / total) * 100;
        return `${percentage.toFixed(1)}%`;
    };

    // Validation utilities
    const isValidEmail = (email: string) => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    };

    const isValidPhone = (phone: string) => {
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    };

    const isValidDate = (dateString: string) => {
        const date = new Date(dateString);
        return date instanceof Date && !isNaN(date.getTime());
    };

    // Array utilities
    const groupBy = <T>(array: T[], key: keyof T) => {
        return array.reduce((groups, item) => {
            const group = String(item[key]);
            groups[group] = groups[group] || [];
            groups[group].push(item);
            return groups;
        }, {} as Record<string, T[]>);
    };

    const sortBy = <T>(array: T[], key: keyof T, direction: 'asc' | 'desc' = 'asc') => {
        return [...array].sort((a, b) => {
            const aVal = a[key];
            const bVal = b[key];

            if (aVal < bVal) return direction === 'asc' ? -1 : 1;
            if (aVal > bVal) return direction === 'asc' ? 1 : -1;
            return 0;
        });
    };

    const unique = <T>(array: T[]) => {
        return [...new Set(array)];
    };

    // Color utilities
    const getRandomColor = () => {
        const colors = [
            'bg-red-100 text-red-800',
            'bg-blue-100 text-blue-800',
            'bg-green-100 text-green-800',
            'bg-yellow-100 text-yellow-800',
            'bg-purple-100 text-purple-800',
            'bg-pink-100 text-pink-800',
            'bg-indigo-100 text-indigo-800',
            'bg-gray-100 text-gray-800',
        ];
        return colors[Math.floor(Math.random() * colors.length)];
    };

    const getStatusColor = (status: string) => {
        const colors: Record<string, string> = {
            active: 'bg-green-100 text-green-800',
            inactive: 'bg-gray-100 text-gray-800',
            pending: 'bg-yellow-100 text-yellow-800',
            approved: 'bg-green-100 text-green-800',
            rejected: 'bg-red-100 text-red-800',
            completed: 'bg-blue-100 text-blue-800',
        };
        return colors[status.toLowerCase()] || 'bg-gray-100 text-gray-800';
    };

    return {
        // Date formatting
        formatDate,
        formatDateShort,
        formatTime,
        formatDateTime,

        // Currency formatting
        formatCurrency,
        formatPeso,

        // String utilities
        capitalize,
        capitalizeWords,
        slugify,
        truncate,

        // Number utilities
        formatNumber,
        formatPercentage,

        // Validation utilities
        isValidEmail,
        isValidPhone,
        isValidDate,

        // Array utilities
        groupBy,
        sortBy,
        unique,

        // Color utilities
        getRandomColor,
        getStatusColor,
    };
}
