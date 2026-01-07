<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IncidentAssignment>
 */
class IncidentAssignmentFactory extends Factory
{
    protected $model = IncidentAssignment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'incident_id' => Incident::factory(),
            'maintenancier_id' => User::factory()->maintenancier(),
            'assigne_par_id' => User::factory()->chefService(),
            'instructions' => fake()->sentence(10),
            'is_active' => true,
            'date_affectation' => now(),
        ];
    }

    /**
     * Indicate that the assignment is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the assignment is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
