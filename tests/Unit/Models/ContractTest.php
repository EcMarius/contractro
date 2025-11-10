<?php

namespace Tests\Unit\Models;

use App\Models\Contract;
use App\Models\ContractSignature;
use App\Models\ContractTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_unique_contract_number_on_creation()
    {
        $user = User::factory()->create();

        $contract = Contract::factory()->create([
            'user_id' => $user->id,
            'contract_number' => null,
        ]);

        $this->assertNotNull($contract->contract_number);
        $this->assertMatchesRegularExpression('/^CONT-\d{4}-\d{4}$/', $contract->contract_number);
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $contract->user);
        $this->assertEquals($user->id, $contract->user->id);
    }

    /** @test */
    public function it_belongs_to_a_template()
    {
        $template = ContractTemplate::factory()->create();
        $contract = Contract::factory()->create(['template_id' => $template->id]);

        $this->assertInstanceOf(ContractTemplate::class, $contract->template);
        $this->assertEquals($template->id, $contract->template->id);
    }

    /** @test */
    public function it_has_many_signatures()
    {
        $contract = Contract::factory()->create();
        $signatures = ContractSignature::factory()->count(3)->create([
            'contract_id' => $contract->id,
        ]);

        $this->assertCount(3, $contract->signatures);
        $this->assertInstanceOf(ContractSignature::class, $contract->signatures->first());
    }

    /** @test */
    public function it_can_check_signature_status()
    {
        $contract = Contract::factory()->create(['status' => 'pending_signature']);

        // Create 3 signatures, 2 signed, 1 pending
        ContractSignature::factory()->count(2)->create([
            'contract_id' => $contract->id,
            'status' => 'signed',
        ]);

        ContractSignature::factory()->create([
            'contract_id' => $contract->id,
            'status' => 'pending',
        ]);

        $contract->checkSignatureStatus();

        $this->assertEquals('partially_signed', $contract->fresh()->status);
    }

    /** @test */
    public function it_updates_to_signed_when_all_signatures_complete()
    {
        $contract = Contract::factory()->create(['status' => 'pending_signature']);

        ContractSignature::factory()->count(3)->create([
            'contract_id' => $contract->id,
            'status' => 'signed',
        ]);

        $contract->checkSignatureStatus();

        $freshContract = $contract->fresh();
        $this->assertEquals('signed', $freshContract->status);
        $this->assertNotNull($freshContract->signed_at);
    }

    /** @test */
    public function it_can_process_variables_in_content()
    {
        $contract = Contract::factory()->create([
            'content' => 'Hello {{name}}, your contract value is {{value}}.',
        ]);

        $processed = $contract->processVariables([
            'name' => 'John Doe',
            'value' => '$10,000',
        ]);

        $this->assertEquals('Hello John Doe, your contract value is $10,000.', $processed);
    }

    /** @test */
    public function it_can_create_versions()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contract = Contract::factory()->create(['content' => 'Original content']);

        $version = $contract->createVersion('Initial version');

        $this->assertCount(1, $contract->versions);
        $this->assertEquals(1, $version->version_number);
        $this->assertEquals('Original content', $version->content);
        $this->assertEquals('Initial version', $version->change_summary);
    }

    /** @test */
    public function it_formats_contract_value_correctly()
    {
        $contract = Contract::factory()->create([
            'contract_value' => 1234.56,
            'currency' => 'USD',
        ]);

        $this->assertEquals('USD 1,234.56', $contract->formatted_contract_value);
    }

    /** @test */
    public function it_calculates_days_until_expiration()
    {
        $contract = Contract::factory()->create([
            'expires_at' => now()->addDays(15),
        ]);

        $this->assertEquals(15, $contract->days_until_expiration);
    }

    /** @test */
    public function it_checks_if_contract_is_expired()
    {
        $expiredContract = Contract::factory()->create([
            'expires_at' => now()->subDays(1),
        ]);

        $activeContract = Contract::factory()->create([
            'expires_at' => now()->addDays(30),
        ]);

        $this->assertTrue($expiredContract->is_expired);
        $this->assertFalse($activeContract->is_expired);
    }

    /** @test */
    public function it_checks_if_contract_is_expiring_soon()
    {
        $expiringSoon = Contract::factory()->create([
            'expires_at' => now()->addDays(15),
        ]);

        $notExpiringSoon = Contract::factory()->create([
            'expires_at' => now()->addDays(60),
        ]);

        $this->assertTrue($expiringSoon->is_expiring_soon);
        $this->assertFalse($notExpiringSoon->is_expiring_soon);
    }

    /** @test */
    public function it_can_check_approval_status()
    {
        $contract = Contract::factory()->create();

        // Contract with no approvals
        $contract->checkApprovalStatus();
        $this->assertEquals('not_required', $contract->fresh()->approval_status);
    }

    /** @test */
    public function it_checks_if_needs_approval_based_on_value()
    {
        setting(['contracts.high_value_threshold' => 10000]);

        $highValue = Contract::factory()->create(['contract_value' => 15000]);
        $lowValue = Contract::factory()->create(['contract_value' => 5000]);

        $this->assertTrue($highValue->needsApproval());
        $this->assertFalse($lowValue->needsApproval());
    }

    /** @test */
    public function it_scopes_contracts_by_status()
    {
        Contract::factory()->create(['status' => 'draft']);
        Contract::factory()->create(['status' => 'draft']);
        Contract::factory()->create(['status' => 'signed']);

        $this->assertCount(2, Contract::draft()->get());
        $this->assertCount(1, Contract::signed()->get());
    }

    /** @test */
    public function it_scopes_expiring_soon_contracts()
    {
        Contract::factory()->create(['expires_at' => now()->addDays(15)]);
        Contract::factory()->create(['expires_at' => now()->addDays(20)]);
        Contract::factory()->create(['expires_at' => now()->addDays(60)]);

        $expiringSoon = Contract::expiringSoon(30)->get();

        $this->assertCount(2, $expiringSoon);
    }

    /** @test */
    public function it_can_be_viewed_by_owner()
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($contract->canBeViewedBy($user));
    }

    /** @test */
    public function it_can_be_edited_only_when_draft()
    {
        $user = User::factory()->create();

        $draftContract = Contract::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        $signedContract = Contract::factory()->create([
            'user_id' => $user->id,
            'status' => 'signed',
        ]);

        $this->assertTrue($draftContract->canBeEditedBy($user));
        $this->assertFalse($signedContract->canBeEditedBy($user));
    }
}
