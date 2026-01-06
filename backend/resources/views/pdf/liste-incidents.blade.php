<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Incidents</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 16px;
            color: #1e40af;
            margin: 0;
        }
        .header h2 {
            font-size: 12px;
            color: #666;
            margin: 5px 0 0;
            font-weight: normal;
        }
        .periode {
            background: #f0f4f8;
            padding: 10px;
            margin-bottom: 15px;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background: #1e40af;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background: #f8fafc;
        }
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-red { background: #fee2e2; color: #b91c1c; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-purple { background: #ede9fe; color: #5b21b6; }
        .badge-gray { background: #f3f4f6; color: #374151; }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
        .summary {
            margin-bottom: 20px;
        }
        .summary-item {
            display: inline-block;
            margin-right: 20px;
            padding: 5px 10px;
            background: #f0f4f8;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>DIRECTION GÉNÉRALE DU TRÉSOR ET DE LA COMPTABILITÉ PUBLIQUE</h1>
        <h2>Liste des Incidents Informatiques</h2>
    </div>

    <div class="periode">
        <strong>Période :</strong> du {{ \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') }}
        au {{ \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y') }}
    </div>

    <div class="summary">
        <span class="summary-item"><strong>Total :</strong> {{ $incidents->count() }} incidents</span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 80px;">Référence</th>
                <th style="width: 180px;">Titre</th>
                <th style="width: 60px;">Type</th>
                <th style="width: 60px;">Priorité</th>
                <th style="width: 80px;">Statut</th>
                <th style="width: 100px;">Auteur</th>
                <th style="width: 100px;">Technicien</th>
                <th style="width: 70px;">Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($incidents as $incident)
            <tr>
                <td>{{ $incident->reference }}</td>
                <td>{{ Str::limit($incident->titre, 40) }}</td>
                <td>{{ $incident->type }}</td>
                <td>
                    @switch($incident->priorite)
                        @case('CRITIQUE')
                            <span class="badge badge-red">CRITIQUE</span>
                            @break
                        @case('HAUTE')
                            <span class="badge badge-yellow">HAUTE</span>
                            @break
                        @case('MOYENNE')
                            <span class="badge badge-blue">MOYENNE</span>
                            @break
                        @default
                            <span class="badge badge-gray">BASSE</span>
                    @endswitch
                </td>
                <td>
                    @switch($incident->statut)
                        @case('OUVERT')
                            <span class="badge badge-red">OUVERT</span>
                            @break
                        @case('AFFECTE')
                            <span class="badge badge-blue">AFFECTÉ</span>
                            @break
                        @case('EN_COURS')
                            <span class="badge badge-yellow">EN COURS</span>
                            @break
                        @case('RESOLU')
                            <span class="badge badge-green">RÉSOLU</span>
                            @break
                        @case('EN_ATTENTE_VALIDATION')
                            <span class="badge badge-purple">VALIDATION</span>
                            @break
                        @case('CLOTURE')
                            <span class="badge badge-gray">CLÔTURÉ</span>
                            @break
                    @endswitch
                </td>
                <td>{{ $incident->auteur->name }}</td>
                <td>{{ $incident->affectationActive?->maintenancier?->name ?? '-' }}</td>
                <td>{{ $incident->created_at->format('d/m/Y') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center;">Aucun incident trouvé pour cette période</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y à H:i') }} - Système de Gestion des Incidents DGTCP
    </div>
</body>
</html>
