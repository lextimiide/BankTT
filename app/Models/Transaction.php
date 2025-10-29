<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'numero_transaction',
        'type',
        'montant',
        'devise',
        'description',
        'statut',
        'date_transaction',
        'compte_id',
        'compte_destination_id',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_transaction' => 'datetime',
    ];

    // Relations
    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }

    public function compteDestination(): BelongsTo
    {
        return $this->belongsTo(Compte::class, 'compte_destination_id');
    }

    // Mutator pour générer automatiquement le numéro de transaction
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->numero_transaction)) {
                $transaction->numero_transaction = self::generateNumeroTransaction();
            }
            if (empty($transaction->date_transaction)) {
                $transaction->date_transaction = now();
            }
        });
    }

    /**
     * Génère un numéro de transaction unique
     */
    private static function generateNumeroTransaction(): string
    {
        do {
            // Format: TX + Année + Mois + Jour + 6 chiffres aléatoires
            $numero = 'TX' . date('ymd') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('numero_transaction', $numero)->exists());

        return $numero;
    }

    // Méthodes utilitaires
    public function isValidee(): bool
    {
        return $this->statut === 'validee';
    }

    public function isRejetee(): bool
    {
        return $this->statut === 'rejete';
    }

    public function getMontantFormateAttribute(): string
    {
        return number_format($this->montant, 2, ',', ' ') . ' ' . $this->devise;
    }

    public function getTypeLibelleAttribute(): string
    {
        return match($this->type) {
            'depot' => 'Dépôt',
            'retrait' => 'Retrait',
            'virement' => 'Virement',
            'transfert' => 'Transfert',
            'frais' => 'Frais bancaires',
            default => ucfirst($this->type)
        };
    }

    // Scopes pour filtrer
    public function scopeValidees($query)
    {
        return $query->where('statut', 'validee');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeRejetees($query)
    {
        return $query->where('statut', 'rejete');
    }

    // Scope par type
    public function scopeDepots($query)
    {
        return $query->where('type', 'depot');
    }

    public function scopeRetraits($query)
    {
        return $query->where('type', 'retrait');
    }

    public function scopeVirements($query)
    {
        return $query->where('type', 'virement');
    }

    // Scope par période
    public function scopeAujourdHui($query)
    {
        return $query->whereDate('date_transaction', today());
    }

    public function scopeCeMois($query)
    {
        return $query->whereMonth('date_transaction', now()->month)
                    ->whereYear('date_transaction', now()->year);
    }
}
