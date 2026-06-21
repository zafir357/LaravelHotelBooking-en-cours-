# Hotel Booking — Maison Bellevue

Application de réservation d'hôtel construite avec **Laravel 13** (API + sessions Fortify/Sanctum) et **Vue 3 + Inertia.js + Vuetify** côté frontend, avec un vrai flow de **paiement Stripe** (mode test).

## Ce que fait l'application

- **Côté client (guest)** : parcourir les chambres avec filtres (type, prix, capacité, dates), réserver une chambre en payant via Stripe (carte bancaire), consulter ses réservations et les annuler (remboursement automatique).
- **Côté réceptionniste (receptionist)** : voir et annuler n'importe quelle réservation, créer/éditer/supprimer des chambres, inviter d'autres comptes receptionist (par email).
- **Paiement** : aucune réservation n'est créée en base avant que le paiement Stripe soit confirmé — le serveur revérifie systématiquement auprès de Stripe (montant + statut) avant d'enregistrer quoi que ce soit.
- Il n'y a que deux rôles : `guest` et `receptionist` (pas de rôle admin séparé — la réceptionniste a tous les pouvoirs de gestion).

## Prérequis

- PHP 8.4+
- Composer
- Node.js + npm
- Une base de données (MySQL/MariaDB/SQLite — voir `.env`)

## Installation

```bash
git clone <url-du-repo>
cd hotel-booking

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Configurer la base de données dans `.env` (par défaut SQLite, fonctionne sans rien configurer en plus). Si MySQL est préféré, renseigner `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.

### Stripe (paiement)

Créer un compte Stripe (gratuit) et récupérer les clés **de test** sur [dashboard.stripe.com/test/apikeys](https://dashboard.stripe.com/test/apikeys), puis les ajouter dans `.env` :

```env
STRIPE_SECRET=sk_test_...
VITE_STRIPE_KEY=pk_test_...
```

Sans ces clés, le reste de l'application fonctionne, mais la réservation/paiement échouera.

### Base de données

```bash
php artisan migrate --seed
```

Le seeder crée une chambre receptionist (`reception@hotel.test` / `password`) et un compte guest (`guest@hotel.test` / `password`), plus quelques chambres d'exemple. Email non requis à vérifier pour ces comptes seedés (déjà marqués vérifiés).

### Comptes de test rapides

Pour se connecter immédiatement sans passer par un email de vérification (utile après un `git clone`, car `storage/logs/laravel.log` n'est pas versionné) :

```bash
php artisan app:create-test-users
```

Crée deux comptes déjà vérifiés :

| Rôle | Email | Mot de passe |
|---|---|---|
| guest | `test_guest@hotel.test` | `password` |
| receptionist | `test_receptionist@hotel.test` | `password` |

### Frontend

```bash
npm run build
```

(ou `npm run dev` pendant le développement, voir plus bas)

## Lancer l'application

Si l'application est servie par **Laravel Herd**, rien à faire — elle est déjà accessible (juste lancer `npm run dev` pour le hot-reload du frontend).

Sinon, en développement, tout lancer en une commande (serveur PHP + queue + Vite) :

```bash
composer run dev
```

Ou manuellement, dans des terminaux séparés :

```bash
php artisan serve
npm run dev
```

L'application est alors disponible sur l'URL affichée par `php artisan serve` (par défaut `http://localhost:8000`).

## Tester le paiement

1. Se connecter avec un compte guest (`test_guest@hotel.test` / `password`).
2. Aller sur `/rooms`, choisir une chambre, cliquer "Book now".
3. Choisir des dates, "Continue to payment".
4. Utiliser une carte de test Stripe :
   - `4242 4242 4242 4242` — paiement réussi (date d'expiration future quelconque, CVC quelconque).
   - `4000 0000 0000 0002` — carte refusée (pour tester la gestion d'erreur).
5. Vérifier la réservation sur `/dashboard`.

En local, `MAIL_MAILER=log` : tout email (vérification, invitation receptionist) est écrit dans `storage/logs/laravel.log` au lieu d'être réellement envoyé.

## Commandes utiles

| Commande | Description |
|---|---|
| `composer run dev` | Lance serveur PHP + queue worker + Vite en parallèle |
| `npm run dev` | Lance Vite seul (hot-reload frontend) |
| `npm run build` | Build de production du frontend |
| `php artisan migrate:fresh --seed` | Réinitialise la base et reseed |
| `php artisan app:create-test-users` | Crée les deux comptes de test pré-vérifiés |
| `composer run lint` | Corrige le style de code PHP (Pint) |
| `composer run types:check` | Analyse statique PHP (PHPStan/Larastan) |
| `php artisan test --compact` | Lance la suite de tests Pest |
| `composer run ci:check` | Lint + types + tests (ce que la CI exécute) |
| `npm run lint` | Corrige le style de code JS/TS/Vue (ESLint) |
| `npm run format` | Formate le code frontend (Prettier) |

## Stack technique

- **Backend** : Laravel 13, Fortify (auth par session), Sanctum (mode stateful SPA), Stripe PHP SDK, Pest (tests), PHPStan/Larastan, Pint.
- **Frontend** : Vue 3 (Composition API), Inertia.js v3, Vuetify 3, TypeScript, Tailwind CSS, ESLint/Prettier.
- **Architecture backend** : Repository/Service (`app/Repositories`, `app/Services`), Policies pour l'autorisation, Form Requests pour la validation.
