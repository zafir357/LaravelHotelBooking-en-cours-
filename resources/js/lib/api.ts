import axios from 'axios';

// Crée une instance axios dédiée à notre API
const api = axios.create({
    baseURL: '/api',
});

// Intercepteur : avant chaque requête, ajoute le token s'il existe
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});

export default api;
