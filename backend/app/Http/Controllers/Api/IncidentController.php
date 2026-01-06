<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\IncidentImage;
use App\Models\IncidentStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IncidentController extends Controller
{
    /**
     * Liste tous les incidents (avec filtres)
     */
    public function index(Request $request)
    {
        $query = Incident::with(['auteur', 'affectationActive.maintenancier']);

        // Filtres
        if ($request->has('statut') && $request->statut) {
            $query->where('statut', $request->statut);
        }
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }
        if ($request->has('priorite') && $request->priorite) {
            $query->where('priorite', $request->priorite);
        }
        if ($request->has('date_debut') && $request->has('date_fin')) {
            $query->whereBetween('created_at', [$request->date_debut, $request->date_fin]);
        }

        // Tri par date de création décroissante
        $query->orderBy('created_at', 'desc');

        return response()->json($query->paginate(15));
    }

    /**
     * Liste les incidents de l'utilisateur connecté
     */
    public function mesIncidents(Request $request)
    {
        $query = Incident::with(['affectationActive.maintenancier'])
            ->where('auteur_id', $request->user()->id);

        if ($request->has('statut') && $request->statut) {
            $query->where('statut', $request->statut);
        }

        $query->orderBy('created_at', 'desc');

        return response()->json($query->paginate(15));
    }

    /**
     * Créer un nouvel incident
     */
    public function store(Request $request)
    {
        $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'type' => ['required', 'in:RESEAU,LOGICIEL,HARDWARE'],
            'priorite' => ['required', 'in:BASSE,MOYENNE,HAUTE,CRITIQUE'],
            'lieu' => ['nullable', 'string', 'max:255'],
            'equipement' => ['nullable', 'string', 'max:255'],
            'images.*' => ['nullable', 'image', 'max:10240'], // 10MB max
        ]);

        $incident = Incident::create([
            'titre' => $request->titre,
            'description' => $request->description,
            'type' => $request->type,
            'priorite' => $request->priorite,
            'lieu' => $request->lieu,
            'equipement' => $request->equipement,
            'auteur_id' => $request->user()->id,
            'statut' => 'OUVERT',
        ]);

        // Enregistrer l'historique
        IncidentStatusHistory::create([
            'incident_id' => $incident->id,
            'ancien_statut' => null,
            'nouveau_statut' => 'OUVERT',
            'modifie_par_id' => $request->user()->id,
            'commentaire' => 'Incident créé',
        ]);

        // Upload des images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('incidents/' . $incident->id, 'public');

                IncidentImage::create([
                    'incident_id' => $incident->id,
                    'nom_fichier' => $image->getClientOriginalName(),
                    'chemin' => $path,
                    'type_mime' => $image->getMimeType(),
                    'taille' => $image->getSize(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Incident créé avec succès',
            'incident' => $incident->load(['auteur', 'images']),
        ], 201);
    }

    /**
     * Afficher un incident
     */
    public function show(Incident $incident)
    {
        return response()->json(
            $incident->load([
                'auteur',
                'images',
                'affectationActive.maintenancier',
                'affectationActive.assignePar',
                'affectations.maintenancier',
                'historiqueStatuts.modifiePar',
            ])
        );
    }

    /**
     * Mettre à jour un incident
     */
    public function update(Request $request, Incident $incident)
    {
        // Seul l'auteur peut modifier (si pas encore affecté)
        if ($incident->auteur_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if ($incident->statut !== 'OUVERT') {
            return response()->json([
                'message' => 'L\'incident ne peut plus être modifié',
            ], 422);
        }

        $request->validate([
            'titre' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'type' => ['sometimes', 'in:RESEAU,LOGICIEL,HARDWARE'],
            'priorite' => ['sometimes', 'in:BASSE,MOYENNE,HAUTE,CRITIQUE'],
            'lieu' => ['nullable', 'string', 'max:255'],
            'equipement' => ['nullable', 'string', 'max:255'],
        ]);

        $incident->update($request->only([
            'titre', 'description', 'type', 'priorite', 'lieu', 'equipement'
        ]));

        return response()->json([
            'message' => 'Incident mis à jour',
            'incident' => $incident->fresh(['auteur', 'images']),
        ]);
    }

    /**
     * Supprimer un incident
     */
    public function destroy(Request $request, Incident $incident)
    {
        // Seul l'auteur ou un admin peut supprimer
        if ($incident->auteur_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if ($incident->statut !== 'OUVERT') {
            return response()->json([
                'message' => 'L\'incident ne peut plus être supprimé',
            ], 422);
        }

        // Supprimer les images
        foreach ($incident->images as $image) {
            Storage::disk('public')->delete($image->chemin);
        }

        $incident->delete();

        return response()->json([
            'message' => 'Incident supprimé',
        ]);
    }

    /**
     * Affecter un incident à un maintenancier (Chef Service)
     */
    public function affecter(Request $request, Incident $incident)
    {
        // Vérifier les permissions
        if (!$request->user()->isChefService()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if (!$incident->peutEtreAffecte()) {
            return response()->json([
                'message' => 'L\'incident ne peut pas être affecté',
            ], 422);
        }

        $request->validate([
            'maintenancier_id' => ['required', 'exists:users,id'],
            'instructions' => ['nullable', 'string'],
        ]);

        // Désactiver les anciennes affectations
        $incident->affectations()->update(['is_active' => false]);

        // Créer la nouvelle affectation
        $incident->affectations()->create([
            'maintenancier_id' => $request->maintenancier_id,
            'assigne_par_id' => $request->user()->id,
            'instructions' => $request->instructions,
            'date_affectation' => now(),
            'is_active' => true,
        ]);

        // Mettre à jour le statut
        $ancienStatut = $incident->statut;
        $incident->update(['statut' => 'AFFECTE']);

        // Historique
        IncidentStatusHistory::create([
            'incident_id' => $incident->id,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => 'AFFECTE',
            'modifie_par_id' => $request->user()->id,
            'commentaire' => 'Incident affecté',
        ]);

        return response()->json([
            'message' => 'Incident affecté avec succès',
            'incident' => $incident->fresh(['affectationActive.maintenancier']),
        ]);
    }

    /**
     * Prendre en charge un incident (Maintenancier)
     */
    public function prendreEnCharge(Request $request, Incident $incident)
    {
        $affectation = $incident->affectationActive;

        if (!$affectation || $affectation->maintenancier_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $affectation->prendreEnCharge();

        // Mettre à jour le statut
        $ancienStatut = $incident->statut;
        $incident->update(['statut' => 'EN_COURS']);

        // Historique
        IncidentStatusHistory::create([
            'incident_id' => $incident->id,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => 'EN_COURS',
            'modifie_par_id' => $request->user()->id,
            'commentaire' => 'Prise en charge',
        ]);

        return response()->json([
            'message' => 'Incident pris en charge',
            'incident' => $incident->fresh(['affectationActive.maintenancier']),
        ]);
    }

    /**
     * Résoudre un incident (Maintenancier)
     */
    public function resoudre(Request $request, Incident $incident)
    {
        $affectation = $incident->affectationActive;

        if (!$affectation || $affectation->maintenancier_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if (!$incident->peutEtreResolu()) {
            return response()->json([
                'message' => 'L\'incident ne peut pas être résolu dans son état actuel',
            ], 422);
        }

        $request->validate([
            'rapport_intervention' => ['required', 'string'],
        ]);

        // Marquer l'affectation comme résolue
        $affectation->marquerResolu($request->rapport_intervention);

        // Mettre à jour l'incident
        $ancienStatut = $incident->statut;
        $incident->update([
            'statut' => 'EN_ATTENTE_VALIDATION',
            'date_resolution' => now(),
            'commentaire_resolution' => $request->rapport_intervention,
        ]);

        // Historique
        IncidentStatusHistory::create([
            'incident_id' => $incident->id,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => 'EN_ATTENTE_VALIDATION',
            'modifie_par_id' => $request->user()->id,
            'commentaire' => 'Résolu - En attente de validation',
        ]);

        return response()->json([
            'message' => 'Incident résolu, en attente de validation par l\'auteur',
            'incident' => $incident->fresh(['affectationActive.maintenancier']),
        ]);
    }

    /**
     * Valider/Clôturer un incident (Auteur - double validation)
     */
    public function valider(Request $request, Incident $incident)
    {
        // Seul l'auteur peut valider
        if ($incident->auteur_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if (!$incident->peutEtreCloture()) {
            return response()->json([
                'message' => 'L\'incident ne peut pas être clôturé dans son état actuel',
            ], 422);
        }

        $request->validate([
            'note' => ['required', 'integer', 'min:1', 'max:10'],
            'commentaire_validation' => ['nullable', 'string'],
        ]);

        // Clôturer l'incident
        $ancienStatut = $incident->statut;
        $incident->update([
            'statut' => 'CLOTURE',
            'date_cloture' => now(),
            'note' => $request->note,
            'commentaire_validation' => $request->commentaire_validation,
        ]);

        // Historique
        IncidentStatusHistory::create([
            'incident_id' => $incident->id,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => 'CLOTURE',
            'modifie_par_id' => $request->user()->id,
            'commentaire' => 'Clôturé par l\'auteur avec une note de ' . $request->note . '/10',
        ]);

        return response()->json([
            'message' => 'Incident clôturé avec succès',
            'incident' => $incident->fresh(),
        ]);
    }

    /**
     * Rejeter la résolution (Auteur)
     */
    public function rejeter(Request $request, Incident $incident)
    {
        // Seul l'auteur peut rejeter
        if ($incident->auteur_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if ($incident->statut !== 'EN_ATTENTE_VALIDATION') {
            return response()->json([
                'message' => 'L\'incident n\'est pas en attente de validation',
            ], 422);
        }

        $request->validate([
            'motif' => ['required', 'string'],
        ]);

        // Remettre en cours
        $ancienStatut = $incident->statut;
        $incident->update([
            'statut' => 'EN_COURS',
            'date_resolution' => null,
        ]);

        // Historique
        IncidentStatusHistory::create([
            'incident_id' => $incident->id,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => 'EN_COURS',
            'modifie_par_id' => $request->user()->id,
            'commentaire' => 'Résolution rejetée: ' . $request->motif,
        ]);

        return response()->json([
            'message' => 'Résolution rejetée',
            'incident' => $incident->fresh(['affectationActive.maintenancier']),
        ]);
    }
}
