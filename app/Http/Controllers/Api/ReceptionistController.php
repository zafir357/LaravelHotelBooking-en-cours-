<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\SetPasswordInvite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

// Réservé à un compte receptionist déjà existant (voir 'role:receptionist'
// dans routes/api.php) — il n'y a plus de rôle admin séparé, c'est donc la
// réceptionniste elle-même qui peut faire grandir l'équipe.
class ReceptionistController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')],
        ]);

        // Mot de passe aléatoire et inutilisable : personne ne le connaît,
        // ni nous ni l'invité — il sera remplacé dès que l'invité aura
        // cliqué sur le lien et choisi son propre mot de passe (voir
        // SetPasswordController). On ne peut pas laisser ce champ vide, la
        // colonne "password" est NOT NULL en base.
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(40)),
            'role' => 'receptionist',
        ]);

        // URL::temporarySignedRoute génère une URL contenant une signature
        // cryptographique (basée sur APP_KEY) + une date d'expiration, toutes
        // les deux dans la query string. Le middleware "signed" (routes/web.php)
        // recalcule cette même signature à partir de l'URL reçue : si elles ne
        // correspondent pas (URL modifiée) ou si la date est passée, il bloque
        // la requête avec un 403 — sans avoir besoin d'un mot de passe.
        $url = URL::temporarySignedRoute(
            'staff.set-password',
            now()->addHours(48),
            ['user' => $user->id],
        );

        $user->notify(new SetPasswordInvite($url));

        return response()->json([
            'message' => 'Invitation sent.',
        ], 201);
    }
}
