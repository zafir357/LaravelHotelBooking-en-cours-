<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Le rôle "admin" est supprimé : la réceptionniste a maintenant tous
        // les pouvoirs (réservations + chambres + invitation d'autres
        // réceptionnistes). On bascule donc d'abord les comptes existants
        // marqués "admin" vers "receptionist" — sinon, une fois l'enum
        // restreint à 'receptionist','guest' juste après, ces lignes
        // deviendraient invalides en base.
        DB::statement("UPDATE users SET role = 'receptionist' WHERE role = 'admin'");

        // ->change() plutôt qu'un DB::statement() en SQL brut : MySQL (en
        // production) et SQLite (utilisé par les tests, voir phpunit.xml)
        // n'acceptent pas la même syntaxe ALTER TABLE pour un enum — Laravel
        // génère la bonne syntaxe pour chacun à partir de cette seule ligne.
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['receptionist', 'guest'])->default('guest')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'receptionist', 'guest'])->default('guest')->change();
        });
    }
};
