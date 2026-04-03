<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Met à jour les rôles des utilisateurs existants.
     *
     * - Les utilisateurs sans rôle reçoivent le rôle 'client'
     * - Le premier utilisateur devient 'admin' (s'il n'y a pas d'admin)
     */
    public function up(): void
    {
        // Mettre à jour les utilisateurs sans rôle
        DB::statement("UPDATE users SET role = 'client' WHERE role IS NULL OR role = ''");

        // S'assurer qu'il y a au moins un admin
        $adminExists = DB::table('users')->where('role', 'admin')->exists();

        if (!$adminExists) {
            // Le premier utilisateur devient admin
            $firstUser = DB::table('users')->orderBy('id')->first();
            if ($firstUser) {
                DB::table('users')
                    ->where('id', $firstUser->id)
                    ->update(['role' => 'admin']);
            }
        }

        // Mettre à jour tous les rôles invalides vers 'client'
        DB::statement("UPDATE users SET role = 'client' WHERE role NOT IN ('admin', 'employe', 'client')");
    }

    public function down(): void
    {
        // Rollback simple : on ne peut pas restaurer l'état exact
        // mais on peut s'assurer que tout le monde a un rôle valide
        DB::statement("UPDATE users SET role = 'employe' WHERE role IS NULL OR role = ''");
    }
};