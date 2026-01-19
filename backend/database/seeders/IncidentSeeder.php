<?php

namespace Database\Seeders;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Database\Seeder;

class IncidentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les utilisateurs
        $admin = User::where('email', 'admin@dgtcp.ci')->first();
        $chef = User::where('email', 'chef.maintenance@dgtcp.ci')->first();
        $technician1 = User::where('email', 'tech.reseau@dgtcp.ci')->first();
        $technician2 = User::where('email', 'tech.hardware@dgtcp.ci')->first();
        $user1 = User::where('email', 'jean.dupont@dgtcp.ci')->first();
        $user2 = User::where('email', 'marie.kouassi@dgtcp.ci')->first();

        if (!$admin || !$user1) {
            $this->command->warn('Seeder users first with: php artisan db:seed --class=UserSeeder');
            return;
        }

        // Incidents clôturés (ancien)
        Incident::create([
            'reference' => 'INC-2025-0001',
            'titre' => 'Connexion Internet lente',
            'description' => 'La connexion Internet du bureau 101 est très lente.',
            'type' => 'RESEAU',
            'priorite' => 'MOYENNE',
            'statut' => 'CLOTURE',
            'auteur_id' => $user1->id,
            'lieu' => 'Bureau 101',
            'equipement' => 'Modem WAN-01',
            'date_resolution' => now()->subDays(15)->setHour(14),
            'date_cloture' => now()->subDays(15)->setHour(15),
            'commentaire_resolution' => 'Redémarrage du modem et configuration réseau réajustée',
            'note' => 4,
            'commentaire_validation' => 'Incident résolu correctement',
            'created_at' => now()->subDays(20),
            'updated_at' => now()->subDays(15),
        ]);

        Incident::create([
            'reference' => 'INC-2025-0002',
            'titre' => 'Écran d\'affichage cassé',
            'description' => 'L\'écran du bureau 202 ne s\'allume plus.',
            'type' => 'HARDWARE',
            'priorite' => 'HAUTE',
            'statut' => 'CLOTURE',
            'auteur_id' => $user2->id,
            'lieu' => 'Bureau 202',
            'equipement' => 'Écran Dell UltraSharp',
            'date_resolution' => now()->subDays(12)->setHour(11),
            'date_cloture' => now()->subDays(12)->setHour(12),
            'commentaire_resolution' => 'Remplacement de l\'écran par un modèle identique',
            'note' => 5,
            'commentaire_validation' => 'Intervention rapide et efficace',
            'created_at' => now()->subDays(18),
            'updated_at' => now()->subDays(12),
        ]);

        // Incidents en cours
        Incident::create([
            'reference' => 'INC-2026-0001',
            'titre' => 'Logiciel de comptabilité bogué',
            'description' => 'Le logiciel de comptabilité refuse de générer les rapports mensuels.',
            'type' => 'LOGICIEL',
            'priorite' => 'CRITIQUE',
            'statut' => 'EN_COURS',
            'auteur_id' => $user1->id,
            'lieu' => 'Bureau Comptabilité',
            'equipement' => 'Serveur APP-01',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(1),
        ]);

        Incident::create([
            'reference' => 'INC-2026-0002',
            'titre' => 'Imprimante réseau hors service',
            'description' => 'L\'imprimante du 3ème étage est hors ligne depuis ce matin.',
            'type' => 'HARDWARE',
            'priorite' => 'MOYENNE',
            'statut' => 'EN_COURS',
            'auteur_id' => $user2->id,
            'lieu' => '3ème étage',
            'equipement' => 'Imprimante HP Color LaserJet',
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDay(),
        ]);

        // Incidents affectés
        Incident::create([
            'reference' => 'INC-2026-0003',
            'titre' => 'Problème d\'accès au serveur de fichiers',
            'description' => 'Impossible d\'accéder au serveur de fichiers depuis le poste 45.',
            'type' => 'RESEAU',
            'priorite' => 'HAUTE',
            'statut' => 'AFFECTE',
            'auteur_id' => $user1->id,
            'lieu' => 'Bureau 45',
            'equipement' => 'Poste de travail',
            'created_at' => now()->subHours(4),
            'updated_at' => now()->subHours(2),
        ]);

        // Incidents ouverts
        Incident::create([
            'reference' => 'INC-2026-0004',
            'titre' => 'Mise à jour Windows bloquée',
            'description' => 'La mise à jour de Windows 11 est restée bloquée à 50%.',
            'type' => 'LOGICIEL',
            'priorite' => 'MOYENNE',
            'statut' => 'OUVERT',
            'auteur_id' => $user2->id,
            'lieu' => 'Bureau 150',
            'equipement' => 'Poste de travail Dell',
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHour(),
        ]);

        Incident::create([
            'reference' => 'INC-2026-0005',
            'titre' => 'Accès VPN défaillant',
            'description' => 'Les connexions VPN se déconnectent régulièrement.',
            'type' => 'RESEAU',
            'priorite' => 'HAUTE',
            'statut' => 'OUVERT',
            'auteur_id' => $user1->id,
            'lieu' => 'Télétravail',
            'equipement' => 'VPN Client',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('Incidents seeded successfully!');
    }
}
