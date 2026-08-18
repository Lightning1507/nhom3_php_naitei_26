import apiClient from './client';

export function fetchServices(params = {}) {
    return apiClient.get('/services', { params });
}

export function fetchService(id) {
    return apiClient.get(`/services/${id}`);
}

export function fetchCategories() {
    return apiClient.get('/services/categories');
}
