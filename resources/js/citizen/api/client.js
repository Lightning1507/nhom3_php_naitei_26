import axios from 'axios';

const apiClient = axios.create({
    baseURL: '/api/v1',
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
    withXSRFToken: true,
});

export async function initializeCsrfProtection() {
    await axios.get('/sanctum/csrf-cookie', {
        withCredentials: true,
        withXSRFToken: true,
    });
}

export default apiClient;
