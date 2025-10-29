<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Compte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_comptes_returns_successful_response(): void
    {
        Compte::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/comptes');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        '*' => [
                            'id',
                            'numero_compte',
                            'type',
                            'solde_initial',
                            'devise',
                            'statut',
                            'client_id',
                            'created_at',
                            'updated_at',
                            'solde',
                            'client'
                        ]
                    ],
                    'timestamp',
                    'path',
                    'traceId'
                ]);
    }

    public function test_create_compte_with_new_client(): void
    {
        $compteData = [
            'type' => 'cheque',
            'soldeInitial' => 500000,
            'devise' => 'FCFA',
            'client' => [
                'titulaire' => 'John Doe',
                'email' => 'john.doe@example.com',
                'telephone' => '701234567',
                'adresse' => 'Dakar, Sénégal'
            ]
        ];

        $response = $this->postJson('/api/v1/comptes', $compteData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'numeroCompte',
                        'titulaire',
                        'type',
                        'solde',
                        'devise',
                        'dateCreation',
                        'statut',
                        'metadata'
                    ],
                    'timestamp',
                    'path',
                    'traceId'
                ]);

        $this->assertDatabaseHas('clients', [
            'email' => 'john.doe@example.com',
            'telephone' => '701234567'
        ]);

        $this->assertDatabaseHas('comptes', [
            'type' => 'cheque',
            'solde_initial' => 500000,
            'devise' => 'FCFA'
        ]);
    }

    public function test_create_compte_with_existing_client(): void
    {
        $client = Client::factory()->create();

        $compteData = [
            'type' => 'epargne',
            'soldeInitial' => 1000000,
            'devise' => 'FCFA',
            'client' => [
                'id' => $client->id
            ]
        ];

        $response = $this->postJson('/api/v1/comptes', $compteData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('comptes', [
            'client_id' => $client->id,
            'type' => 'epargne',
            'solde_initial' => 1000000
        ]);
    }

    public function test_create_compte_validation_fails(): void
    {
        $response = $this->postJson('/api/v1/comptes', [
            'type' => 'invalid_type',
            'soldeInitial' => 5000, // Below minimum
            'devise' => 'INVALID'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['type', 'soldeInitial', 'devise']);
    }

    public function test_create_compte_with_invalid_phone_fails(): void
    {
        $compteData = [
            'type' => 'cheque',
            'soldeInitial' => 500000,
            'devise' => 'FCFA',
            'client' => [
                'titulaire' => 'John Doe',
                'email' => 'john.doe@example.com',
                'telephone' => '123456789', // Invalid Senegalese phone
                'adresse' => 'Dakar, Sénégal'
            ]
        ];

        $response = $this->postJson('/api/v1/comptes', $compteData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors('client.telephone');
    }

    public function test_get_single_compte(): void
    {
        $compte = Compte::factory()->create();

        $response = $this->getJson("/api/v1/comptes/{$compte->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'id',
                    'numeroCompte',
                    'titulaire',
                    'type',
                    'solde',
                    'devise',
                    'dateCreation',
                    'statut',
                    'metadata'
                ]);
    }

    public function test_get_nonexistent_compte_returns_404(): void
    {
        $response = $this->getJson('/api/v1/comptes/nonexistent-id');

        $response->assertStatus(404);
    }

    public function test_compte_filtering_by_type(): void
    {
        Compte::factory()->create(['type' => 'cheque']);
        Compte::factory()->create(['type' => 'epargne']);

        $response = $this->getJson('/api/v1/comptes?type=cheque');

        $response->assertStatus(200);

        $data = $response->json('data.data');
        foreach ($data as $compte) {
            $this->assertEquals('cheque', $compte['type']);
        }
    }

    public function test_compte_pagination(): void
    {
        Compte::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/comptes?limit=10');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertCount(10, $data['data']['data']);
        $this->assertEquals(25, $data['data']['total']);
    }
}
