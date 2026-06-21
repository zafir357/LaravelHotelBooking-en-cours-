import axios from 'axios';
import { ref } from 'vue';
import api from '@/lib/api';

// Avant : login()/register() appelaient /api/login et /api/register (un
// système maison qui ne créait qu'un token, jamais de session Laravel) —
// d'où le bug "je suis connecté mais /dashboard me renvoie au login" : le
// middleware "auth" côté serveur ne voyait jamais cette connexion.
//
// Maintenant : on appelle directement les routes de SESSION de Fortify
// (POST /login, POST /register, POST /logout — déjà enregistrées
// automatiquement par Fortify, mêmes routes que toute appli Laravel
// classique). Ces routes passent par le groupe 'web', qui crée un vrai
// cookie de session. Comme le mode stateful de Sanctum est activé
// (bootstrap/app.php), ce même cookie authentifie aussi les appels /api/*
// (via l'instance "api" de lib/api.ts, qui envoie le cookie grâce à
// withCredentials: true) — un seul système de connexion, plus de token.
//
// On utilise axios "brut" (pas l'instance "api") car ces routes Fortify
// vivent à la racine (/login), pas sous /api comme le reste de notre API.
axios.defaults.withCredentials = true;

interface User {
    id: number;
    name: string;
    email: string;
    role: 'receptionist' | 'guest';
}

// `ref` partagé entre tous les composants qui utilisent useAuth
const user = ref<User | null>(null);

export function useAuth() {
    // Connexion. Fortify ne renvoie pas l'utilisateur dans la réponse de
    // /login (juste un succès) — on va donc le chercher juste après via
    // fetchUser(), qui lit la session fraîchement créée.
    async function login(email: string, password: string) {
        await axios.post('/login', { email, password });

        return fetchUser();
    }

    // Inscription — même principe : Fortify crée le compte + la session
    // (voir app/Actions/Fortify/CreateNewUser.php), on récupère ensuite
    // l'utilisateur via fetchUser().
    async function register(name: string, email: string, password: string, passwordConfirmation: string) {
        await axios.post('/register', {
            name,
            email,
            password,
            password_confirmation: passwordConfirmation,
        });

        return fetchUser();
    }

    // Déconnexion — invalide la session côté serveur (route Fortify).
    async function logout() {
        await axios.post('/logout');
        user.value = null;
    }

    // Récupère le user connecté à partir de la session actuelle (pas de
    // token à vérifier nous-mêmes : si la session est valide, /api/me
    // répond 200 ; sinon 401, intercepté ci-dessous).
    async function fetchUser() {
        try {
            const { data } = await api.get('/me');
            user.value = data;

            return data;
        } catch {
            user.value = null;

            return null;
        }
    }

    return { user, login, register, logout, fetchUser };
}
