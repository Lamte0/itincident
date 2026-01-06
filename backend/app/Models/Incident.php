<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Incident extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'titre',
        'description',
        'type',
        'priorite',
        'statut',
        'auteur_id',
        'lieu',
        'equipement',
        'date_resolution',
        'date_cloture',
        'commentaire_resolution',
        'note',
        'commentaire_validation',
    ];

    protected function casts(): array
    {
        return [
            'date_resolution' => 'datetime',
            'date_cloture' => 'datetime',
            'note' => 'integer',
        ];
    }

    /**
     * Génère une référence unique pour l'incident
     */
    public static function generateReference(): string
    {
        $year = date('Y');
        $lastIncident = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastIncident
            ? intval(substr($lastIncident->reference, -4)) + 1
            : 1;

        return sprintf('INC-%s-%04d', $year, $nextNumber);
    }

    /**
     * Boot method pour générer automatiquement la référence
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($incident) {
            if (empty($incident->reference)) {
                $incident->reference = self::generateReference();
            }
        });
    }

    /**
     * L'auteur de l'incident
     */
    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    /**
     * Les images associées à l'incident
     */
    public function images(): HasMany
    {
        return $this->hasMany(IncidentImage::class);
    }

    /**
     * Les affectations de l'incident
     */
    public function affectations(): HasMany
    {
        return $this->hasMany(IncidentAssignment::class);
    }

    /**
     * L'affectation active de l'incident
     */
    public function affectationActive(): HasOne
    {
        return $this->hasOne(IncidentAssignment::class)->where('is_active', true);
    }

    /**
     * L'historique des statuts
     */
    public function historiqueStatuts(): HasMany
    {
        return $this->hasMany(IncidentStatusHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Scope pour filtrer par statut
     */
    public function scopeParStatut($query, string $statut)
    {
        return $query->where('statut', $statut);
    }

    /**
     * Scope pour filtrer par type
     */
    public function scopeParType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour filtrer par priorité
     */
    public function scopeParPriorite($query, string $priorite)
    {
        return $query->where('priorite', $priorite);
    }

    /**
     * Scope pour filtrer par période
     */
    public function scopeParPeriode($query, $dateDebut, $dateFin)
    {
        return $query->whereBetween('created_at', [$dateDebut, $dateFin]);
    }

    /**
     * Vérifie si l'incident peut être affecté
     */
    public function peutEtreAffecte(): bool
    {
        return $this->statut === 'OUVERT';
    }

    /**
     * Vérifie si l'incident peut être résolu
     */
    public function peutEtreResolu(): bool
    {
        return in_array($this->statut, ['AFFECTE', 'EN_COURS']);
    }

    /**
     * Vérifie si l'incident peut être validé/clôturé
     */
    public function peutEtreCloture(): bool
    {
        return $this->statut === 'EN_ATTENTE_VALIDATION';
    }
}
