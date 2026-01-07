<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Incident>
 */
class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => Incident::generateReference(),
            'titre' => fake()->sentence(6),
            'description' => fake()->paragraph(3),
            'type' => fake()->randomElement(['RESEAU', 'LOGICIEL', 'HARDWARE']),
            'priorite' => fake()->randomElement(['BASSE', 'MOYENNE', 'HAUTE', 'CRITIQUE']),
            'statut' => 'OUVERT',
            'auteur_id' => User::factory(),
            'lieu' => fake()->randomElement(['Bureau 101', 'Salle serveur', 'Accueil', 'Direction']),
            'equipement' => 'PC-' . fake()->randomNumber(4, true),
        ];
    }

    /**
     * Indicate that the incident is open.
     */
    public function ouvert(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'OUVERT',
        ]);
    }

    /**
     * Indicate that the incident is assigned.
     */
    public function affecte(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'AFFECTE',
        ]);
    }

    /**
     * Indicate that the incident is in progress.
     */
    public function enCours(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'EN_COURS',
        ]);
    }

    /**
     * Indicate that the incident is resolved.
     */
    public function resolu(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'RESOLU',
            'date_resolution' => now(),
        ]);
    }

    /**
     * Indicate that the incident is closed.
     */
    public function cloture(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'CLOTURE',
            'date_resolution' => now()->subHours(2),
            'date_cloture' => now(),
            'note' => fake()->numberBetween(1, 5),
            'commentaire_validation' => fake()->sentence(),
        ]);
    }

    /**
     * Set the incident type to network.
     */
    public function reseau(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'RESEAU',
        ]);
    }

    /**
     * Set the incident type to software.
     */
    public function logiciel(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'LOGICIEL',
        ]);
    }

    /**
     * Set the incident type to hardware.
     */
    public function hardware(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'HARDWARE',
        ]);
    }

    /**
     * Set the incident priority to critical.
     */
    public function critique(): static
    {
        return $this->state(fn (array $attributes) => [
            'priorite' => 'CRITIQUE',
        ]);
    }
}
