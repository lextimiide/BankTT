<?php

namespace App\Auth;

use App\Models\Admin;
use App\Models\Client;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Log;

class MultiTableUserProvider implements UserProvider
{
    /**
     * The models to search in order of priority
     */
    protected array $models = [
        Admin::class,
        Client::class,
    ];

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        Log::info("MultiTableUserProvider: Searching for user with ID: {$identifier}");

        foreach ($this->models as $modelClass) {
            Log::info("MultiTableUserProvider: Checking model: {$modelClass}");

            // Try to find by ID, handling both UUID and integer formats
            $user = $modelClass::where('id', $identifier)->first();

            if ($user) {
                Log::info("MultiTableUserProvider: Found user in {$modelClass}: {$user->id}");
                return $user;
            }
        }

        Log::warning("MultiTableUserProvider: User not found with ID: {$identifier}");
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
        foreach ($this->models as $modelClass) {
            $user = $modelClass::where('id', $identifier)
                               ->where('remember_token', $token)
                               ->first();

            if ($user) {
                return $user;
            }
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

        foreach ($this->models as $modelClass) {
            $user = $modelClass::where('email', $credentials['email'])->first();

            if ($user && $this->validateCredentials($user, $credentials)) {
                return $user;
            }
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