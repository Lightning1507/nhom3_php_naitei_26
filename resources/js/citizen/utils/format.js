const VIETNAMESE_LOCALE = 'vi-VN';

export function formatDate(value) {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return date.toLocaleDateString(VIETNAMESE_LOCALE, {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

export function formatDateTime(value) {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return date.toLocaleString(VIETNAMESE_LOCALE, {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function formatFee(fee) {
    const amount = Number(fee);

    if (!Number.isFinite(amount) || amount <= 0) {
        return 'Miễn phí';
    }

    return `${new Intl.NumberFormat(VIETNAMESE_LOCALE).format(amount)} ₫`;
}

export function formatBytes(bytes) {
    if (!Number.isFinite(bytes)) {
        return '';
    }

    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}