<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * ANAF Integration Service
 * Handles e-Factura (mandatory B2B invoicing since Jan 2025) and CUI validation
 */
class AnafService
{
    protected string $baseUrl = 'https://webservicesp.anaf.ro';
    protected string $productionUrl = 'https://api.anaf.ro/prod/FCTEL/rest';
    protected string $testUrl = 'https://api.anaf.ro/test/FCTEL/rest';

    /**
     * Validate CUI (Romanian tax ID) against ANAF database
     * Returns company details if CUI is valid
     */
    public function validateCUI(string $cui): array
    {
        // Clean CUI - remove RO prefix, spaces
        $cleanCUI = preg_replace('/[^0-9]/', '', $cui);

        // Check cache first (cache for 24 hours)
        $cacheKey = "anaf_cui_{$cleanCUI}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/PlatitorTvaRest/api/v8/ws/tva", [
                [
                    'cui' => $cleanCUI,
                    'data' => now()->format('Y-m-d'),
                ]
            ]);

            if (!$response->successful()) {
                throw new \Exception('ANAF API error: ' . $response->status());
            }

            $data = $response->json();

            if (empty($data['found'])) {
                return [
                    'valid' => false,
                    'message' => 'CUI invalid sau neînregistrat în baza ANAF',
                ];
            }

            $companyData = $data['found'][0];

            $result = [
                'valid' => true,
                'cui' => $cleanCUI,
                'name' => $companyData['denumire'] ?? null,
                'vat_registered' => $companyData['scpTVA'] ?? false,
                'vat_from_date' => $companyData['data_inceput_ScpTVA'] ?? null,
                'vat_to_date' => $companyData['data_sfarsit_ScpTVA'] ?? null,
                'address' => $companyData['adresa'] ?? null,
                'phone' => $companyData['telefon'] ?? null,
                'registration_number' => $companyData['nrRegCom'] ?? null,
                'is_inactive' => $companyData['dataInactivare'] ?? false,
                'message' => 'CUI valid - înregistrat în baza ANAF',
            ];

            // Cache for 24 hours
            Cache::put($cacheKey, $result, now()->addDay());

