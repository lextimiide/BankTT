<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Client;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\Bridge\User;
use Laravel\Passport\PassportUserProvider;

class CustomPassportUserProvider extends PassportUserProvider
{
    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Laravel\Passport\Bridge\User|null
     */
    public function getUserEntityByUserCredentials($username, $password, $grantType, \Laravel\Passport\Client $clientEntity)
    {
        // Essayer d'abord avec Admin
        $admin = Admin::where('email', $username)->first();
        if ($admin && \Hash::check($password, $admin->password)) {
            return new User($admin->getAuthIdentifier());
        }

        // Essayer avec Client
        $client = Client::where('email', $username)->first();
        if ($client && \Hash::check($password, $client->password)) {
            return new User($client->getAuthIdentifier());
        }

        return null;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        // Essayer d'abord avec Admin
        $admin = Admin::find($identifier);
        if ($admin) {
            return $admin;
        }

        // Essayer avec Client
        $client = Client::find($identifier);
        if ($client) {
            return $client;
        }

        return null;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        // Essayer d'abord avec Admin
        $admin = Admin::where('id', $identifier)
                     ->where('remember_token', $token)
                     ->first();
        if ($admin) {
            return $admin;
        }

        // Essayer avec Client
        $client = Client::where('id', $identifier)
                       ->where('remember_token', $token)
                       ->first();
        if ($client) {
            return $client;
        }

        return null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
        $user->save();
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) || !isset($credentials['email'])) {
            return null;
        }

        // Essayer d'abord avec Admin
        $admin = Admin::where('email', $credentials['email'])->first();
        if ($admin && $this->validateCredentials($admin, $credentials)) {
            return $admin;
        }

        // Essayer avec Client
        $client = Client::where('email', $credentials['email'])->first();
        if ($client && $this->validateCredentials($client, $credentials)) {
            return $client;
        }

        return null;
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return isset($credentials['password']) &&
               \Hash::check($credentials['password'], $user->getAuthPassword());
    }

    /**
     * Rehash the user's password if required and supported.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @param  bool  $force
     * @return void
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
        // Not implemented for this custom provider
    }
}