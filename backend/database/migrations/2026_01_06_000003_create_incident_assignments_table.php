<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('incident_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents')->onDelete('cascade');
            $table->foreignId('maintenancier_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigne_par_id')->constrained('users')->onDelete('cascade'); // Chef service
            $table->text('instructions')->nullable(); // Instructions pour le maintenancier
            $table->timestamp('date_affectation');
            $table->timestamp('date_prise_en_charge')->nullable();
            $table->timestamp('date_resolution')->nullable();
            $table->text('rapport_intervention')->nullable();
            $table->boolean('is_active')->default(true); // Pour gérer les réaffectations
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_assignments');
    }
};
