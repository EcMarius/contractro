<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ContractReportService
{
    protected ContractAnalyticsService $analytics;

    public function __construct(ContractAnalyticsService $analytics)
    {
        $this->analytics = $analytics;
    }

    /**
     * Generate PDF report
     */
    public function generatePDFReport(array $options = []): string
    {
        $reportData = $this->analytics->generateReport($options);

        $pdf = PDF::loadView('reports.contracts', [
            'data' => $reportData,
            'title' => $options['title'] ?? 'Contract Analytics Report',
            'generated_at' => now(),
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    /**
     * Save PDF report to storage
     */
    public function savePDFReport(array $options = []): string
    {
        $pdfContent = $this->generatePDFReport($options);

        $filename = 'contract-report-' . now()->format('Y-m-d-His') . '.pdf';
        $path = "reports/contracts/{$filename}";

        Storage::disk('local')->put($path, $pdfContent);

        return $path;
    }

    /**
     * Generate CSV export
     */
    public function generateCSVExport(string $type = 'contracts', array $filters = []): string
    {
        $data = match ($type) {
            'contracts' => $this->getContractsData($filters),
            'signatures' => $this->getSignaturesData($filters),
            'templates' => $this->getTemplatesData($filters),
            'revenue' => $this->getRevenueData($filters),
            default => [],
        };

        return $this->arrayToCSV($data);
    }

    /**
     * Get contracts data for export
     */
    protected function getContractsData(array $filters): array
    {
        $query = \App\Models\Contract::with(['user', 'template']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $contracts = $query->get();

        $data = [
            ['Contract Number', 'Title', 'Status', 'Value', 'Created At', 'Signed At', 'Template', 'User'],
        ];

        foreach ($contracts as $contract) {
            $data[] = [
                $contract->contract_number,
                $contract->title,
                $contract->status,
                $contract->contract_value ?? 0,
                $contract->created_at->format('Y-m-d H:i:s'),
                $contract->signed_at?->format('Y-m-d H:i:s') ?? 'N/A',
                $contract->template?->name ?? 'N/A',
                $contract->user?->name ?? 'N/A',
            ];
        }

        return $data;
    }

    /**
     * Get signatures data for export
     */
    protected function getSignaturesData(array $filters): array
    {
        $query = \App\Models\ContractSignature::with(['contract', 'approver']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        $signatures = $query->get();

        $data = [
            ['Contract', 'Signer Name', 'Signer Email', 'Status', 'Requested At', 'Signed At', 'Signature Type'],
        ];

        foreach ($signatures as $signature) {
            $data[] = [
                $signature->contract?->title ?? 'N/A',
                $signature->signer_name,
                $signature->signer_email,
                $signature->status,
                $signature->requested_at->format('Y-m-d H:i:s'),
                $signature->signed_at?->format('Y-m-d H:i:s') ?? 'N/A',
                $signature->signature_type ?? 'N/A',
            ];
        }

        return $data;
    }

    /**
     * Get templates data for export
     */
    protected function getTemplatesData(array $filters): array
    {
        $templates = \App\Models\ContractTemplate::withCount('contracts')->get();

        $data = [
            ['Name', 'Category', 'Usage Count', 'Contracts Created', 'Is Public', 'Created At'],
        ];

        foreach ($templates as $template) {
            $data[] = [
                $template->name,
                $template->category ?? 'N/A',
                $template->usage_count,
                $template->contracts_count,
                $template->is_public ? 'Yes' : 'No',
                $template->created_at->format('Y-m-d H:i:s'),
            ];
        }

        return $data;
    }

    /**
     * Get revenue data for export
     */
    protected function getRevenueData(array $filters): array
    {
        $months = $filters['months'] ?? 12;
        $revenueData = $this->analytics->getMonthlyRevenue($months);

        $data = [
            ['Month', 'Revenue', 'Contracts Signed'],
        ];

        foreach ($revenueData as $month) {
            $data[] = [
                $month['month'],
                number_format($month['revenue'], 2),
                \App\Models\Contract::whereIn('status', ['signed', 'completed'])
                    ->whereYear('signed_at', substr($month['month'], 0, 4))
                    ->whereMonth('signed_at', substr($month['month'], 5, 2))
                    ->count(),
            ];
        }

        return $data;
    }

    /**
     * Convert array to CSV string
     */
    protected function arrayToCSV(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Save CSV export to storage
     */
    public function saveCSVExport(string $type, array $filters = []): string
    {
        $csvContent = $this->generateCSVExport($type, $filters);

        $filename = "contract-{$type}-export-" . now()->format('Y-m-d-His') . '.csv';
        $path = "exports/contracts/{$filename}";

        Storage::disk('local')->put($path, $csvContent);

        return $path;
    }

    /**
     * Generate scheduled report and email it
     */
    public function generateScheduledReport(array $recipients, string $frequency = 'weekly'): void
    {
        $reportData = $this->analytics->generateReport([
            'date_range' => $this->getDateRangeForFrequency($frequency),
        ]);

        $pdfPath = $this->savePDFReport([
            'title' => ucfirst($frequency) . ' Contract Report',
        ]);

        // Send email with PDF attachment
        foreach ($recipients as $recipient) {
            \Illuminate\Support\Facades\Mail::send('emails.contract-report', [
                'data' => $reportData,
                'frequency' => $frequency,
            ], function ($message) use ($recipient, $pdfPath, $frequency) {
                $message->to($recipient)
                    ->subject(ucfirst($frequency) . ' Contract Analytics Report')
                    ->attach(storage_path('app/' . $pdfPath));
            });
        }
    }

    /**
     * Get date range based on frequency
     */
    protected function getDateRangeForFrequency(string $frequency): array
    {
        return match ($frequency) {
            'daily' => [now()->subDay()->startOfDay(), now()->endOfDay()],
            'weekly' => [now()->subWeek()->startOfWeek(), now()->endOfWeek()],
            'monthly' => [now()->subMonth()->startOfMonth(), now()->endOfMonth()],
            default => [now()->subWeek()->startOfWeek(), now()->endOfWeek()],
        };
    }
}
