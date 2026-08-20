const STATUS_CONFIG = {
    received: { label: 'Đã tiếp nhận', tone: 'c-neutral' },
    processing: { label: 'Đang xử lý', tone: 'c-info' },
    supplement_required: { label: 'Cần bổ sung', tone: 'c-warning' },
    approved: { label: 'Đã duyệt', tone: 'c-success' },
    rejected: { label: 'Bị từ chối', tone: 'c-danger' },
};

export function statusLabel(status) {
    return STATUS_CONFIG[status]?.label ?? status;
}

export default function StatusBadge({ status }) {
    const config = STATUS_CONFIG[status] ?? { tone: 'c-neutral' };

    return (
        <span className={`capsule-lg ${config.tone}`}>
            {STATUS_CONFIG[status]?.label ?? status}
        </span>
    );
}