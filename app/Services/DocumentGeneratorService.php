<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DocumentGeneratorService
{
    /**
     * Generate PDF for a contract
     *
     * @param Contract $contract
     * @return string Path to generated PDF
     */
    public function generateContractPDF(Contract $contract): string
    {
        $html = $this->generateContractHTML($contract);

        // TODO: When mPDF is installed, use:
        // $mpdf = new \Mpdf\Mpdf([
        //     'mode' => 'utf-8',
        //     'format' => 'A4',
        //     'margin_left' => 15,
        //     'margin_right' => 15,
        //     'margin_top' => 20,
        //     'margin_bottom' => 20,
        // ]);
        // $mpdf->WriteHTML($html);
        // $filename = 'contracts/' . $contract->contract_number . '.pdf';
        // $mpdf->Output(storage_path('app/public/' . $filename), 'F');

        // For now, just log and return placeholder
        Log::info('Contract PDF generation requested', ['contract_id' => $contract->id]);

        return 'contracts/placeholder.pdf';
    }

    /**
     * Generate PDF for an invoice
     *
     * @param Invoice $invoice
     * @return string Path to generated PDF
     */
    public function generateInvoicePDF(Invoice $invoice): string
    {
        $html = $this->generateInvoiceHTML($invoice);

        // TODO: When mPDF is installed, use similar approach as contract
        Log::info('Invoice PDF generation requested', ['invoice_id' => $invoice->id]);

        return 'invoices/placeholder.pdf';
    }

    /**
     * Generate HTML for contract
     *
     * @param Contract $contract
     * @return string
     */
    protected function generateContractHTML(Contract $contract): string
    {
        $company = $contract->company;
        $contractType = $contract->contractType;

        $html = '
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract ' . $contract->contract_number . '</title>
    <style>
        @page {
            margin: 2cm;
            @top-right {
                content: "Pagina " counter(page) " din " counter(pages);
                font-size: 9pt;
                color: #666;
            }
        }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 20px;
        }
        .company-info {
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 14pt;
            font-weight: bold;
            color: #0066cc;
        }
        .contract-title {
            font-size: 16pt;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            text-transform: uppercase;
        }
        .contract-number {
            text-align: center;
            font-size: 12pt;
            margin-bottom: 20px;
        }
        .content {
            text-align: justify;
            margin: 20px 0;
        }
        .signature-block {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-party {
            width: 45%;
            display: inline-block;
            vertical-align: top;
            margin-bottom: 30px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72pt;
            color: rgba(0, 0, 0, 0.1);
            z-index: -1;
        }
    </style>
</head>
<body>';

        // Add watermark for unsigned contracts
        if ($contract->status !== 'signed' && $contract->status !== 'active') {
            $html .= '<div class="watermark">NESEMNAT</div>';
        }

        // Header with company info
        $html .= '
    <div class="header">
        <div class="company-name">' . htmlspecialchars($company->name) . '</div>';

        if ($company->cui) {
            $html .= '<div>CUI: ' . htmlspecialchars($company->cui) . '</div>';
        }

        if ($company->reg_com) {
            $html .= '<div>Nr. Reg. Com.: ' . htmlspecialchars($company->reg_com) . '</div>';
        }

        if ($company->address) {
            $html .= '<div>' . htmlspecialchars($company->address) . '</div>';
        }

        if ($company->phone || $company->email) {
            $html .= '<div>';
            if ($company->phone) {
                $html .= 'Tel: ' . htmlspecialchars($company->phone);
            }
            if ($company->phone && $company->email) {
                $html .= ' | ';
            }
            if ($company->email) {
                $html .= 'Email: ' . htmlspecialchars($company->email);
            }
            $html .= '</div>';
        }

        $html .= '
    </div>';

        // Contract title and number
        $html .= '
    <div class="contract-title">' . htmlspecialchars($contract->title) . '</div>
    <div class="contract-number">Nr. ' . htmlspecialchars($contract->contract_number) . '</div>';

        // Contract details
        $html .= '<table><tbody>';

        if ($contractType) {
            $html .= '<tr><th>Tip Contract:</th><td>' . htmlspecialchars($contractType->name) . '</td></tr>';
        }

        if ($contract->start_date) {
            $html .= '<tr><th>Data Început:</th><td>' . $contract->start_date->format('d.m.Y') . '</td></tr>';
        }

        if ($contract->end_date) {
            $html .= '<tr><th>Data Sfârșit:</th><td>' . $contract->end_date->format('d.m.Y') . '</td></tr>';
        }

        if ($contract->value) {
            $html .= '<tr><th>Valoare:</th><td>' . number_format($contract->value, 2, ',', '.') . ' RON</td></tr>';
        }

        if ($contract->client_name) {
            $html .= '<tr><th>Client:</th><td>' . htmlspecialchars($contract->client_name) . '</td></tr>';
        }

        $html .= '</tbody></table>';

        // Contract content
        $html .= '<div class="content">' . nl2br(htmlspecialchars($contract->content)) . '</div>';

        // Signature blocks
        if ($contract->parties->count() > 0) {
            $html .= '<div class="signature-block">';
            $html .= '<h3>Părți Contractante:</h3>';

            foreach ($contract->parties as $party) {
                $html .= '
                <div class="signature-party">
                    <strong>' . htmlspecialchars($party->name) . '</strong><br>
                    Email: ' . htmlspecialchars($party->email) . '<br>
                    Tel: ' . htmlspecialchars($party->phone) . '<br>';

                if ($party->hasSigned()) {
                    $signature = $party->signatures()->where('code_verified', true)->first();
                    $html .= '<br>Semnat la: ' . $signature->signed_at->format('d.m.Y H:i') . '<br>';
                    $html .= 'IP: ' . $signature->ip_address . '<br>';

                    if ($signature->signature_image_path) {
                        // TODO: Embed signature image
                        $html .= '<div class="signature-line">Semnătură Electronică</div>';
                    }
                } else {
                    $html .= '<div class="signature-line">_____________________</div>';
                }

                $html .= '</div>';
            }

            $html .= '</div>';
        }

        $html .= '
</body>
</html>';

        return $html;
    }

    /**
     * Generate HTML for invoice (Romanian format)
     *
     * @param Invoice $invoice
     * @return string
     */
    protected function generateInvoiceHTML(Invoice $invoice): string
    {
        $company = $invoice->company;

        $html = '
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factură ' . $invoice->invoice_number . '</title>
    <style>
        @page {
            margin: 2cm;
        }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 20px;
        }
        .invoice-title {
            font-size: 24pt;
            font-weight: bold;
            color: #0066cc;
            text-align: center;
            margin-bottom: 10px;
        }
        .invoice-number {
            text-align: center;
            font-size: 14pt;
            margin-bottom: 20px;
        }
        .party-info {
            width: 48%;
            display: inline-block;
            vertical-align: top;
        }
        .party-title {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 10px;
            color: #0066cc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .text-right {
            text-align: right;
        }
        .totals-table {
            width: 50%;
            margin-left: auto;
            margin-top: 20px;
        }
        .totals-table td {
            padding: 5px 10px;
            border: none;
        }
        .total-row {
            font-weight: bold;
            font-size: 12pt;
            background-color: #f5f5f5;
        }
        .footer {
            margin-top: 40px;
            font-size: 9pt;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="invoice-title">FACTURĂ</div>
        <div class="invoice-number">Seria ' . htmlspecialchars($invoice->series ?? 'FACT') . ' Nr. ' . htmlspecialchars($invoice->invoice_number) . '</div>
    </div>

    <div style="margin-bottom: 30px;">
        <div class="party-info">
            <div class="party-title">Furnizor:</div>
            <strong>' . htmlspecialchars($company->name) . '</strong><br>';

        if ($company->cui) {
            $html .= 'CUI: ' . htmlspecialchars($company->cui) . '<br>';
        }
        if ($company->reg_com) {
            $html .= 'Nr. Reg. Com.: ' . htmlspecialchars($company->reg_com) . '<br>';
        }
        if ($company->address) {
            $html .= 'Adresă: ' . htmlspecialchars($company->address) . '<br>';
        }
        if ($company->phone) {
            $html .= 'Tel: ' . htmlspecialchars($company->phone) . '<br>';
        }
        if ($company->email) {
            $html .= 'Email: ' . htmlspecialchars($company->email) . '<br>';
        }
        if ($company->bank_name || $company->iban) {
            if ($company->bank_name) {
                $html .= 'Bancă: ' . htmlspecialchars($company->bank_name) . '<br>';
            }
            if ($company->iban) {
                $html .= 'IBAN: ' . htmlspecialchars($company->iban) . '<br>';
            }
        }

        $html .= '
        </div>

        <div class="party-info" style="margin-left: 4%;">
            <div class="party-title">Client:</div>
            <strong>' . htmlspecialchars($invoice->client_name) . '</strong><br>';

        if ($invoice->client_cui) {
            $html .= 'CUI: ' . htmlspecialchars($invoice->client_cui) . '<br>';
        }
        if ($invoice->client_reg_com) {
            $html .= 'Nr. Reg. Com.: ' . htmlspecialchars($invoice->client_reg_com) . '<br>';
        }
        if ($invoice->client_address) {
            $html .= 'Adresă: ' . nl2br(htmlspecialchars($invoice->client_address)) . '<br>';
        }

        $html .= '
        </div>
    </div>

    <table>
        <tr><th>Data Emitere:</th><td>' . ($invoice->issue_date ? $invoice->issue_date->format('d.m.Y') : '-') . '</td></tr>
        <tr><th>Data Scadență:</th><td>' . ($invoice->due_date ? $invoice->due_date->format('d.m.Y') : '-') . '</td></tr>';

        if ($invoice->contract) {
            $html .= '<tr><th>Contract:</th><td>' . htmlspecialchars($invoice->contract->contract_number) . '</td></tr>';
        }

        $html .= '
    </table>

    <table>
        <thead>
            <tr>
                <th>Nr.</th>
                <th>Denumire produs/serviciu</th>
                <th class="text-right">Cantitate</th>
                <th class="text-right">Preț unitar</th>
                <th class="text-right">Valoare</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($invoice->items as $index => $item) {
            $html .= '
            <tr>
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($item->description) . '</td>
                <td class="text-right">' . number_format($item->quantity, 2, ',', '.') . '</td>
                <td class="text-right">' . number_format($item->unit_price, 2, ',', '.') . '</td>
                <td class="text-right">' . number_format($item->total_price, 2, ',', '.') . '</td>
            </tr>';
        }

        $html .= '
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">' . number_format($invoice->subtotal_amount, 2, ',', '.') . ' RON</td>
        </tr>
        <tr>
            <td>TVA (19%):</td>
            <td class="text-right">' . number_format($invoice->vat_amount, 2, ',', '.') . ' RON</td>
        </tr>
        <tr class="total-row">
            <td>TOTAL DE PLATĂ:</td>
            <td class="text-right">' . number_format($invoice->total_amount, 2, ',', '.') . ' RON</td>
        </tr>
    </table>';

        if ($invoice->notes) {
            $html .= '<div style="margin-top: 20px;"><strong>Observații:</strong><br>' . nl2br(htmlspecialchars($invoice->notes)) . '</div>';
        }

        $html .= '
    <div class="footer">
        Document generat electronic. Conform Legii 227/2015 privind Codul fiscal, această factură este valabilă fără semnătură și ștampilă.
    </div>
</body>
</html>';

        return $html;
    }
}
