<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Middleware paramétrable : ->middleware('role:receptionist') autorise
// seulement les utilisateurs ayant l'un de ces rôles. Avant ce fichier, la
// seule façon de protéger une route par rôle était de vérifier $user->role
// à la main dans chaque contrôleur/FormRequest — ici la vérification se fait
// une seule fois, au niveau de la route, avant même d'atteindre le contrôleur.
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null || ! in_array($user->role, $roles, true)) {
            abort(403, 'You are not authorized to access this page.');
        }

        return $next($request);
    }
}
