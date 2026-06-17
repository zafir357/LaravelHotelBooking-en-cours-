import axios from 'axios';

// Crée une instance axios dédiée à notre API.
// withCredentials: true = envoie le cookie de session Laravel (et le cookie
// XSRF-TOKEN) avec chaque requête. Avant, l'authentification passait par un
// token Bearer stocké dans localStorage (intercepteur retiré ci-dessous) —
// maintenant que le login passe par la session Fortify (voir useAuth.ts) et
// que le mode stateful de Sanctum est actif (bootstrap/app.php), c'est le
// cookie de session qui authentifie ces appels /api/*, plus de token à gérer.
const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
});

export default api;
