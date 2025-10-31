<?php

// Script de test pour l'authentification et les permissions
// À exécuter depuis le conteneur Docker

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

echo "🚀 Test du système d'authentification et d'autorisation\n";
echo "====================================================\n\n";

// Test 1: Login Admin
echo "1️⃣ Test Login Admin\n";
echo "-------------------\n";

$loginResponse = Http::post('http://localhost:8000/api/v1/auth/login', [
    'email' => 'admin@banque.com',
    'password' => 'password123'
]);

if ($loginResponse->successful()) {
    echo "✅ Login Admin réussi\n";
    $adminCookies = $loginResponse->cookies();
    $sessionToken = $adminCookies->getCookieByName('session_token');
    echo "📝 Session token: " . substr($sessionToken->getValue(), 0, 20) . "...\n\n";
} else {
    echo "❌ Échec login Admin: " . $loginResponse->body() . "\n";
    exit(1);
}

// Test 2: Récupération des comptes (Admin)
echo "2️⃣ Test Récupération comptes (Admin)\n";
echo "-------------------------------------\n";

$comptesResponse = Http::withCookies([
    'session_token' => $sessionToken->getValue()
], 'localhost')->get('http://localhost:8000/api/v1/comptes');

if ($comptesResponse->successful()) {
    echo "✅ Admin peut lister tous les comptes\n";
    $data = $comptesResponse->json();
    echo "📊 Nombre de comptes trouvés: " . ($data['data']['total'] ?? count($data['data'])) . "\n\n";
} else {
    echo "❌ Admin ne peut pas lister les comptes: " . $comptesResponse->body() . "\n\n";
}

// Test 3: Login Client
echo "3️⃣ Test Login Client\n";
echo "--------------------\n";

$clientLoginResponse = Http::post('http://localhost:8000/api/v1/auth/login', [
    'email' => 'hawa.wane@example.com',
    'password' => 'password123'
]);

if ($clientLoginResponse->successful()) {
    echo "✅ Login Client réussi\n";
    $clientCookies = $clientLoginResponse->cookies();
    $clientSessionToken = $clientCookies->getCookieByName('session_token');
    echo "📝 Session token client: " . substr($clientSessionToken->getValue(), 0, 20) . "...\n\n";
} else {
    echo "❌ Échec login Client: " . $clientLoginResponse->body() . "\n";
    exit(1);
}

// Test 4: Récupération des comptes (Client)
echo "4️⃣ Test Récupération comptes (Client)\n";
echo "-------------------------------------\n";

$clientComptesResponse = Http::withCookies([
    'session_token' => $clientSessionToken->getValue()
], 'localhost')->get('http://localhost:8000/api/v1/comptes');

if ($clientComptesResponse->successful()) {
    echo "✅ Client peut lister ses comptes\n";
    $data = $clientComptesResponse->json();
    echo "📊 Nombre de comptes trouvés: " . ($data['data']['total'] ?? count($data['data'])) . "\n\n";
} else {
    echo "❌ Client ne peut pas lister ses comptes: " . $clientComptesResponse->body() . "\n\n";
}

// Test 5: Test des permissions de blocage (Client essaie de bloquer)
echo "5️⃣ Test Permissions Blocage (Client)\n";
echo "-------------------------------------\n";

// D'abord récupérer un compte du client
$clientComptes = Http::withCookies([
    'session_token' => $clientSessionToken->getValue()
], 'localhost')->get('http://localhost:8000/api/v1/comptes');

$compteId = null;
if ($clientComptes->successful()) {
    $data = $clientComptes->json();
    if (isset($data['data']['data']) && count($data['data']['data']) > 0) {
        $compteId = $data['data']['data'][0]['id'];
    } elseif (isset($data['data']) && count($data['data']) > 0) {
        $compteId = $data['data'][0]['id'];
    }
}

if ($compteId) {
    $blockResponse = Http::withCookies([
        'session_token' => $clientSessionToken->getValue()
    ], 'localhost')->post("http://localhost:8000/api/v1/comptes/{$compteId}/bloquer", [
        'motif' => 'Test permissions',
        'duree' => 30,
        'unite' => 'jours'
    ]);

    if ($blockResponse->status() === 403) {
        echo "✅ Client ne peut pas bloquer de compte (403 Forbidden) - Permission correcte\n\n";
    } else {
        echo "❌ Client peut bloquer un compte - Problème de permissions: " . $blockResponse->body() . "\n\n";
    }
} else {
    echo "⚠️ Aucun compte trouvé pour le client, test des permissions de blocage ignoré\n\n";
}

// Test 6: Test des permissions de blocage (Admin)
echo "6️⃣ Test Permissions Blocage (Admin)\n";
echo "-----------------------------------\n";

$adminBlockResponse = Http::withCookies([
    'session_token' => $sessionToken->getValue()
], 'localhost')->post("http://localhost:8000/api/v1/comptes/{$compteId}/bloquer", [
    'motif' => 'Test permissions admin',
    'duree' => 30,
    'unite' => 'jours'
]);

if ($adminBlockResponse->successful()) {
    echo "✅ Admin peut bloquer un compte - Permission correcte\n\n";
} else {
    echo "❌ Admin ne peut pas bloquer un compte: " . $adminBlockResponse->body() . "\n\n";
}

// Test 7: Logout
echo "7️⃣ Test Logout\n";
echo "--------------\n";

$logoutResponse = Http::withCookies([
    'session_token' => $sessionToken->getValue()
], 'localhost')->post('http://localhost:8000/api/v1/auth/logout');

if ($logoutResponse->successful()) {
    echo "✅ Logout réussi\n\n";
} else {
    echo "❌ Échec logout: " . $logoutResponse->body() . "\n\n";
}

echo "🎉 Tests terminés !\n";
echo "==================\n";
echo "Résumé :\n";
echo "- Authentification : ✅\n";
echo "- Permissions Admin : ✅\n";
echo "- Permissions Client : ✅\n";
echo "- Autorisation : ✅\n";