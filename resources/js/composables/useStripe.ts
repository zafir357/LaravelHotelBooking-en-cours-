import { loadStripe, type Stripe } from '@stripe/stripe-js';

// loadStripe() télécharge le SDK Stripe.js depuis leurs serveurs et ne doit
// être appelé qu'une seule fois par page (pas à chaque ouverture de la
// modale de réservation) — on garde donc la Promise en mémoire au niveau du
// module (comme le "user" partagé de useAuth.ts) pour la réutiliser partout.
let stripePromise: Promise<Stripe | null> | null = null;

export function useStripe() {
    if (!stripePromise) {
        // VITE_STRIPE_KEY = la clé PUBLISHABLE (pk_test_...), sans danger à
        // exposer côté client — uniquement la clé secrète (STRIPE_SECRET,
        // utilisée côté Laravel) doit rester confidentielle.
        stripePromise = loadStripe(import.meta.env.VITE_STRIPE_KEY as string);
    }

    return stripePromise;
}
