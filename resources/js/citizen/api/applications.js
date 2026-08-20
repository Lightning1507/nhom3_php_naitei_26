import apiClient, { initializeCsrfProtection } from './client';

const REQUIREMENT_CODE_FIELD = 'requirement_code';

export async function createApplication(serviceTypeId, formData) {
    await initializeCsrfProtection();

    const { data } = await apiClient.post('/applications', {
        service_type_id: serviceTypeId,
        form_data: formData,
    });

    return data;
}

export async function fetchApplications(params = {}) {
    const { data } = await apiClient.get('/applications', { params });

    return data;
}

export async function fetchApplication(id) {
    const { data } = await apiClient.get(`/applications/${id}`);

    return data;
}

export async function uploadApplicationDocument(applicationId, file, requirementCode) {
    await initializeCsrfProtection();

    const payload = new FormData();
    payload.append('document', file);

    if (requirementCode) {
        payload.append(REQUIREMENT_CODE_FIELD, requirementCode);
    }

    const { data } = await apiClient.post(`/applications/${applicationId}/documents`, payload);

    return data;
}

export async function deleteApplicationDocument(applicationId, documentId) {
    await initializeCsrfProtection();

    const { data } = await apiClient.delete(`/applications/${applicationId}/documents/${documentId}`);

    return data;
}

export async function downloadApplicationDocument(applicationId, documentId, fileName) {
    const response = await apiClient.get(`/applications/${applicationId}/documents/${documentId}`, {
        responseType: 'blob',
    });

    const blobUrl = window.URL.createObjectURL(response.data);
    const link = document.createElement('a');
    link.href = blobUrl;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(blobUrl);
}