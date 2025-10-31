<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_type', // 'admin' ou 'client'
        'role_id',   // ID dans la table admin ou client
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Relation polymorphique vers Admin ou Client
     */
    public function roleable()
    {
        return $this->morphTo();
    }

    /**
     * Créer un User wrapper pour un Admin ou Client
     */
    public static function createFromRole($roleModel)
    {
        $roleType = $roleModel instanceof Admin ? 'admin' : 'client';

        return static::create([
            'name' => $roleModel->email,
            'email' => $roleModel->email,
            'password' => $roleModel->password,
            'role_type' => $roleType,
            'role_id' => $roleModel->id,
        ]);
    }

    /**
     * Obtenir l'instance réelle (Admin ou Client)
     */
    public function getRealUser()
    {
        if ($this->role_type === 'admin') {
            return Admin::find($this->role_id);
        }

        if ($this->role_type === 'client') {
            return Client::find($this->role_id);
        }

        return null;
    }

    /**
     * Trouver un utilisateur par credentials (email/password)
     * Cette méthode est utilisée par Passport pour l'authentification
     */
    public function findForPassport($username)
    {
        // Essayer d'abord avec Admin
        $admin = Admin::where('email', $username)->first();
        if ($admin) {
            // Créer ou récupérer le wrapper User
            $user = static::where('role_type', 'admin')->where('role_id', $admin->id)->first();
            if (!$user) {
                $user = static::createFromRole($admin);
            }
            return $user;
        }

        // Essayer avec Client
        $client = Client::where('email', $username)->first();
        if ($client) {
            // Créer ou récupérer le wrapper User
            $user = static::where('role_type', 'client')->where('role_id', $client->id)->first();
            if (!$user) {
                $user = static::createFromRole($client);
            }
            return $user;
        }

        return null;
    }

    /**
     * Valider le mot de passe pour Passport
     */
    public function validateForPassportPasswordGrant($password)
    {
        $realUser = $this->getRealUser();
        return $realUser && \Hash::check($password, $realUser->password);
    }

    /**
     * Obtenir le rôle de l'utilisateur
     */
    public function getRoleAttribute()
    {
        return $this->role_type;
    }
}