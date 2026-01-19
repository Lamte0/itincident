<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modifier l'enum pour inclure les anciens et nouveaux noms
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['UTILISATEUR', 'MAINTENANCIER', 'CHEF_SERVICE', 'ADMIN', 'AGENT', 'TECHNICIEN', 'SUPERVISEUR'])->default('AGENT')->change();
        });

        // Mettre à jour les valeurs des rôles
        DB::table('users')->where('role', 'UTILISATEUR')->update(['role' => 'AGENT']);
        DB::table('users')->where('role', 'MAINTENANCIER')->update(['role' => 'TECHNICIEN']);
        DB::table('users')->where('role', 'CHEF_SERVICE')->update(['role' => 'SUPERVISEUR']);

        // Modifier l'enum pour ne garder que les nouveaux noms
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['AGENT', 'TECHNICIEN', 'SUPERVISEUR', 'ADMIN'])->default('AGENT')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Modifier l'enum pour inclure les anciens noms
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['UTILISATEUR', 'MAINTENANCIER', 'CHEF_SERVICE', 'ADMIN', 'AGENT', 'TECHNICIEN', 'SUPERVISEUR'])->default('UTILISATEUR')->change();
        });

        // Remettre les anciennes valeurs
        DB::table('users')->where('role', 'AGENT')->update(['role' => 'UTILISATEUR']);
        DB::table('users')->where('role', 'TECHNICIEN')->update(['role' => 'MAINTENANCIER']);
        DB::table('users')->where('role', 'SUPERVISEUR')->update(['role' => 'CHEF_SERVICE']);

        // Remettre l'ancien enum
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['UTILISATEUR', 'MAINTENANCIER', 'CHEF_SERVICE', 'ADMIN'])->default('UTILISATEUR')->change();
        });
    }
};
