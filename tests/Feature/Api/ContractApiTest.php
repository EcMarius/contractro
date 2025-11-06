<?php

namespace Tests\Feature\Api;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContractApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_list_contracts()
    {
        Contract::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/contracts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'contract_number',
                        'title',
                        'status',
                        'created_at',
                    ],
                ],
                'meta',
            ]);
    }

    /** @test */
    public function it_can_create_a_contract()
    {
        $template = ContractTemplate::factory()->create();

        $contractData = [
            'title' => 'New Service Agreement',
            'description' => 'Test contract description',
            'content' => 'Contract content goes here',
            'template_id' => $template->id,
            'status' => 'draft',
            'contract_value' => 5000.00,
            'currency' => 'USD',
        ];

        $response = $this->postJson('/api/contracts', $contractData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'title' => 'New Service Agreement',
                'status' => 'draft',
            ]);

        $this->assertDatabaseHas('contracts', [
            'title' => 'New Service Agreement',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_on_creation()
    {
        $response = $this->postJson('/api/contracts', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content']);
    }

    /** @test */
    public function it_can_show_a_contract()
    {
        $contract = Contract::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/contracts/{$contract->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $contract->id,
                'title' => $contract->title,
            ]);
    }

    /** @test */
    public function it_cannot_show_another_users_contract()
    {
        $otherUser = User::factory()->create();
        $contract = Contract::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/contracts/{$contract->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_update_a_contract()
    {
        $contract = Contract::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->putJson("/api/contracts/{$contract->id}", [
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Updated Title']);

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'title' => 'Updated Title',
        ]);
    }

    /** @test */
    public function it_cannot_update_signed_contract()
    {
        $contract = Contract::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'signed',
        ]);

        $response = $this->putJson("/api/contracts/{$contract->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_delete_a_draft_contract()
    {
        $contract = Contract::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->deleteJson("/api/contracts/{$contract->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('contracts', ['id' => $contract->id]);
    }

    /** @test */
    public function it_can_duplicate_a_contract()
    {
        $original = Contract::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Original Contract',
        ]);

        $response = $this->postJson("/api/contracts/{$original->id}/duplicate");

        $response->assertStatus(201)
            ->assertJsonFragment([
                'title' => 'Original Contract (Copy)',
                'status' => 'draft',
            ]);

        $this->assertDatabaseCount('contracts', 2);
    }

    /** @test */
    public function it_can_generate_pdf()
    {
        $contract = Contract::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/contracts/{$contract->id}/pdf");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }

    /** @test */
    public function it_can_send_for_signature()
    {
        $contract = Contract::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->postJson("/api/contracts/{$contract->id}/send-for-signature", [
            'signers' => [
                [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'role' => 'Client',
                ],
                [
                    'name' => 'Jane Smith',
                    'email' => 'jane@example.com',
                    'role' => 'Witness',
                ],
            ],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'status' => 'pending_signature',
        ]);

        $this->assertDatabaseCount('contract_signatures', 2);
    }

    /** @test */
    public function it_can_filter_contracts_by_status()
    {
        Contract::factory()->create(['user_id' => $this->user->id, 'status' => 'draft']);
        Contract::factory()->create(['user_id' => $this->user->id, 'status' => 'draft']);
        Contract::factory()->create(['user_id' => $this->user->id, 'status' => 'signed']);

        $response = $this->getJson('/api/contracts?status=draft');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(2, $data);
    }

    /** @test */
    public function it_can_search_contracts()
    {
        Contract::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Service Agreement',
        ]);

        Contract::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Employment Contract',
        ]);

        $response = $this->getJson('/api/contracts?search=Service');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('Service Agreement', $data[0]['title']);
    }

    /** @test */
    public function it_respects_pagination()
    {
        Contract::factory()->count(25)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/contracts?per_page=10');

        $response->assertStatus(200);
        $data = $response->json('data');
        $meta = $response->json('meta');

        $this->assertCount(10, $data);
        $this->assertEquals(25, $meta['total']);
    }

    /** @test */
    public function it_requires_authentication()
    {
        Sanctum::actingAs(null); // Remove authentication

        $response = $this->getJson('/api/contracts');

        $response->assertStatus(401);
    }
}
