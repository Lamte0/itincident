<?php

namespace Database\Seeders;

use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;

class IncidentAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $chef = User::where('email', 'chef.maintenance@dgtcp.ci')->first();
        $tech1 = User::where('email', 'tech.reseau@dgtcp.ci')->first();
        $tech2 = User::where('email', 'tech.hardware@dgtcp.ci')->first();

        if (!$chef || !$tech1 || !$tech2) {
            return;
        }

        // Affectation pour incident clôturé 1
        IncidentAssignment::create([
            'incident_id' => 1,
            'maintenancier_id' => $tech1->id,
            'assigne_par_id' => $chef->id,
            'date_affectation' => now()->subDays(20)->setHour(10),
            'date_prise_en_charge' => now()->subDays(20)->setHour(10),
            'date_resolution' => now()->subDays(15)->setHour(14),
            'rapport_intervention' => 'Redémarrage du modem et reconfiguration réseau',
            'is_active' => false,
        ]);

        // Affectation pour incident clôturé 2
        IncidentAssignment::create([
            'incident_id' => 2,
            'maintenancier_id' => $tech2->id,
            'assigne_par_id' => $chef->id,
            'date_affectation' => now()->subDays(18)->setHour(9),
            'date_prise_en_charge' => now()->subDays(18)->setHour(9),
            'date_resolution' => now()->subDays(12)->setHour(11),
            'rapport_intervention' => 'Remplacement de l\'écran par modèle identique',
            'is_active' => false,
        ]);

        // Affectation pour incident EN_COURS 1
        IncidentAssignment::create([
            'incident_id' => 3,
            'maintenancier_id' => $tech2->id,
            'assigne_par_id' => $chef->id,
            'date_affectation' => now()->subDays(5)->setHour(10),
            'date_prise_en_charge' => now()->subDays(5)->setHour(10),
            'is_active' => true,
        ]);

        // Affectation pour incident EN_COURS 2
        IncidentAssignment::create([
            'incident_id' => 4,
            'maintenancier_id' => $tech1->id,
            'assigne_par_id' => $chef->id,
            'date_affectation' => now()->subDays(3)->setHour(9),
            'date_prise_en_charge' => now()->subDays(3)->setHour(9),
            'is_active' => true,
        ]);

        // Affectation pour incident AFFECTE
        IncidentAssignment::create([
            'incident_id' => 5,
            'maintenancier_id' => $tech1->id,
            'assigne_par_id' => $chef->id,
            'date_affectation' => now()->subHours(4),
            'is_active' => true,
        ]);

        $this->command->info('Incident assignments seeded successfully!');
    }
}
