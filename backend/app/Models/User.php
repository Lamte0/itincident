<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'service',
        'telephone',
        'matricule',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Les incidents créés par cet utilisateur
     */
    public function incidentsCrees(): HasMany
    {
        return $this->hasMany(Incident::class, 'auteur_id');
    }

    /**
     * Les affectations d'incidents pour ce maintenancier
     */
    public function affectationsRecues(): HasMany
    {
        return $this->hasMany(IncidentAssignment::class, 'maintenancier_id');
    }

    /**
     * Les affectations faites par ce chef de service
     */
    public function affectationsFaites(): HasMany
    {
        return $this->hasMany(IncidentAssignment::class, 'assigne_par_id');
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Vérifie si l'utilisateur est admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    /**
     * Vérifie si l'utilisateur est chef de service
     */
    public function isChefService(): bool
    {
        return $this->role === 'SUPERVISEUR' || $this->role === 'ADMIN';
    }

    /**
     * Vérifie si l'utilisateur est maintenancier
     */
    public function isMaintenancier(): bool
    {
        return $this->role === 'MAINTENANCIER';
    }
}
