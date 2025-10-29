<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\NonDeletedScope;

class Compte extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new NonDeletedScope);
    }

    protected $fillable = [
        'numero_compte',
        'type',
        'solde_initial',
        'devise',
        'statut',
        'client_id',
    ];

    protected $casts = [
        'solde_initial' => 'decimal:2',
    ];

    protected $appends = [
        'solde',
    ];

    // Relations
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Mutator pour générer automatiquement le numéro de compte
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($compte) {
            if (empty($compte->numero_compte)) {
                $compte->numero_compte = self::generateNumeroCompte();
            }
        });
    }

    /**
     * Génère un numéro de compte unique
     */
    private static function generateNumeroCompte(): string
    {
        do {
            // Format: CB + Année + Mois + 8 chiffres aléatoires
            $numero = 'CB' . date('ym') . str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        } while (self::where('numero_compte', $numero)->exists());

        return $numero;
    }

    // Méthodes utilitaires
    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }

    /**
     * Calcule le solde dynamique basé sur les transactions
     * Solde = solde_initial + (dépôts + virements reçus) - (retraits + virements envoyés + frais)
     */
    public function getSoldeAttribute(): float
    {
        // Commencer par le solde initial
        $solde = (float) $this->solde_initial;

        // Récupérer toutes les transactions validées pour ce compte
        $transactions = $this->transactions()
            ->where('statut', 'validee')
            ->get();

        foreach ($transactions as $transaction) {
            $montant = (float) $transaction->montant;

            switch ($transaction->type) {
                case 'depot':
                    // Les dépôts augmentent le solde
                    $solde += $montant;
                    break;

                case 'retrait':
                    // Les retraits diminuent le solde
                    $solde -= $montant;
                    break;

                case 'virement':
                    // Pour les virements, vérifier si c'est entrant ou sortant
                    if ($transaction->compte_destination_id === $this->id) {
                        // Virement entrant (reçu)
                        $solde += $montant;
                    } else {
                        // Virement sortant (envoyé)
                        $solde -= $montant;
                    }
                    break;

                case 'transfert':
                    // Pour les transferts, vérifier si c'est entrant ou sortant
                    if ($transaction->compte_destination_id === $this->id) {
                        // Transfert entrant (reçu)
                        $solde += $montant;
                    } else {
                        // Transfert sortant (envoyé)
                        $solde -= $montant;
                    }
                    break;

                case 'frais':
                    // Les frais diminuent toujours le solde
                    $solde -= $montant;
                    break;
            }
        }

        return round($solde, 2);
    }

    public function getSoldeFormateAttribute(): string
    {
        return number_format($this->getSoldeAttribute(), 2, ',', ' ') . ' ' . $this->devise;
    }

    public function getSoldeInitialFormateAttribute(): string
    {
        return number_format($this->solde_initial, 2, ',', ' ') . ' ' . $this->devise;
    }

    // Scope pour filtrer par statut
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeBloque($query)
    {
        return $query->where('statut', 'bloque');
    }

    public function scopeFerme($query)
    {
        return $query->where('statut', 'ferme');
    }

    // Scope pour filtrer par type
    public function scopeCheque($query)
    {
        return $query->where('type', 'cheque');
    }

    public function scopeEpargne($query)
    {
        return $query->where('type', 'epargne');
    }

    public function scopeCourant($query)
    {
        return $query->where('type', 'courant');
    }

    /**
     * Scope pour rechercher un compte par son numéro
     */
    public function scopeNumero($query, string $numero)
    {
        return $query->where('numero_compte', $numero);
    }

    /**
     * Scope pour récupérer les comptes d'un client via son téléphone
     */
    public function scopeClient($query, string $telephone)
    {
        return $query->whereHas('client', function ($q) use ($telephone) {
            $q->where('telephone', $telephone);
        });
    }
}
