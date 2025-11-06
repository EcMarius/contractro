<?php

namespace Tests\Unit\Services;

use App\Models\Contract;
use App\Models\User;
use App\Services\ContractPDFService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractPDFServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ContractPDFService $pdfService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdfService = app(ContractPDFService::class);
        Storage::fake('local');
    }

    /** @test */
    public function it_generates_pdf_from_contract()
    {
        $contract = Contract::factory()->create([
            'title' => 'Test Contract',
            'content' => 'This is test content',
        ]);

        $pdf = $this->pdfService->generatePDF($contract);

        $this->assertNotEmpty($pdf);
        $this->assertStringContainsString('%PDF', $pdf); // PDF header
    }

    /** @test */
    public function it_saves_pdf_to_storage()
    {
        $contract = Contract::factory()->create();

        $path = $this->pdfService->savePDF($contract);

        Storage::disk('local')->assertExists($path);
        $this->assertStringContainsString('contracts/pdf/', $path);
    }

    /** @test */
    public function it_generates_filename_with_contract_details()
    {
        $contract = Contract::factory()->create([
            'contract_number' => 'CONT-2025-0001',
            'title' => 'Service Agreement',
        ]);

        $path = $this->pdfService->savePDF($contract);
        $filename = basename($path);

        $this->assertStringContainsString('CONT-2025-0001', $filename);
        $this->assertStringContainsString('Service_Agreement', $filename);
        $this->assertStringEndsWith('.pdf', $filename);
    }

    /** @test */
    public function it_saves_pdf_with_version_tracking()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contract = Contract::factory()->create();

        $result = $this->pdfService->savePDFWithVersion($contract);

        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('url', $result);

        $this->assertEquals(1, $result['version']->version_number);
        Storage::disk('local')->assertExists($result['path']);
    }

    /** @test */
    public function it_increments_version_numbers()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contract = Contract::factory()->create();

        // Create first version
        $v1 = $this->pdfService->savePDFWithVersion($contract);
        $this->assertEquals(1, $v1['version']->version_number);

        // Create second version
        $v2 = $this->pdfService->savePDFWithVersion($contract);
        $this->assertEquals(2, $v2['version']->version_number);
    }

    /** @test */
    public function it_can_archive_old_versions()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contract = Contract::factory()->create();

        // Create 5 versions
        for ($i = 1; $i <= 5; $i++) {
            $this->pdfService->savePDFWithVersion($contract);
        }

        // Archive keeping only latest 2
        $archived = $this->pdfService->archiveOldVersions($contract, 2);

        $this->assertEquals(3, $archived); // Should archive 3 old versions
    }

    /** @test */
    public function it_generates_secure_urls()
    {
        $contract = Contract::factory()->create();
        $path = 'contracts/pdf/1/test.pdf';

        $url = $this->pdfService->getSecureUrl($contract, $path);

        $this->assertStringContainsString('contracts.pdf.secure', $url);
        $this->assertStringContainsString('token=', $url);
        $this->assertStringContainsString('expires=', $url);
    }

    /** @test */
    public function it_verifies_secure_tokens()
    {
        $contract = Contract::factory()->create();
        $path = 'contracts/pdf/1/test.pdf';

        $url = $this->pdfService->getSecureUrl($contract, $path);

        // Extract token from URL
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        $token = $params['token'];

        $this->assertTrue($this->pdfService->verifySecureToken($contract, $path, $token));
        $this->assertFalse($this->pdfService->verifySecureToken($contract, $path, 'invalid-token'));
    }

    /** @test */
    public function it_gets_all_versions_for_contract()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contract = Contract::factory()->create();

        // Create 3 versions
        for ($i = 1; $i <= 3; $i++) {
            $this->pdfService->savePDFWithVersion($contract, [
                'change_summary' => "Version {$i}",
            ]);
        }

        $versions = $this->pdfService->getAllVersions($contract);

        $this->assertCount(3, $versions);
        $this->assertEquals(3, $versions[0]['version_number']); // Latest first
        $this->assertEquals(1, $versions[2]['version_number']);
    }

    /** @test */
    public function it_creates_bulk_zip_export()
    {
        $contracts = Contract::factory()->count(3)->create();
        $contractIds = $contracts->pluck('id')->toArray();

        $zipPath = $this->pdfService->bulkExportAsZip($contractIds);

        $this->assertFileExists($zipPath);
        $this->assertStringContainsString('.zip', $zipPath);

        // Cleanup
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }
    }
}