            return $result;

        } catch (\Exception $e) {
            Log::error('ANAF CUI validation error', [
                'cui' => $cleanCUI,
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'message' => 'Eroare la validarea CUI: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send invoice to ANAF e-Factura (mandatory since Jan 2025)
     * Requires SPV (Virtual Private Space) credentials
     */
    public function sendToEFactura(Invoice $invoice, Company $company, bool $testMode = true): array
    {
        $url = $testMode ? $this->testUrl : $this->productionUrl;

        try {
            // Get company's ANAF integration credentials
            $integration = $company->integrations()
                ->where('type', 'anaf')
                ->where('provider', 'anaf_efactura')
                ->where('is_active', true)
                ->first();

            if (!$integration) {
                throw new \Exception('ANAF e-Factura integration not configured for this company');
            }

            $config = $integration->config;

            // Generate UBL 2.1 XML format (mandatory for RO e-Factura)
            $xml = $this->generateUBL21XML($invoice, $company);

            // Upload to ANAF
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . ($config['access_token'] ?? ''),
                'Content-Type' => 'application/xml',
            ])->timeout(30)->post("{$url}/upload", [
                'standard' => 'UBL',
                'cif' => $company->cui,
                'zip' => base64_encode(gzencode($xml)),
            ]);

            if (!$response->successful()) {
                throw new \Exception('ANAF upload failed: ' . $response->body());
            }

            $result = $response->json();

            // Log the integration
            $integration->logs()->create([
                'action' => 'upload_invoice',
                'status' => 'success',
                'request_data' => ['invoice_id' => $invoice->id],
                'response_data' => $result,
            ]);

            $integration->update([
                'last_sync_at' => now(),
                'sync_count' => $integration->sync_count + 1,
            ]);

            // Update invoice with ANAF data
            $invoice->update([
                'anaf_upload_index' => $result['upload_index'] ?? null,
                'anaf_status' => 'uploaded',
                'anaf_uploaded_at' => now(),
            ]);

            return [
                'success' => true,
                'upload_index' => $result['upload_index'] ?? null,
                'message' => 'Factura a fost trimisă cu succes către ANAF e-Factura',
                'download_id' => $result['download_id'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('ANAF e-Factura upload error', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            if (isset($integration)) {
                $integration->logs()->create([
                    'action' => 'upload_invoice',
                    'status' => 'failed',
                    'request_data' => ['invoice_id' => $invoice->id],
                    'error_message' => $e->getMessage(),
                ]);

                $integration->update(['last_error' => $e->getMessage()]);
            }

            return [
                'success' => false,
                'message' => 'Eroare la trimiterea facturii: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate UBL 2.1 XML format (mandatory for RO e-Factura)
     */
    protected function generateUBL21XML(Invoice $invoice, Company $company): string
    {
        // Basic UBL 2.1 structure for Romanian e-Factura
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2">';
        $xml .= '<cbc:ID>' . $invoice->invoice_number . '</cbc:ID>';
        $xml .= '<cbc:IssueDate>' . $invoice->issue_date->format('Y-m-d') . '</cbc:IssueDate>';
        $xml .= '<cbc:DueDate>' . $invoice->due_date->format('Y-m-d') . '</cbc:DueDate>';
        $xml .= '<cbc:InvoiceTypeCode>380</cbc:InvoiceTypeCode>';
        $xml .= '<cbc:DocumentCurrencyCode>' . $invoice->currency . '</cbc:DocumentCurrencyCode>';

        // Supplier (AccountingSupplierParty)
        $xml .= '<cac:AccountingSupplierParty>';
        $xml .= '<cac:Party>';
        $xml .= '<cac:PartyIdentification><cbc:ID>' . $company->cui . '</cbc:ID></cac:PartyIdentification>';
        $xml .= '<cac:PartyName><cbc:Name>' . htmlspecialchars($company->name) . '</cbc:Name></cac:PartyName>';
        $xml .= '<cac:PostalAddress>';
        $xml .= '<cbc:StreetName>' . htmlspecialchars($company->address ?? '') . '</cbc:StreetName>';
        $xml .= '<cbc:CityName>' . htmlspecialchars($company->city ?? '') . '</cbc:CityName>';
        $xml .= '<cbc:PostalZone>' . htmlspecialchars($company->postal_code ?? '') . '</cbc:PostalZone>';
        $xml .= '<cac:Country><cbc:IdentificationCode>RO</cbc:IdentificationCode></cac:Country>';
        $xml .= '</cac:PostalAddress>';
        $xml .= '</cac:Party>';
        $xml .= '</cac:AccountingSupplierParty>';

        // Customer (AccountingCustomerParty)
        $xml .= '<cac:AccountingCustomerParty>';
        $xml .= '<cac:Party>';
        $xml .= '<cac:PartyIdentification><cbc:ID>' . ($invoice->client_cui ?? 'N/A') . '</cbc:ID></cac:PartyIdentification>';
        $xml .= '<cac:PartyName><cbc:Name>' . htmlspecialchars($invoice->client_name) . '</cbc:Name></cac:PartyName>';
        $xml .= '</cac:Party>';
        $xml .= '</cac:AccountingCustomerParty>';

        // Invoice lines
        foreach ($invoice->items as $index => $item) {
            $xml .= '<cac:InvoiceLine>';
            $xml .= '<cbc:ID>' . ($index + 1) . '</cbc:ID>';
            $xml .= '<cbc:InvoicedQuantity unitCode="EA">' . $item['quantity'] . '</cbc:InvoicedQuantity>';
            $xml .= '<cbc:LineExtensionAmount currencyID="' . $invoice->currency . '">' . $item['total'] . '</cbc:LineExtensionAmount>';
            $xml .= '<cac:Item><cbc:Description>' . htmlspecialchars($item['description']) . '</cbc:Description></cac:Item>';
            $xml .= '<cac:Price><cbc:PriceAmount currencyID="' . $invoice->currency . '">' . $item['unit_price'] . '</cbc:PriceAmount></cac:Price>';
            $xml .= '</cac:InvoiceLine>';
        }

        // Monetary totals
        $xml .= '<cac:LegalMonetaryTotal>';
        $xml .= '<cbc:LineExtensionAmount currencyID="' . $invoice->currency . '">' . $invoice->subtotal . '</cbc:LineExtensionAmount>';
        $xml .= '<cbc:TaxExclusiveAmount currencyID="' . $invoice->currency . '">' . $invoice->subtotal . '</cbc:TaxExclusiveAmount>';
        $xml .= '<cbc:TaxInclusiveAmount currencyID="' . $invoice->currency . '">' . $invoice->total . '</cbc:TaxInclusiveAmount>';
        $xml .= '<cbc:PayableAmount currencyID="' . $invoice->currency . '">' . $invoice->total . '</cbc:PayableAmount>';
        $xml .= '</cac:LegalMonetaryTotal>';

        $xml .= '</Invoice>';

        return $xml;
    }

    /**
     * Check status of uploaded invoice
     */
    public function checkInvoiceStatus(Invoice $invoice, Company $company, bool $testMode = true): array
    {
        $url = $testMode ? $this->testUrl : $this->productionUrl;

        if (!$invoice->anaf_upload_index) {
            return [
                'success' => false,
                'message' => 'Factura nu a fost încă încărcată în ANAF',
            ];
        }

        try {
            $integration = $company->integrations()
                ->where('type', 'anaf')
                ->where('is_active', true)
                ->first();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . ($integration->config['access_token'] ?? ''),
            ])->get("{$url}/messages/{$invoice->anaf_upload_index}/state");

            $result = $response->json();

            return [
                'success' => true,
                'status' => $result['status'] ?? 'unknown',
                'messages' => $result['messages'] ?? [],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Eroare la verificarea statusului: ' . $e->getMessage(),
            ];
        }
    }
}
