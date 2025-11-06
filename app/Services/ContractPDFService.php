<?php

namespace App\Services;

use App\Models\Contract;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ContractPDFService
{
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
                'margin_left' => $options['margins']['left'] => 10,
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
}
