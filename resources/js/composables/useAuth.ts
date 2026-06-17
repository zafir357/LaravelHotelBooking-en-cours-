import { ref } from 'vue';
import api from '@/lib/api';

interface User {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'receptionist' | 'guest';
}

// `ref` partagé entre tous les composants qui utilisent useAuth
const user = ref<User | null>(null);

export function useAuth() {
    // Connexion
    async function login(email: string, password: string) {
        const { data } = await api.post('/login', { email, password });

        // Stocke le token pour les futures requêtes
        localStorage.setItem('auth_token', data.token);
        user.value = data.user;

        return data.user;
    }

    // Inscription
    async function register(name: string, email: string, password: string, passwordConfirmation: string) {
        const { data } = await api.post('/register', {
            name,
            email,
            password,
            password_confirmation: passwordConfirmation,
        });

        localStorage.setItem('auth_token', data.token);
        user.value = data.user;

        return data.user;
    }


    // Déconnexion
    async function logout() {
        await api.post('/logout');
        localStorage.removeItem('auth_token');
        user.value = null;
    }


    // Récupère le user connecté (au chargement de l'app par exemple)
    async function fetchUser() {
        const token = localStorage.getItem('auth_token');
        // Si pas de token, on ne fait pas la requête
        if (!token) return null;

        try {
            const { data } = await api.get('/me');
            user.value = data;
            return data;
        } catch {
            // Token invalide/expiré
            localStorage.removeItem('auth_token');
            user.value = null;
            return null;
        }
    }

    return { user, login, register, logout, fetchUser };
}
