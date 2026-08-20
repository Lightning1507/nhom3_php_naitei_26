function normalizeType(type) {
    switch (type) {
        case 'number':
            return 'number';
        case 'date':
            return 'date';
        case 'boolean':
        case 'checkbox':
            return 'boolean';
        default:
            return 'text';
    }
}

export function normalizeSchemaFields(service) {
    const raw = Array.isArray(service?.form_schema) ? service.form_schema : [];

    return raw
        .filter((field) => field && typeof field.name === 'string' && field.name !== '')
        .filter((field) => (field.type ?? 'text') !== 'file')
        .map((field) => ({
            name: field.name,
            label: field.label || field.name,
            type: normalizeType(field.type),
            required: Boolean(field.required || field.is_required),
        }));
}

function slugify(value) {
    return String(value ?? '')
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '') || 'giay-to';
}

export function normalizeDocumentRequirements(service) {
    const raw = Array.isArray(service?.document_requirements) ? service.document_requirements : [];

    return raw
        .filter((item) => {
            if (typeof item === 'string') {
                return item !== '';
            }

            const label = item?.label ?? item?.name;

            return typeof label === 'string' && label !== '';
        })
        .map((item) => {
            if (typeof item === 'string') {
                return { code: slugify(item), label: item, required: false, type: 'mixed' };
            }

            const label = item.label ?? item.name;

            return {
                code: typeof item.code === 'string' && item.code !== '' ? item.code : slugify(label),
                label,
                required: Boolean(item.required ?? item.is_required),
                type: ['pdf', 'image', 'mixed'].includes(item.type) ? item.type : 'mixed',
            };
        });
}

export function requirementAccept(requirement) {
    switch (requirement?.type) {
        case 'pdf':
            return '.pdf,application/pdf';
        case 'image':
            return '.jpg,.jpeg,.png,image/jpeg,image/png';
        default:
            return '.pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png';
    }
}