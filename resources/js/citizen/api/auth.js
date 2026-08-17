import apiClient, { initializeCsrfProtection } from './client';

const citizenSessionKey = 'citizen.auth.user';

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

export async function getPendingGoogleCitizen() {
    const { data } = await apiClient.get('/auth/google/pending');

    return data;
}

export async function completeGoogleCitizenRegistration(payload) {
    await initializeCsrfProtection();

    const { data } = await apiClient.post('/auth/google/complete', payload);

    return data;
}

export function rememberCitizenSession(user) {
    localStorage.setItem(citizenSessionKey, JSON.stringify(user));
}

export function getRememberedCitizen() {
    const storedUser = localStorage.getItem(citizenSessionKey);

    if (!storedUser) {
        return null;
    }

    try {
        return JSON.parse(storedUser);
    } catch {
        localStorage.removeItem(citizenSessionKey);

        return null;
    }
}

export function forgetCitizenSession() {
    localStorage.removeItem(citizenSessionKey);
}

export function getApiError(error) {
    const response = error?.response?.data;

    return {
        message: response?.message ?? 'Có lỗi xảy ra. Vui lòng thử lại.',
        errors: response?.errors ?? {},
    };
}
