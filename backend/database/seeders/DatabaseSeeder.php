<?php

namespace Database\Seeders;

use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\IncidentStatusHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

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

        $admin = User::where('role', 'ADMIN')->first();
        $chef = User::where('role', 'CHEF_SERVICE')->first();
        $techReseau = User::where('email', 'tech.reseau@dgtcp.ci')->first();
        $techHardware = User::where('email', 'tech.hardware@dgtcp.ci')->first();
        $techLogiciel = User::where('email', 'tech.logiciel@dgtcp.ci')->first();

        $jean = User::where('email', 'jean.dupont@dgtcp.ci')->first();
        $marie = User::where('email', 'marie.kouassi@dgtcp.ci')->first();
        $pierre = User::where('email', 'pierre.konan@dgtcp.ci')->first();

        $users = [$jean, $marie, $pierre];
        $techs = [$techReseau, $techHardware, $techLogiciel];

        // 1. Incidents OUVERTS (non affectés) - 8 incidents
        $openData = [
            [
                'titre' => 'Coupure de connexion réseau au Service Comptabilité',
                'description' => 'Impossible d’accéder au serveur central de la comptabilité depuis ce matin. Le voyant du switch mural clignote en rouge.',
                'type' => 'RESEAU',
                'priorite' => 'HAUTE',
                'lieu' => 'Bureau 102 - Comptabilité',
                'equipement' => 'Switch Cisco 2960',
                'auteur_id' => $jean->id,
                'created_at' => Carbon::now()->subHours(2),
            ],
            [
                'titre' => 'Imprimante réseau bloquée et non détectée',
                'description' => 'L\'imprimante du bureau Budget n\'imprime plus les états mensuels. Message d\'erreur d\'adresse IP.',
                'type' => 'HARDWARE',
                'priorite' => 'MOYENNE',
                'lieu' => 'Service Budget - 2ème étage',
                'equipement' => 'Imprimante HP LaserJet Pro M404',
                'auteur_id' => $marie->id,
                'created_at' => Carbon::now()->subHours(4),
            ],
            [
                'titre' => 'Erreur de connexion à l\'application Trésor Pay',
                'description' => 'Un message de timeout s\'affiche lors de la validation des ordres de virement sur le poste Trésorerie.',
                'type' => 'LOGICIEL',
                'priorite' => 'CRITIQUE',
                'lieu' => 'Service Trésorerie - Guichet 1',
                'equipement' => 'Poste Trésorerie PC-899',
                'auteur_id' => $pierre->id,
                'created_at' => Carbon::now()->subHours(1),
            ],
            [
                'titre' => 'Ecran bleu au démarrage du poste de travail',
                'description' => 'Le PC affiche un écran bleu d\'erreur (BSOD) immédiatement après le logo Windows.',
                'type' => 'HARDWARE',
                'priorite' => 'HAUTE',
                'lieu' => 'Bureau 205 - Comptabilité',
                'equipement' => 'Dell OptiPlex 7080',
                'auteur_id' => $jean->id,
                'created_at' => Carbon::now()->subHours(6),
            ],
            [
                'titre' => 'Lenteur excessive du réseau Wi-Fi Direction',
                'description' => 'Connexion très instable lors des réunions en visioconférence dans la salle de conférence.',
                'type' => 'RESEAU',
                'priorite' => 'BASSE',
                'lieu' => 'Grande Salle de Réunion',
                'equipement' => 'Point d\'accès Aruba AP-505',
                'auteur_id' => $marie->id,
                'created_at' => Carbon::now()->subHours(12),
            ],
            [
                'titre' => 'Problème de licence Microsoft Excel',
                'description' => 'Message d\'avertissement indiquant que la licence expire dans 2 jours sur l\'ordinateur du budget.',
                'type' => 'LOGICIEL',
                'priorite' => 'BASSE',
                'lieu' => 'Service Budget',
                'equipement' => 'PC-703',
                'auteur_id' => $marie->id,
                'created_at' => Carbon::now()->subHours(8),
            ],
            [
                'titre' => 'Le scanner à chèques ne répond plus',
                'description' => 'Impossible de numériser les chèques du jour au service caisse.',
                'type' => 'HARDWARE',
                'priorite' => 'HAUTE',
                'lieu' => 'Service Caisses - Guichet 3',
                'equipement' => 'Scanner Digital Check TS240',
                'auteur_id' => $pierre->id,
                'created_at' => Carbon::now()->subMinutes(45),
            ],
            [
                'titre' => 'Fichier partagé inaccessible sur le NAS',
                'description' => 'Le dossier "Rapports_2026" renvoie une erreur d\'autorisation insuffisante.',
                'type' => 'LOGICIEL',
                'priorite' => 'MOYENNE',
                'lieu' => 'Direction Informatique',
                'equipement' => 'Serveur NAS Synology',
                'auteur_id' => $jean->id,
                'created_at' => Carbon::now()->subMinutes(15),
            ],
        ];

        foreach ($openData as $data) {
            $incident = Incident::create(array_merge($data, ['statut' => 'OUVERT']));
            IncidentStatusHistory::create([
                'incident_id' => $incident->id,
                'ancien_statut' => null,
                'nouveau_statut' => 'OUVERT',
                'modifie_par_id' => $data['auteur_id'],
                'commentaire' => 'Incident déclaré via le portail.',
                'created_at' => $data['created_at'],
            ]);
        }

        // 2. Incidents AFFECTES & EN_COURS - 6 incidents
        $inProgressData = [
            [
                'titre' => 'Panne du serveur d\'impression du 1er étage',
                'description' => 'Tous les travaux d\'impression restent en file d\'attente sans s\'imprimer.',
                'type' => 'RESEAU',
                'priorite' => 'HAUTE',
                'lieu' => 'Local Serveur 1er étage',
                'equipement' => 'PrintServer HP',
                'auteur_id' => $jean->id,
                'tech' => $techReseau,
                'instructions' => 'Vérifier la pile d\'impression et redémarrer le service de spouleur.',
                'statut' => 'EN_COURS',
                'created_at' => Carbon::now()->subDays(1),
            ],
            [
                'titre' => 'Clavier et souris sans fil non reconnus',
                'description' => 'Le récepteur USB du poste comptable ne répond plus malgré le changement des piles.',
                'type' => 'HARDWARE',
                'priorite' => 'BASSE',
                'lieu' => 'Bureau 104 - Comptabilité',
                'equipement' => 'Kit Logitech MK270',
                'auteur_id' => $jean->id,
                'tech' => $techHardware,
                'instructions' => 'Fournir un nouveau kit clavier/souris filaire.',
                'statut' => 'AFFECTE',
                'created_at' => Carbon::now()->subHours(18),
            ],
            [
                'titre' => 'Bug lors de la génération du bilan annuel PDF',
                'description' => 'Le script PHP s\'arrête avec une erreur mémoire lors de l\'export des statistiques.',
                'type' => 'LOGICIEL',
                'priorite' => 'HAUTE',
                'lieu' => 'Service Budget',
                'equipement' => 'Application Métier',
                'auteur_id' => $marie->id,
                'tech' => $techLogiciel,
                'instructions' => 'Analyser les logs d\'erreur PHP et optimiser la mémoire attribuée à DomPDF.',
                'statut' => 'EN_COURS',
                'created_at' => Carbon::now()->subDays(2),
            ],
            [
                'titre' => 'Disque dur externe non monté sur le poste Trésorerie',
                'description' => 'Le disque de sauvegarde hebdomadaire n\'apparaît pas dans le poste de travail.',
                'type' => 'HARDWARE',
                'priorite' => 'MOYENNE',
                'lieu' => 'Service Trésorerie',
                'equipement' => 'Disque WD MyPassport 2TB',
                'auteur_id' => $pierre->id,
                'tech' => $techHardware,
                'instructions' => 'Tester le câble USB3 et la table de partition du disque.',
                'statut' => 'EN_COURS',
                'created_at' => Carbon::now()->subDays(1)->subHours(5),
            ],
            [
                'titre' => 'Problème de certificat SSL sur l\'intranet',
                'description' => 'Navigateur affiche un avertissement de sécurité "Connexion non sécurisée".',
                'type' => 'RESEAU',
                'priorite' => 'CRITIQUE',
                'lieu' => 'Direction Générale',
                'equipement' => 'Serveur Web Nginx',
                'auteur_id' => $marie->id,
                'tech' => $techReseau,
                'instructions' => 'Renouveler le certificat wildcard dgtcp.ci et réinstaller sur Nginx.',
                'statut' => 'AFFECTE',
                'created_at' => Carbon::now()->subHours(10),
            ],
            [
                'titre' => 'Mise à niveau Antivirus Kaspersky nécessaire',
                'description' => 'La base de définitions de virus date de plus de 15 jours sur 3 postes.',
                'type' => 'LOGICIEL',
                'priorite' => 'MOYENNE',
                'lieu' => 'Service Comptabilité',
                'equipement' => 'Postes USR001, USR002',
                'auteur_id' => $jean->id,
                'tech' => $techLogiciel,
                'instructions' => 'Forcer la mise à jour via la console d\'administration Kaspersky.',
                'statut' => 'EN_COURS',
                'created_at' => Carbon::now()->subDays(3),
            ],
        ];

        foreach ($inProgressData as $data) {
            $incident = Incident::create([
                'titre' => $data['titre'],
                'description' => $data['description'],
                'type' => $data['type'],
                'priorite' => $data['priorite'],
                'statut' => $data['statut'],
                'lieu' => $data['lieu'],
                'equipement' => $data['equipement'],
                'auteur_id' => $data['auteur_id'],
                'created_at' => $data['created_at'],
            ]);

            IncidentAssignment::create([
                'incident_id' => $incident->id,
                'maintenancier_id' => $data['tech']->id,
                'assigne_par_id' => $chef->id,
                'instructions' => $data['instructions'],
                'is_active' => true,
                'date_affectation' => $data['created_at']->addMinutes(30),
                'date_prise_en_charge' => $data['statut'] === 'EN_COURS' ? $data['created_at']->addHour() : null,
                'created_at' => $data['created_at']->addMinutes(30),
            ]);

            IncidentStatusHistory::create([
                'incident_id' => $incident->id,
                'ancien_statut' => 'OUVERT',
                'nouveau_statut' => 'AFFECTE',
                'modifie_par_id' => $chef->id,
                'commentaire' => 'Incident affecté à ' . $data['tech']->name,
                'created_at' => $data['created_at']->addMinutes(30),
            ]);
        }

        // 3. Incidents RESOLUS & CLOTURES - 10 incidents
        $closedData = [
            [
                'titre' => 'Remplacement de la carte réseau défectueuse',
                'description' => 'La carte réseau intégrée de la carte mère ne synchronisait plus qu\'en 10Mbps.',
                'type' => 'HARDWARE',
                'priorite' => 'HAUTE',
                'lieu' => 'Bureau 101',
                'equipement' => 'PC Lenovo ThinkCentre',
                'auteur' => $jean,
                'tech' => $techHardware,
                'rapport' => 'Carte PCI-Express Gigabit installée et testée à 1000Mbps. Connexion rétablie.',
                'note' => 5,
                'created_at' => Carbon::now()->subDays(7),
            ],
            [
                'titre' => 'Reconfiguration du compte de messagerie Outlook',
                'description' => 'Impossible de recevoir les e-mails externes depuis le changement de mot de passe AD.',
                'type' => 'LOGICIEL',
                'priorite' => 'MOYENNE',
                'lieu' => 'Service Budget',
                'equipement' => 'Microsoft Outlook 2021',
                'auteur' => $marie,
                'tech' => $techLogiciel,
                'rapport' => 'Profil Outlook recréé avec les nouveaux identifiants Exchange. Synchronisation OK.',
                'note' => 4,
                'created_at' => Carbon::now()->subDays(6),
            ],
            [
                'titre' => 'Remise en état du câble réseau RJ45 dénudé',
                'description' => 'Faux contact sur la prise murale du poste de travail.',
                'type' => 'RESEAU',
                'priorite' => 'BASSE',
                'lieu' => 'Guichet Trésorerie',
                'equipement' => 'Câble Categorie 6',
                'auteur' => $pierre,
                'tech' => $techReseau,
                'rapport' => 'Connecteur RJ45 serti à nouveau et testé au réflectomètre.',
                'note' => 5,
                'created_at' => Carbon::now()->subDays(8),
            ],
            [
                'titre' => 'Remplacement bloc d\'alimentation PC de bureau',
                'description' => 'L\'ordinateur ne s\'allumait plus du tout. Odeur de composant brûlé.',
                'type' => 'HARDWARE',
                'priorite' => 'CRITIQUE',
                'lieu' => 'Comptabilité',
                'equipement' => 'Alimentation Corsair 550W',
                'auteur' => $jean,
                'tech' => $techHardware,
                'rapport' => 'Bloc d\'alimentation défectueux remplacé sous garantie. Tests de charge effectués.',
                'note' => 5,
                'created_at' => Carbon::now()->subDays(10),
            ],
            [
                'titre' => 'Installation pilote du nouveau scanner documentaire',
                'description' => 'Besoin d\'installer le pilote TWAIN sur le poste de la secrétaire.',
                'type' => 'LOGICIEL',
                'priorite' => 'BASSE',
                'lieu' => 'Secrétariat Général',
                'equipement' => 'Scanner Fujitsu fi-7160',
                'auteur' => $marie,
                'tech' => $techLogiciel,
                'rapport' => 'Pilote TWAIN 64-bit installé et paramétré sur l\'application GED.',
                'note' => 4,
                'created_at' => Carbon::now()->subDays(12),
            ],
            [
                'titre' => 'Résolution du conflit d\'adresses IP sur le sous-réseau',
                'description' => 'Deux postes affichaient "Une autre machine utilise la même adresse IP".',
                'type' => 'RESEAU',
                'priorite' => 'HAUTE',
                'lieu' => 'Service Budget & Comptabilité',
                'equipement' => 'Serveur DHCP Windows Server',
                'auteur' => $pierre,
                'tech' => $techReseau,
                'rapport' => 'Bail DHCP obsolète supprimé sur le serveur DHCP et renouvellement forcé.',
                'note' => 5,
                'created_at' => Carbon::now()->subDays(14),
            ],
            [
                'titre' => 'Nettoyage des fichiers temporaires et optimisation du SSD',
                'description' => 'Message "Espace disque insuffisant sur le lecteur C:".',
                'type' => 'LOGICIEL',
                'priorite' => 'MOYENNE',
                'lieu' => 'Service Trésorerie',
                'equipement' => 'SSD NVMe 256GB',
                'auteur' => $pierre,
                'tech' => $techLogiciel,
                'rapport' => '45 Go de fichiers temporaires et d\'anciens téléchargements purgés.',
                'note' => 4,
                'created_at' => Carbon::now()->subDays(15),
            ],
            [
                'titre' => 'Changement d\'onduleur pour la baie informatique',
                'description' => 'L\'ancien onduleur émettait des bips continus suite à une baisse de tension.',
                'type' => 'HARDWARE',
                'priorite' => 'HAUTE',
                'lieu' => 'Local Technique',
                'equipement' => 'Onduleur Eaton 1500VA',
                'auteur' => $jean,
                'tech' => $techHardware,
                'rapport' => 'Batterie d\'onduleur remplacée. Test de basculement sur batterie OK.',
                'note' => 5,
                'created_at' => Carbon::now()->subDays(20),
            ],
        ];

        foreach ($closedData as $data) {
            $createdDate = $data['created_at'];
            $resolvedDate = $createdDate->copy()->addHours(3);
            $closedDate = $createdDate->copy()->addHours(5);

            $incident = Incident::create([
                'titre' => $data['titre'],
                'description' => $data['description'],
                'type' => $data['type'],
                'priorite' => $data['priorite'],
                'statut' => 'CLOTURE',
                'lieu' => $data['lieu'],
                'equipement' => $data['equipement'],
                'auteur_id' => $data['auteur']->id,
                'date_resolution' => $resolvedDate,
                'date_cloture' => $closedDate,
                'note' => $data['note'],
                'commentaire_validation' => 'Intervention très satisfaisante.',
                'created_at' => $createdDate,
            ]);

            IncidentAssignment::create([
                'incident_id' => $incident->id,
                'maintenancier_id' => $data['tech']->id,
                'assigne_par_id' => $chef->id,
                'instructions' => 'Intervenir dans les meilleurs délais.',
                'date_affectation' => $createdDate->copy()->addMinutes(15),
                'date_prise_en_charge' => $createdDate->copy()->addMinutes(45),
                'date_resolution' => $resolvedDate,
                'rapport_intervention' => $data['rapport'],
                'is_active' => true,
                'created_at' => $createdDate->copy()->addMinutes(15),
            ]);

            IncidentStatusHistory::create([
                'incident_id' => $incident->id,
                'ancien_statut' => 'RESOLU',
                'nouveau_statut' => 'CLOTURE',
                'modifie_par_id' => $data['auteur']->id,
                'commentaire' => 'Incident validé et clôturé.',
                'created_at' => $closedDate,
            ]);
        }
    }
}
