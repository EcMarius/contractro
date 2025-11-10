<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractVersion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContractPDFService
{
    /**
     * Default storage disk for PDFs
     */
    protected string $defaultDisk;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->defaultDisk = config('filesystems.contracts_disk', 'local');
    }

    /**
     * Generate PDF from contract
     */
    public function generatePDF(Contract $contract, array $options = []): string
    {
        $pdf = PDF::loadView('contracts.pdf', [
            'contract' => $contract,
            'options' => $options,
        ]);

        // Set options
        $pdf->setPaper($options['paper'] ?? 'a4', $options['orientation'] ?? 'portrait');

        if (!empty($options['margins'])) {
            $pdf->setOptions([
                'margin_top' => $options['margins']['top'] ?? 10,
                'margin_right' => $options['margins']['right'] ?? 10,
                'margin_bottom' => $options['margins']['bottom'] ?? 10,
                'margin_left' => $options['margins']['left'] ?? 10,
            ]);
        }

        return $pdf->output();
    }

    /**
     * Save PDF to storage
     */
    public function savePDF(Contract $contract, array $options = []): string
    {
        $pdfContent = $this->generatePDF($contract, $options);

        $filename = $this->generateFilename($contract);
        $path = "contracts/pdf/{$contract->id}/{$filename}";

        Storage::disk($options['disk'] ?? 'local')->put($path, $pdfContent);

        return $path;
    }

    /**
     * Download PDF
     */
    public function downloadPDF(Contract $contract, array $options = [])
    {
        $pdf = PDF::loadView('contracts.pdf', [
            'contract' => $contract,
            'options' => $options,
        ]);

        $filename = $this->generateFilename($contract);

        return $pdf->download($filename);
    }

    /**
     * Stream PDF
     */
    public function streamPDF(Contract $contract, array $options = [])
    {
        $pdf = PDF::loadView('contracts.pdf', [
            'contract' => $contract,
            'options' => $options,
        ]);

        return $pdf->stream($this->generateFilename($contract));
    }

    /**
     * Generate filename for PDF
     */
    private function generateFilename(Contract $contract): string
    {
        $number = $contract->contract_number;
        $title = str_replace(' ', '_', $contract->title);
        $date = now()->format('Y-m-d');

        return "{$number}_{$title}_{$date}.pdf";
    }

    /**
     * Generate PDF with signatures
     */
    public function generateSignedPDF(Contract $contract, array $options = []): string
    {
        $options['show_signatures'] = true;

        return $this->generatePDF($contract, $options);
    }

    /**
     * Generate certificate of completion
     */
    public function generateCertificate(Contract $contract): string
    {
        $pdf = PDF::loadView('contracts.certificate', [
            'contract' => $contract,
        ]);

        return $pdf->output();
    }

    /**
     * Save PDF with version tracking
     */
    public function savePDFWithVersion(Contract $contract, array $options = []): array
    {
        $pdfContent = $this->generatePDF($contract, $options);

        // Determine version number
        $versionNumber = $contract->versions()->max('version_number') + 1;

        // Generate filename with version
        $filename = $this->generateVersionedFilename($contract, $versionNumber);
        $path = "contracts/pdf/{$contract->id}/v{$versionNumber}/{$filename}";

        // Choose storage disk
        $disk = $options['disk'] ?? $this->defaultDisk;

        // Encrypt if requested
        if ($options['encrypt'] ?? false) {
            $pdfContent = Crypt::encryptString($pdfContent);
            $path .= '.enc';
        }

        // Save to storage
        Storage::disk($disk)->put($path, $pdfContent);

        // Also upload to cloud if enabled
        if (($options['upload_to_cloud'] ?? true) && $disk !== 's3') {
            $this->uploadToCloud($contract, $path, $pdfContent);
        }

        // Create version record
        $version = ContractVersion::create([
            'contract_id' => $contract->id,
            'version_number' => $versionNumber,
            'content' => $contract->content,
            'changed_by' => auth()->id(),
            'change_summary' => $options['change_summary'] ?? 'PDF generated',
            'pdf_path' => $path,
            'metadata' => [
                'disk' => $disk,
                'encrypted' => $options['encrypt'] ?? false,
                'file_size' => strlen($pdfContent),
                'generated_at' => now()->toIso8601String(),
            ],
        ]);

        return [
            'path' => $path,
            'version' => $version,
            'url' => $this->getSecureUrl($contract, $path, $disk),
        ];
    }

    /**
     * Upload PDF to cloud storage (S3, Google Drive, etc.)
     */
    protected function uploadToCloud(Contract $contract, string $localPath, string $content): ?string
    {
        if (!config('filesystems.disks.s3.key')) {
            return null; // S3 not configured
        }

        try {
            $cloudPath = "contracts/{$contract->id}/" . basename($localPath);
            Storage::disk('s3')->put($cloudPath, $content, 'private');

            // Store cloud path in contract metadata
            $metadata = $contract->metadata ?? [];
            $metadata['cloud_storage'] = [
                'provider' => 's3',
                'path' => $cloudPath,
                'uploaded_at' => now()->toIso8601String(),
            ];
            $contract->update(['metadata' => $metadata]);

            return $cloudPath;
        } catch (\Exception $e) {
            \Log::error('Failed to upload contract to cloud storage', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate secure, expiring URL for PDF access
     */
    public function getSecureUrl(Contract $contract, string $path, string $disk = null, int $expiresInMinutes = 60): string
    {
        $disk = $disk ?? $this->defaultDisk;

        // For S3, use native temporary URLs
        if ($disk === 's3') {
            return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes($expiresInMinutes));
        }

        // For local storage, generate signed route
        return route('contracts.pdf.secure', [
            'contract' => $contract->id,
            'token' => $this->generateSecureToken($contract, $path),
            'expires' => now()->addMinutes($expiresInMinutes)->timestamp,
        ]);
    }

    /**
     * Generate secure token for PDF access
     */
    protected function generateSecureToken(Contract $contract, string $path): string
    {
        return hash_hmac('sha256', $contract->id . $path . auth()->id(), config('app.key'));
    }

    /**
     * Verify secure token
     */
    public function verifySecureToken(Contract $contract, string $path, string $token): bool
    {
        return hash_equals($this->generateSecureToken($contract, $path), $token);
    }

    /**
     * Get PDF from storage (handles encryption)
     */
    public function getPDFFromStorage(string $path, string $disk = null, bool $encrypted = false): string
    {
        $disk = $disk ?? $this->defaultDisk;
        $content = Storage::disk($disk)->get($path);

        if ($encrypted) {
            $content = Crypt::decryptString($content);
        }

        return $content;
    }

    /**
     * Archive old contract PDFs
     */
    public function archiveOldVersions(Contract $contract, int $keepLatestN = 3): int
    {
        $versions = $contract->versions()
            ->orderBy('version_number', 'desc')
            ->skip($keepLatestN)
            ->get();

        $archived = 0;

        foreach ($versions as $version) {
            if ($version->pdf_path) {
                $disk = $version->metadata['disk'] ?? $this->defaultDisk;

                // Move to archive location
                $archivePath = str_replace('contracts/pdf/', 'contracts/archive/', $version->pdf_path);

                if (Storage::disk($disk)->exists($version->pdf_path)) {
                    Storage::disk($disk)->move($version->pdf_path, $archivePath);
                    $version->update(['pdf_path' => $archivePath]);
                    $archived++;
                }
            }
        }

        return $archived;
    }

    /**
     * Delete old archived PDFs based on retention policy
     */
    public function deleteExpiredArchives(int $retentionDays = null): int
    {
        $retentionDays = $retentionDays ?? (int) setting('contracts.data_retention_days', 2555);

        if ($retentionDays === 0) {
            return 0; // Indefinite retention
        }

        $cutoffDate = now()->subDays($retentionDays);

        $expiredVersions = ContractVersion::where('created_at', '<', $cutoffDate)
            ->whereNotNull('pdf_path')
            ->where('pdf_path', 'like', '%/archive/%')
            ->get();

        $deleted = 0;

        foreach ($expiredVersions as $version) {
            $disk = $version->metadata['disk'] ?? $this->defaultDisk;

            if (Storage::disk($disk)->exists($version->pdf_path)) {
                Storage::disk($disk)->delete($version->pdf_path);
                $version->update(['pdf_path' => null]);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Generate versioned filename
     */
    private function generateVersionedFilename(Contract $contract, int $version): string
    {
        $number = $contract->contract_number;
        $title = Str::slug($contract->title);
        $date = now()->format('Y-m-d');

        return "{$number}_{$title}_v{$version}_{$date}.pdf";
    }

    /**
     * Get all versions of a contract PDF
     */
    public function getAllVersions(Contract $contract): array
    {
        return $contract->versions()
            ->whereNotNull('pdf_path')
            ->orderBy('version_number', 'desc')
            ->get()
            ->map(function ($version) use ($contract) {
                $disk = $version->metadata['disk'] ?? $this->defaultDisk;

                return [
                    'version_number' => $version->version_number,
                    'created_at' => $version->created_at,
                    'changed_by' => $version->changedBy?->name,
                    'change_summary' => $version->change_summary,
                    'file_size' => $version->metadata['file_size'] ?? null,
                    'url' => $this->getSecureUrl($contract, $version->pdf_path, $disk),
                    'is_encrypted' => $version->metadata['encrypted'] ?? false,
                ];
            })
            ->toArray();
    }

    /**
     * Bulk export multiple contracts as ZIP
     */
    public function bulkExportAsZip(array $contractIds): string
    {
        $zip = new \ZipArchive();
        $zipFilename = 'contracts_export_' . now()->format('Y-m-d_His') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFilename);

        // Ensure temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($contractIds as $contractId) {
                $contract = Contract::find($contractId);

                if ($contract) {
                    $pdfContent = $this->generatePDF($contract);
                    $filename = $this->generateFilename($contract);
                    $zip->addFromString($filename, $pdfContent);
                }
            }

            $zip->close();
        }

        return $zipPath;
    }
}
