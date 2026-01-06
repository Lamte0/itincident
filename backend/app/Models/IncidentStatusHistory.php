<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'incident_status_history';

    protected $fillable = [
        'incident_id',
        'ancien_statut',
        'nouveau_statut',
        'modifie_par_id',
        'commentaire',
    ];

    /**
     * L'incident associé
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * L'utilisateur qui a modifié le statut
     */
    public function modifiePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modifie_par_id');
    }
}
