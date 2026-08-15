import apiClient, { initializeCsrfProtection } from './client';

export async function registerCitizen(payload) {
    await initializeCsrfProtection();

    const { data } = await apiClient.post('/auth/register', payload);

    return data;
}

export async function loginCitizen(payload) {
    await initializeCsrfProtection();

    const { data } = await apiClient.post('/auth/login', payload);

    return data;
}

export async function logoutCitizen() {
    await initializeCsrfProtection();

    const { data } = await apiClient.post('/auth/logout');

    return data;
}

export function getApiError(error) {
    const response = error?.response?.data;

    return {
        message: response?.message ?? 'Có lỗi xảy ra. Vui lòng thử lại.',
        errors: response?.errors ?? {},
    };
}
