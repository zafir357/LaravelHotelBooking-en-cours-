<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

// Cette route est accessible SANS être connecté : le middleware "signed"
// (voir routes/web.php) est la seule protection — il vérifie que l'URL
// complète (avec sa query string ?signature=...&expires=...) correspond
// bien à celle générée par ReceptionistController::store(), et qu'elle n'a
// pas expiré. Pas de mot de passe à fournir pour ACCÉDER à cette page,
// puisque c'est justement cette page qui permet d'en définir un.
class SetPasswordController extends Controller
{
    use PasswordValidationRules;

    public function __invoke(Request $request, User $user): Response|RedirectResponse
    {
        if ($request->isMethod('get')) {
            return Inertia::render('auth/SetPassword', [
                'name' => $user->name,
            ]);
        }

        $validated = $request->validate([
            'password' => $this->passwordRules(),
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        // L'invitation prouvait déjà la possession de cette adresse email
        // (c'est sur cette adresse que le lien a été envoyé) — pas besoin de
        // lui faire revérifier son email en plus, contrairement au flow
        // d'inscription "guest" classique.
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Password set — you can now log in.']);

        return redirect('/login');
    }
}
