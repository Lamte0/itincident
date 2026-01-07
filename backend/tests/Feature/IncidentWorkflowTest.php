<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests d'intégration du workflow complet d'un incident
 */
class IncidentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $utilisateur;
    private User $chefService;
    private User $maintenancier;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les utilisateurs nécessaires
        $this->utilisateur = User::factory()->create([
            'role' => 'UTILISATEUR',
            'name' => 'Jean Dupont',
            'service' => 'Comptabilité',
        ]);

        $this->chefService = User::factory()->chefService()->create([
            'name' => 'Marie Martin',
            'service' => 'DSI',
        ]);

        $this->maintenancier = User::factory()->maintenancier()->create([
            'name' => 'Pierre Durand',
            'service' => 'DSI',
        ]);
    }

    /**
     * Test du workflow complet : OUVERT → AFFECTÉ → EN_COURS → RÉSOLU → CLÔTURÉ
     */
    public function test_workflow_complet_incident(): void
    {
        // === ÉTAPE 1: Création de l'incident par l'utilisateur ===
        $response = $this->actingAs($this->utilisateur)
            ->postJson('/api/incidents', [
                'titre' => 'Problème de connexion réseau',
                'description' => 'Impossible de se connecter au réseau depuis ce matin',
                'type' => 'RESEAU',
                'priorite' => 'HAUTE',
                'lieu' => 'Bureau 205',
                'equipement' => 'PC-COMPTA-01',
            ]);

        $response->assertStatus(201);
        $incidentId = $response->json('id');
        $this->assertEquals('OUVERT', $response->json('statut'));
        $this->assertStringStartsWith('INC-', $response->json('reference'));

        // === ÉTAPE 2: Affectation par le chef de service ===
        $response = $this->actingAs($this->chefService)
            ->postJson("/api/incidents/{$incidentId}/affecter", [
                'maintenancier_id' => $this->maintenancier->id,
                'instructions' => 'Vérifier la carte réseau et le câble',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('AFFECTE', $response->json('statut'));
        $this->assertEquals($this->maintenancier->id, $response->json('affectation_active.maintenancier_id'));

        // === ÉTAPE 3: Prise en charge par le maintenancier ===
        $response = $this->actingAs($this->maintenancier)
            ->postJson("/api/incidents/{$incidentId}/prendre-en-charge");

        $response->assertStatus(200);
        $this->assertEquals('EN_COURS', $response->json('statut'));
        $this->assertNotNull($response->json('affectation_active.date_prise_en_charge'));

        // === ÉTAPE 4: Résolution par le maintenancier ===
        $response = $this->actingAs($this->maintenancier)
            ->postJson("/api/incidents/{$incidentId}/resoudre", [
                'rapport_intervention' => 'Câble réseau défectueux remplacé. Connexion rétablie.',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('RESOLU', $response->json('statut'));
        $this->assertNotNull($response->json('date_resolution'));

        // === ÉTAPE 5: Validation par l'utilisateur ===
        $response = $this->actingAs($this->utilisateur)
            ->postJson("/api/incidents/{$incidentId}/valider", [
                'note' => 5,
                'commentaire_validation' => 'Intervention rapide et efficace, merci !',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('CLOTURE', $response->json('statut'));
        $this->assertEquals(5, $response->json('note'));
        $this->assertNotNull($response->json('date_cloture'));

        // Vérifier l'historique des statuts
        $incident = Incident::find($incidentId);
        $this->assertCount(5, $incident->historiqueStatuts);
    }

    /**
     * Test du workflow avec rejet de résolution
     */
    public function test_workflow_avec_rejet_resolution(): void
    {
        // Créer un incident déjà résolu
        $incident = Incident::factory()->create([
            'auteur_id' => $this->utilisateur->id,
            'statut' => 'OUVERT',
        ]);

        // Affecter
        $this->actingAs($this->chefService)
            ->postJson("/api/incidents/{$incident->id}/affecter", [
                'maintenancier_id' => $this->maintenancier->id,
            ]);

        // Prendre en charge
        $this->actingAs($this->maintenancier)
            ->postJson("/api/incidents/{$incident->id}/prendre-en-charge");

        // Résoudre
        $this->actingAs($this->maintenancier)
            ->postJson("/api/incidents/{$incident->id}/resoudre", [
                'rapport_intervention' => 'Problème résolu',
            ]);

        // Rejeter la résolution
        $response = $this->actingAs($this->utilisateur)
            ->postJson("/api/incidents/{$incident->id}/rejeter", [
                'motif' => 'Le problème persiste toujours',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('EN_COURS', $response->json('statut'));
    }

    /**
     * Test des permissions : utilisateur ne peut pas affecter
     */
    public function test_utilisateur_ne_peut_pas_affecter(): void
    {
        $incident = Incident::factory()->create([
            'auteur_id' => $this->utilisateur->id,
            'statut' => 'OUVERT',
        ]);

        $response = $this->actingAs($this->utilisateur)
            ->postJson("/api/incidents/{$incident->id}/affecter", [
                'maintenancier_id' => $this->maintenancier->id,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test des permissions : maintenancier ne peut pas valider
     */
    public function test_maintenancier_ne_peut_pas_valider(): void
    {
        $incident = Incident::factory()->resolu()->create([
            'auteur_id' => $this->utilisateur->id,
        ]);

        $response = $this->actingAs($this->maintenancier)
            ->postJson("/api/incidents/{$incident->id}/valider", [
                'note' => 5,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test que seul l'auteur peut valider/rejeter son incident
     */
    public function test_seul_auteur_peut_valider_son_incident(): void
    {
        $autreUtilisateur = User::factory()->create(['role' => 'UTILISATEUR']);

        $incident = Incident::factory()->resolu()->create([
            'auteur_id' => $this->utilisateur->id,
        ]);

        $response = $this->actingAs($autreUtilisateur)
            ->postJson("/api/incidents/{$incident->id}/valider", [
                'note' => 5,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test de la réaffectation d'un incident
     */
    public function test_reaffectation_incident(): void
    {
        $maintenancier2 = User::factory()->maintenancier()->create();

        $incident = Incident::factory()->create([
            'auteur_id' => $this->utilisateur->id,
            'statut' => 'OUVERT',
        ]);

        // Première affectation
        $this->actingAs($this->chefService)
            ->postJson("/api/incidents/{$incident->id}/affecter", [
                'maintenancier_id' => $this->maintenancier->id,
            ]);

        // Réaffectation à un autre maintenancier
        $response = $this->actingAs($this->chefService)
            ->postJson("/api/incidents/{$incident->id}/affecter", [
                'maintenancier_id' => $maintenancier2->id,
                'instructions' => 'Réaffecté pour meilleure prise en charge',
            ]);

        $response->assertStatus(200);
        $this->assertEquals($maintenancier2->id, $response->json('affectation_active.maintenancier_id'));

        // Vérifier que l'ancienne affectation est désactivée
        $incident->refresh();
        $affectations = $incident->affectations;
        $this->assertCount(2, $affectations);
        $this->assertEquals(1, $affectations->where('is_active', true)->count());
    }

    /**
     * Test des transitions de statut invalides
     */
    public function test_transition_statut_invalide(): void
    {
        $incident = Incident::factory()->create([
            'auteur_id' => $this->utilisateur->id,
            'statut' => 'OUVERT',
        ]);

        // Impossible de résoudre un incident non pris en charge
        $response = $this->actingAs($this->maintenancier)
            ->postJson("/api/incidents/{$incident->id}/resoudre", [
                'rapport_intervention' => 'Test',
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test de création d'incident avec images
     */
    public function test_creation_incident_avec_images(): void
    {
        // Créer des fichiers images factices pour le test
        $response = $this->actingAs($this->utilisateur)
            ->postJson('/api/incidents', [
                'titre' => 'Écran cassé',
                'description' => 'L\'écran affiche des lignes verticales',
                'type' => 'HARDWARE',
                'priorite' => 'MOYENNE',
                'lieu' => 'Bureau 101',
            ]);

        $response->assertStatus(201);
        $this->assertEquals('HARDWARE', $response->json('type'));
    }

    /**
     * Test du filtrage des incidents par statut
     */
    public function test_filtrage_incidents_par_statut(): void
    {
        // Créer des incidents avec différents statuts
        Incident::factory()->count(3)->create(['statut' => 'OUVERT']);
        Incident::factory()->count(2)->affecte()->create();
        Incident::factory()->count(1)->cloture()->create();

        $response = $this->actingAs($this->chefService)
            ->getJson('/api/incidents?statut=OUVERT');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test des statistiques des incidents
     */
    public function test_statistiques_incidents(): void
    {
        // Créer des incidents variés
        Incident::factory()->count(5)->create(['statut' => 'OUVERT', 'type' => 'RESEAU']);
        Incident::factory()->count(3)->create(['statut' => 'OUVERT', 'type' => 'LOGICIEL']);
        Incident::factory()->count(2)->cloture()->create(['type' => 'HARDWARE']);

        $response = $this->actingAs($this->chefService)
            ->getJson('/api/statistiques');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'par_statut',
            'par_type',
            'par_priorite',
        ]);
    }
}
