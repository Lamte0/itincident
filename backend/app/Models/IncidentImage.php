<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class IncidentImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_id',
        'nom_fichier',
        'chemin',
        'type_mime',
        'taille',
    ];

    protected function casts(): array
    {
        return [
            'taille' => 'integer',
        ];
    }

    /**
     * L'incident associé à cette image
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * Récupère l'URL publique de l'image
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->chemin);
    }

    /**
     * Supprime le fichier lors de la suppression du modèle
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($image) {
            Storage::delete($image->chemin);
        });
    }
}
