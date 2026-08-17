import apiClient from './client';
import { rememberCitizenSession } from './auth';

export async function fetchCitizenProfile() {
    const { data } = await apiClient.get('/me');

    return data;
}

export async function updateCitizenProfile(payload) {
    const { data } = await apiClient.patch('/me', payload);

    rememberCitizenSession(data.data);

    return data;
}
