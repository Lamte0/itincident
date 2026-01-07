<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IncidentTest extends TestCase
{
    use RefreshDatabase;

    protected User $utilisateur;
    protected User $maintenancier;
    protected User $chefService;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Création des utilisateurs pour les tests
        $this->utilisateur = User::factory()->create(['role' => 'UTILISATEUR']);
        $this->maintenancier = User::factory()->create(['role' => 'MAINTENANCIER']);
        $this->chefService = User::factory()->create(['role' => 'CHEF_SERVICE']);
        $this->admin = User::factory()->create(['role' => 'ADMIN']);
    }

    /**
     * Test de création d'un incident
     */
    public function test_user_can_create_incident(): void
    {
        $response = $this->actingAs($this->utilisateur)->postJson('/api/incidents', [
            'titre' => 'Problème de connexion réseau',
            'description' => 'Je n\'arrive pas à me connecter au réseau',
            'type' => 'RESEAU',
            'priorite' => 'HAUTE',
            'lieu' => 'Bureau 101',
            'equipement' => 'PC-001',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'incident' => [
                    'id',
                    'reference',
                    'titre',
                    'description',
                    'type',
                    'priorite',
                    'statut',
                    'auteur_id',
                ],
            ]);

        $this->assertDatabaseHas('incidents', [
            'titre' => 'Problème de connexion réseau',
            'type' => 'RESEAU',
            'priorite' => 'HAUTE',
            'statut' => 'OUVERT',
            'auteur_id' => $this->utilisateur->id,
        ]);
    }

    /**
     * Test de création d'incident avec images
     */
    public function test_user_can_create_incident_with_images(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->utilisateur)->postJson('/api/incidents', [
            'titre' => 'Écran cassé',
            'description' => 'L\'écran affiche des lignes verticales',
            'type' => 'HARDWARE',
            'priorite' => 'MOYENNE',
            'images' => [
                UploadedFile::fake()->image('photo1.jpg'),
                UploadedFile::fake()->image('photo2.png'),
            ],
        ]);

        $response->assertStatus(201);

        $incident = Incident::first();
        $this->assertCount(2, $incident->images);
    }

    /**
     * Test de validation lors de la création
     */
    public function test_incident_creation_requires_valid_data(): void
    {
        $response = $this->actingAs($this->utilisateur)->postJson('/api/incidents', [
            'titre' => '', // vide
            'type' => 'INVALID_TYPE',
            'priorite' => 'INVALID',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['titre', 'description', 'type', 'priorite']);
    }

    /**
     * Test de liste des incidents
     */
    public function test_chef_service_can_list_all_incidents(): void
    {
        Incident::factory()->count(5)->create(['auteur_id' => $this->utilisateur->id]);

        $response = $this->actingAs($this->chefService)->getJson('/api/incidents');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'reference', 'titre', 'statut'],
                ],
                'current_page',
                'total',
            ]);

        $this->assertEquals(5, $response->json('total'));
    }

    /**
     * Test de filtrage des incidents par statut
     */
    public function test_incidents_can_be_filtered_by_status(): void
    {
        Incident::factory()->count(3)->create([
            'auteur_id' => $this->utilisateur->id,
            'statut' => 'OUVERT',
        ]);
        Incident::factory()->count(2)->create([
            'auteur_id' => $this->utilisateur->id,
            'statut' => 'EN_COURS',
        ]);

        $response = $this->actingAs($this->chefService)
            ->getJson('/api/incidents?statut=OUVERT');

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('total'));
    }

    /**
     * Test de récupération de mes incidents
     */
    public function test_user_can_get_own_incidents(): void
    {
        // Incidents de l'utilisateur
        Incident::factory()->count(3)->create(['auteur_id' => $this->utilisateur->id]);
        // Incidents d'un autre utilisateur
        Incident::factory()->count(2)->create(['auteur_id' => $this->maintenancier->id]);

        $response = $this->actingAs($this->utilisateur)
            ->getJson('/api/incidents/mes-incidents');

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('total'));
    }

    /**
     * Test de détail d'un incident
     */
    public function test_user_can_view_incident_details(): void
    {
        $incident = Incident::factory()->create([
            'auteur_id' => $this->utilisateur->id,
            'titre' => 'Test incident',
        ]);

        $response = $this->actingAs($this->utilisateur)
            ->getJson("/api/incidents/{$incident->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $incident->id,
                'titre' => 'Test incident',
            ]);
    }

    /**
     * Test d'affectation d'un incident par le chef de service
     */
    public function test_chef_service_can_assign_incident(): void
    {
        $incident = Incident::factory()->create([
            'auteur_id' => $this->utilisateur->id,
            'statut' => 'OUVERT',
        ]);

        $response = $this->actingAs($this->chefService)
            ->postJson("/api/incidents/{$incident->id}/affecter", [
                'maintenancier_id' => $this->maintenancier->id,
                'instructions' => 'Vérifier le câblage réseau',
            ]);

        $response->assertStatus(200);

        $incident->refresh();
        $this->assertEquals('AFFECTE', $incident->statut);
        $this->assertNotNull($incident->affectationActive);
        $this->assertEquals($this->maintenancier->id, $incident->affectationActive->maintenancier_id);
    }

    /**
     * Test qu'un utilisateur normal ne peut pas affecter
     */
    public function test_regular_user_cannot_assign_incident(): void
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
     * Test de prise en charge par le maintenancier
     */
    public function test_maintenancier_can_take_charge(): void
    {
        $incident = Incident::factory()->create([
            'auteur_id' => $this->utilisateur->id,
            'statut' => 'AFFECTE',
        ]);

        IncidentAssignment::factory()->create([
            'incident_id' => $incident->id,
            'maintenancier_id' => $this->maintenancier->id,
            'assigne_par_id' => $this->chefService->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->maintenancier)
            ->postJson("/api/incidents/{$incident->id}/prendre-en-charge");

        $response->assertStatus(200);

        $incident->refresh();
        $this->assertEquals('EN_COURS', $incident->statut);
    }

    /**
     * Test de résolution d'un incident
     */
    public function test_maintenancier_can_resolve_incident(): void
    {
        $incident = Incident::factory()->create([
            'auteur_id' => $this->utilisateur->id,
            'statut' => 'EN_COURS',
        ]);

        $assignment = IncidentAssignment::factory()->create([
            'incident_id' => $incident->id,
            'maintenancier_id' => $this->maintenancier->id,
            'assigne_par_id' => $this->chefService->id,
            'is_active' => true,
            'date_prise_en_charge' => now(),
        ]);

        $response = $this->actingAs($this->maintenancier)
            ->postJson("/api/incidents/{$incident->id}/resoudre", [
                'rapport_intervention' => 'Câble réseau remplacé, connexion rétablie.',
            ]);

        $response->assertStatus(200);

        $incident->refresh();
        $this->assertEquals('RESOLU', $incident->statut);
        $this->assertNotNull($incident->affectationActive->rapport_intervention);
    }

    /**
     * Test de validation par l'auteur
     */
    public function test_author_can_validate_resolution(): void
    {
        $incident = Incident::factory()->create([
            'auteur_id' => $this->utilisateur->id,
            'statut' => 'RESOLU',
        ]);

        IncidentAssignment::factory()->create([
            'incident_id' => $incident->id,
            'maintenancier_id' => $this->maintenancier->id,
            'assigne_par_id' => $this->chefService->id,
            'is_active' => true,
            'rapport_intervention' => 'Problème résolu',
        ]);

        $response = $this->actingAs($this->utilisateur)
            ->postJson("/api/incidents/{$incident->id}/valider", [
                'note' => 5,
                'commentaire_validation' => 'Excellent travail, merci !',
            ]);

        $response->assertStatus(200);

        $incident->refresh();
        $this->assertEquals('CLOTURE', $incident->statut);
        $this->assertEquals(5, $incident->note);
        $this->assertNotNull($incident->date_cloture);
    }

    /**
     * Test de rejet par l'auteur
     */
    public function test_author_can_reject_resolution(): void
    {
        $incident = Incident::factory()->create([
            'auteur_id' => $this->utilisateur->id,
            'statut' => 'RESOLU',
        ]);

        IncidentAssignment::factory()->create([
            'incident_id' => $incident->id,
            'maintenancier_id' => $this->maintenancier->id,
            'assigne_par_id' => $this->chefService->id,
            'is_active' => true,
            'rapport_intervention' => 'Problème résolu',
        ]);

        $response = $this->actingAs($this->utilisateur)
            ->postJson("/api/incidents/{$incident->id}/rejeter", [
                'motif' => 'Le problème persiste toujours',
            ]);

        $response->assertStatus(200);

        $incident->refresh();
        $this->assertEquals('EN_COURS', $incident->statut);
    }

    /**
     * Test qu'un utilisateur ne peut pas valider un incident qu'il n'a pas créé
     */
    public function test_non_author_cannot_validate(): void
    {
        $incident = Incident::factory()->create([
            'auteur_id' => $this->utilisateur->id,
            'statut' => 'RESOLU',
        ]);

        $otherUser = User::factory()->create(['role' => 'UTILISATEUR']);

        $response = $this->actingAs($otherUser)
            ->postJson("/api/incidents/{$incident->id}/valider", [
                'note' => 5,
            ]);

        $response->assertStatus(403);
    }
}
