# Instructions pour Claude — Projet Hotel Booking

## Règles obligatoires pour TOUT le code donné

1. **Commenter chaque bloc de code** — expliquer CE QUE fait le code,
   pas juste répéter le nom de la variable/fonction.

2. **Expliquer le POURQUOI**, pas juste le QUOI :
   - Mauvais commentaire : `// crée le token`
   - Bon commentaire : `// génère un token Sanctum lié à ce user,
     stocké hashé en base, retourné en clair une seule fois`

3. **Avant tout nouveau concept** (Observer, Event, Policy, Sanctum, etc.) :
   - Expliquer le PROBLÈME que ça résout
   - Donner le flow complet avec code à chaque étape
   - Ne jamais supposer que je connais déjà le concept

4. **Pas de jargon sans définition** — si un terme technique est utilisé
   (middleware, trait, cast, resource, etc.), l'expliquer en une phrase
   simple la première fois.

5. **Niveau** : je viens de Laravel/Livewire/Alpine/Tailwind.
   Je découvre : Vue 3, Inertia, TypeScript, Vuetify, Sanctum API,
   Clean Architecture (repositories/services), Observers, Events/Listeners.
   Utiliser des comparaisons avec ce que je connais (Laravel classique)
   quand c'est utile.

6. **Un seul fichier à la fois** quand on construit quelque chose de complexe —
   ne pas mélanger plusieurs fichiers dans la même réponse sans dire
   clairement "fichier X, puis fichier Y".

7. Si une erreur apparaît, **expliquer la cause avant la solution** —
   pas juste donner le fix.
