<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Client extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'titulaire',
        'nci',
        'email',
        'telephone',
        'adresse',
        'statut',
        'email_verified_at',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relations
    public function comptes(): HasMany
    {
        return $this->hasMany(Compte::class);
    }

    // Méthodes utilitaires
    public function getNomCompletAttribute()
    {
        return $this->titulaire;
    }

    public function getRoleAttribute()
    {
        return 'client';
    }

    public function isActif()
    {
        return $this->statut === 'actif';
    }
}
