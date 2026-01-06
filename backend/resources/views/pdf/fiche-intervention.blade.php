<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche d'intervention - {{ $incident->reference }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            color: #1e40af;
            margin: 0;
        }
        .header h2 {
            font-size: 14px;
            color: #666;
            margin: 5px 0 0;
            font-weight: normal;
        }
        .header .reference {
            font-size: 16px;
            font-weight: bold;
            margin-top: 15px;
            background: #f0f4f8;
            padding: 10px;
            display: inline-block;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            background: #1e40af;
            color: white;
            padding: 8px 15px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .section-content {
            padding: 0 15px;
        }
        .row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        .label {
            display: table-cell;
            width: 150px;
            font-weight: bold;
            color: #555;
        }
        .value {
            display: table-cell;
        }
        .badge {
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-red { background: #fee2e2; color: #b91c1c; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-purple { background: #ede9fe; color: #5b21b6; }
        .badge-gray { background: #f3f4f6; color: #374151; }

        .description-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            margin: 10px 0;
        }
        .timeline {
            border-left: 3px solid #1e40af;
            padding-left: 20px;
            margin-left: 10px;
        }
        .timeline-item {
            margin-bottom: 15px;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            width: 10px;
            height: 10px;
            background: #1e40af;
            border-radius: 50%;
            position: absolute;
            left: -26px;
            top: 5px;
        }
        .timeline-date {
            font-size: 11px;
            color: #666;
        }
        .signature-box {
            margin-top: 40px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .signature-grid {
            display: table;
            width: 100%;
        }
        .signature-cell {
            display: table-cell;
            width: 33%;
            text-align: center;
            padding: 10px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>DIRECTION GÉNÉRALE DU TRÉSOR ET DE LA COMPTABILITÉ PUBLIQUE</h1>
        <h2>Service Maintenance Informatique</h2>
        <div class="reference">FICHE D'INTERVENTION N° {{ $incident->reference }}</div>
    </div>

    <!-- Informations générales -->
    <div class="section">
        <div class="section-title">INFORMATIONS GÉNÉRALES</div>
        <div class="section-content">
            <div class="row">
                <span class="label">Référence :</span>
                <span class="value">{{ $incident->reference }}</span>
            </div>
            <div class="row">
                <span class="label">Date de création :</span>
                <span class="value">{{ $incident->created_at->format('d/m/Y à H:i') }}</span>
            </div>
            <div class="row">
                <span class="label">Type :</span>
                <span class="value">{{ $incident->type }}</span>
            </div>
            <div class="row">
                <span class="label">Priorité :</span>
                <span class="value">
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
                </span>
            </div>
            <div class="row">
                <span class="label">Statut actuel :</span>
                <span class="value">
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
                            <span class="badge badge-purple">EN ATTENTE VALIDATION</span>
                            @break
                        @case('CLOTURE')
                            <span class="badge badge-gray">CLÔTURÉ</span>
                            @break
                    @endswitch
                </span>
            </div>
        </div>
    </div>

    <!-- Demandeur -->
    <div class="section">
        <div class="section-title">DEMANDEUR</div>
        <div class="section-content">
            <div class="row">
                <span class="label">Nom :</span>
                <span class="value">{{ $incident->auteur->name }}</span>
            </div>
            <div class="row">
                <span class="label">Service :</span>
                <span class="value">{{ $incident->auteur->service ?? 'Non renseigné' }}</span>
            </div>
            <div class="row">
                <span class="label">Email :</span>
                <span class="value">{{ $incident->auteur->email }}</span>
            </div>
            <div class="row">
                <span class="label">Téléphone :</span>
                <span class="value">{{ $incident->auteur->telephone ?? 'Non renseigné' }}</span>
            </div>
        </div>
    </div>

    <!-- Description de l'incident -->
    <div class="section">
        <div class="section-title">DESCRIPTION DE L'INCIDENT</div>
        <div class="section-content">
            <div class="row">
                <span class="label">Titre :</span>
                <span class="value"><strong>{{ $incident->titre }}</strong></span>
            </div>
            @if($incident->lieu)
            <div class="row">
                <span class="label">Localisation :</span>
                <span class="value">{{ $incident->lieu }}</span>
            </div>
            @endif
            @if($incident->equipement)
            <div class="row">
                <span class="label">Équipement :</span>
                <span class="value">{{ $incident->equipement }}</span>
            </div>
            @endif
            <div class="description-box">
                {{ $incident->description }}
            </div>
        </div>
    </div>

    <!-- Intervention -->
    @if($incident->affectationActive)
    <div class="section">
        <div class="section-title">INTERVENTION</div>
        <div class="section-content">
            <div class="row">
                <span class="label">Technicien :</span>
                <span class="value">{{ $incident->affectationActive->maintenancier->name }}</span>
            </div>
            <div class="row">
                <span class="label">Affecté par :</span>
                <span class="value">{{ $incident->affectationActive->assignePar->name }}</span>
            </div>
            <div class="row">
                <span class="label">Date d'affectation :</span>
                <span class="value">{{ $incident->affectationActive->date_affectation?->format('d/m/Y à H:i') }}</span>
            </div>
            @if($incident->affectationActive->date_prise_en_charge)
            <div class="row">
                <span class="label">Prise en charge :</span>
                <span class="value">{{ $incident->affectationActive->date_prise_en_charge->format('d/m/Y à H:i') }}</span>
            </div>
            @endif
            @if($incident->affectationActive->date_resolution)
            <div class="row">
                <span class="label">Date de résolution :</span>
                <span class="value">{{ $incident->affectationActive->date_resolution->format('d/m/Y à H:i') }}</span>
            </div>
            @endif
            @if($incident->affectationActive->instructions)
            <div style="margin-top: 10px;">
                <strong>Instructions :</strong>
                <div class="description-box">{{ $incident->affectationActive->instructions }}</div>
            </div>
            @endif
            @if($incident->affectationActive->rapport_intervention)
            <div style="margin-top: 10px;">
                <strong>Rapport d'intervention :</strong>
                <div class="description-box">{{ $incident->affectationActive->rapport_intervention }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Résolution -->
    @if($incident->statut === 'CLOTURE')
    <div class="section">
        <div class="section-title">CLÔTURE</div>
        <div class="section-content">
            <div class="row">
                <span class="label">Date de clôture :</span>
                <span class="value">{{ $incident->date_cloture?->format('d/m/Y à H:i') }}</span>
            </div>
            <div class="row">
                <span class="label">Note attribuée :</span>
                <span class="value"><strong>{{ $incident->note }}/10</strong></span>
            </div>
            @if($incident->commentaire_validation)
            <div style="margin-top: 10px;">
                <strong>Commentaire du demandeur :</strong>
                <div class="description-box">{{ $incident->commentaire_validation }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Historique -->
    <div class="section">
        <div class="section-title">HISTORIQUE</div>
        <div class="section-content">
            <div class="timeline">
                @foreach($incident->historiqueStatuts as $historique)
                <div class="timeline-item">
                    <div class="timeline-date">{{ $historique->created_at->format('d/m/Y à H:i') }}</div>
                    <div><strong>{{ $historique->nouveau_statut }}</strong> par {{ $historique->modifiePar->name }}</div>
                    @if($historique->commentaire)
                    <div style="color: #666; font-size: 11px;">{{ $historique->commentaire }}</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Signatures -->
    <div class="signature-box">
        <div class="signature-grid">
            <div class="signature-cell">
                <strong>Le Demandeur</strong>
                <div class="signature-line">{{ $incident->auteur->name }}</div>
            </div>
            @if($incident->affectationActive)
            <div class="signature-cell">
                <strong>Le Technicien</strong>
                <div class="signature-line">{{ $incident->affectationActive->maintenancier->name }}</div>
            </div>
            <div class="signature-cell">
                <strong>Le Chef Service</strong>
                <div class="signature-line">{{ $incident->affectationActive->assignePar->name }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y à H:i') }} - Système de Gestion des Incidents DGTCP
    </div>
</body>
</html>
