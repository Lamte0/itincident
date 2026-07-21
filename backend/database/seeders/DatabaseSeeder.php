<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
        ]);

        $maintenanciers = \App\Models\User::where('role', 'MAINTENANCIER')->get();
        $chef = \App\Models\User::where('role', 'CHEF_SERVICE')->first();
        $standards = \App\Models\User::where('role', 'UTILISATEUR')->get();

        // Seed Open Incidents
        foreach ($standards as $user) {
            \App\Models\Incident::factory()->ouvert()->create([
                'auteur_id' => $user->id,
                'titre' => 'Problème de connexion réseau au ' . $user->service,
                'type' => 'RESEAU',
                'priorite' => 'MOYENNE',
            ]);
        }

        // Seed In-Progress Incidents
        foreach ($maintenanciers as $m) {
            $incident = \App\Models\Incident::factory()->enCours()->create([
                'auteur_id' => $standards->random()->id,
                'titre' => 'Panne matériel sur le PC de ' . $m->name,
                'type' => 'HARDWARE',
                'priorite' => 'HAUTE',
            ]);
            \App\Models\IncidentAssignment::create([
                'incident_id' => $incident->id,
                'maintenancier_id' => $m->id,
                'assigne_par_id' => $chef->id,
                'instructions' => 'Veuillez vérifier le disque dur et la RAM de ce poste.',
                'is_active' => true,
                'date_affectation' => now()->subDays(2),
                'date_prise_en_charge' => now()->subDays(1),
            ]);
        }

        // Seed Closed Incidents
        for ($i = 0; $i < 5; $i++) {
            $m = $maintenanciers->random();
            $u = $standards->random();
            $incident = \App\Models\Incident::factory()->cloture()->create([
                'auteur_id' => $u->id,
                'titre' => 'Mise à jour logiciel ' . ($i + 1),
                'type' => 'LOGICIEL',
                'priorite' => 'BASSE',
            ]);
            \App\Models\IncidentAssignment::create([
                'incident_id' => $incident->id,
                'maintenancier_id' => $m->id,
                'assigne_par_id' => $chef->id,
                'instructions' => 'Faire la mise à jour de l\'application comptable.',
                'is_active' => true,
                'date_affectation' => now()->subDays(5),
                'date_prise_en_charge' => now()->subDays(4),
                'rapport_intervention' => 'Mise à jour effectuée avec succès. Test fonctionnel OK.',
            ]);
        }
    }
}
