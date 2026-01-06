<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Générer la fiche d'intervention PDF
     */
    public function ficheIntervention(Incident $incident)
    {
        $incident->load([
            'auteur',
            'images',
            'affectationActive.maintenancier',
            'affectationActive.assignePar',
            'affectations.maintenancier',
            'historiqueStatuts.modifiePar',
        ]);

        $pdf = Pdf::loadView('pdf.fiche-intervention', [
            'incident' => $incident,
        ]);

        return $pdf->download('fiche-intervention-' . $incident->reference . '.pdf');
    }

    /**
     * Statistiques des incidents sur une période
     */
    public function statistiques(Request $request)
    {
        $request->validate([
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
        ]);

        $dateDebut = $request->date_debut;
        $dateFin = $request->date_fin;

        // Total des incidents
        $totalIncidents = Incident::whereBetween('created_at', [$dateDebut, $dateFin])->count();

        // Par statut
        $parStatut = Incident::whereBetween('created_at', [$dateDebut, $dateFin])
            ->select('statut', DB::raw('count(*) as count'))
            ->groupBy('statut')
            ->pluck('count', 'statut');

        // Par type
        $parType = Incident::whereBetween('created_at', [$dateDebut, $dateFin])
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type');

        // Par priorité
        $parPriorite = Incident::whereBetween('created_at', [$dateDebut, $dateFin])
            ->select('priorite', DB::raw('count(*) as count'))
            ->groupBy('priorite')
            ->pluck('count', 'priorite');

        // Temps moyen de résolution (en heures)
        $tempsMoyenResolution = Incident::whereBetween('created_at', [$dateDebut, $dateFin])
            ->whereNotNull('date_resolution')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, date_resolution)) as temps_moyen')
            ->first()
            ->temps_moyen ?? 0;

        // Note moyenne
        $noteMoyenne = Incident::whereBetween('created_at', [$dateDebut, $dateFin])
            ->whereNotNull('note')
            ->avg('note') ?? 0;

        // Incidents par jour
        $parJour = Incident::whereBetween('created_at', [$dateDebut, $dateFin])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Top maintenanciers
        $topMaintenanciers = DB::table('incident_assignments')
            ->join('users', 'incident_assignments.maintenancier_id', '=', 'users.id')
            ->join('incidents', 'incident_assignments.incident_id', '=', 'incidents.id')
            ->whereBetween('incidents.created_at', [$dateDebut, $dateFin])
            ->whereNotNull('incident_assignments.date_resolution')
            ->select('users.name', DB::raw('count(*) as interventions'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('interventions')
            ->limit(5)
            ->get();

        return response()->json([
            'periode' => [
                'debut' => $dateDebut,
                'fin' => $dateFin,
            ],
            'total_incidents' => $totalIncidents,
            'incidents_par_statut' => $parStatut,
            'incidents_par_type' => $parType,
            'incidents_par_priorite' => $parPriorite,
            'temps_moyen_resolution' => round($tempsMoyenResolution, 2),
            'note_moyenne' => round($noteMoyenne, 2),
            'incidents_par_jour' => $parJour,
            'top_maintenanciers' => $topMaintenanciers,
        ]);
    }

    /**
     * Export des incidents (PDF ou Excel)
     */
    public function export(Request $request)
    {
        $request->validate([
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'format' => ['required', 'in:pdf,excel'],
        ]);

        $incidents = Incident::with(['auteur', 'affectationActive.maintenancier'])
            ->whereBetween('created_at', [$request->date_debut, $request->date_fin])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($request->format === 'pdf') {
            $pdf = Pdf::loadView('pdf.liste-incidents', [
                'incidents' => $incidents,
                'periode' => [
                    'debut' => $request->date_debut,
                    'fin' => $request->date_fin,
                ],
            ]);

            return $pdf->download('incidents-' . $request->date_debut . '-' . $request->date_fin . '.pdf');
        }

        // Pour Excel, retourner les données JSON (l'export Excel peut être géré côté frontend)
        return response()->json($incidents);
    }
}
