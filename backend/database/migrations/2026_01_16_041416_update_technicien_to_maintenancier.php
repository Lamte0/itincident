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
        // Modifier l'enum pour inclure MAINTENANCIER et TECHNICIEN
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['AGENT', 'TECHNICIEN', 'MAINTENANCIER', 'SUPERVISEUR', 'ADMIN'])->default('AGENT')->change();
        });

        // Mettre à jour les valeurs des rôles
        DB::table('users')->where('role', 'TECHNICIEN')->update(['role' => 'MAINTENANCIER']);

        // Modifier l'enum pour ne garder que les nouveaux noms
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['AGENT', 'MAINTENANCIER', 'SUPERVISEUR', 'ADMIN'])->default('AGENT')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Modifier l'enum pour inclure TECHNICIEN
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['AGENT', 'TECHNICIEN', 'MAINTENANCIER', 'SUPERVISEUR', 'ADMIN'])->default('AGENT')->change();
        });

        // Remettre les anciennes valeurs
        DB::table('users')->where('role', 'MAINTENANCIER')->update(['role' => 'TECHNICIEN']);

        // Remettre l'ancien enum
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['AGENT', 'TECHNICIEN', 'SUPERVISEUR', 'ADMIN'])->default('AGENT')->change();
        });
    }
};
