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
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // INC-2026-0001
            $table->string('titre');
            $table->text('description');
            $table->enum('type', ['RESEAU', 'LOGICIEL', 'HARDWARE'])->default('LOGICIEL');
            $table->enum('priorite', ['BASSE', 'MOYENNE', 'HAUTE', 'CRITIQUE'])->default('MOYENNE');
            $table->enum('statut', [
                'OUVERT',
                'AFFECTE',
                'EN_COURS',
                'RESOLU',
                'EN_ATTENTE_VALIDATION',
                'CLOTURE'
            ])->default('OUVERT');
            $table->foreignId('auteur_id')->constrained('users')->onDelete('cascade');
            $table->text('lieu')->nullable(); // Localisation de l'incident (bureau, bâtiment)
            $table->text('equipement')->nullable(); // Équipement concerné
            $table->timestamp('date_resolution')->nullable();
            $table->timestamp('date_cloture')->nullable();
            $table->text('commentaire_resolution')->nullable();
            $table->unsignedTinyInteger('note')->nullable(); // Note sur 10
            $table->text('commentaire_validation')->nullable(); // Commentaire lors de la validation
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
