<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// register()/login()/logout() ont été retirés : ils dupliquaient ce que
// Fortify fait déjà via les routes de session classiques (POST /register,
// POST /login, POST /logout, web group). Avant, ces deux systèmes
// coexistaient sans se synchroniser — un utilisateur connecté via cette
// API recevait un token Sanctum mais aucune session Laravel, donc tout
// middleware "auth" côté web (ex: sur /dashboard) le traitait comme un
// invité. Maintenant useAuth.ts (frontend) appelle directement les routes
// Fortify, qui créent une vraie session — et grâce au mode stateful de
// Sanctum (bootstrap/app.php), cette même session authentifie aussi /api/*.
class AuthController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
