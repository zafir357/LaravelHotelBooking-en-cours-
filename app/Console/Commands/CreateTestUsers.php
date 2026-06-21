<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

// Quand quelqu'un clone ce repo, il n'a évidemment pas accès à
// storage/logs/laravel.log généré sur MA machine (le fichier n'est pas
// versionné) — donc le lien de vérification d'email envoyé à l'inscription
// (et le lien d'invitation receptionist, voir ReceptionistController) n'est
// jamais visible pour lui. Cette commande crée directement deux comptes
// utilisables, déjà marqués "email vérifié", pour pouvoir se connecter et
// tester l'application sans passer par ces flows.
#[Signature('app:create-test-users')]
#[Description('Crée (ou réinitialise) deux comptes de test déjà vérifiés : test_guest et test_receptionist.')]
class CreateTestUsers extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $accounts = [
            ['name' => 'Test Guest', 'email' => 'test_guest@hotel.test', 'role' => 'guest'],
            ['name' => 'Test Receptionist', 'email' => 'test_receptionist@hotel.test', 'role' => 'receptionist'],
        ];

        foreach ($accounts as $account) {
            // updateOrCreate plutôt que create() : relancer cette commande
            // plusieurs fois (ex: après un nouveau "migrate:fresh") ne doit
            // pas planter sur une contrainte d'unicité email, juste
            // réinitialiser le mot de passe/rôle au cas où ils auraient changé.
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $this->info("{$account['role']} -> {$account['email']} / password");
        }
    }
}
