<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_id',
        'maintenancier_id',
        'assigne_par_id',
        'instructions',
        'date_affectation',
        'date_prise_en_charge',
        'date_resolution',
        'rapport_intervention',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date_affectation' => 'datetime',
            'date_prise_en_charge' => 'datetime',
            'date_resolution' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * L'incident associé
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * Le maintenancier assigné
     */
    public function maintenancier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'maintenancier_id');
    }

    /**
     * Le chef de service qui a fait l'affectation
     */
    public function assignePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigne_par_id');
    }

    /**
     * Marque l'affectation comme prise en charge
     */
    public function prendreEnCharge(): void
    {
        $this->update([
            'date_prise_en_charge' => now(),
        ]);
    }

    /**
     * Marque l'affectation comme résolue
     */
    public function marquerResolu(string $rapportIntervention = null): void
    {
        $this->update([
            'date_resolution' => now(),
            'rapport_intervention' => $rapportIntervention,
        ]);
    }
}
