<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Company Lookup Service
 * Auto-fills company data based on registration code and country
 *
 * Supported:
 * - Romania (RO): ANAF API
 * - European Union (EU): VIES VAT API
 * - United Kingdom (GB/UK): Companies House API (requires API key)
 * - United States (US): OpenCorporates API (free tier, limited)
 * - Worldwide: OpenCorporates API fallback
 */
class CompanyLookupService
{
    protected AnafService $anafService;

    public function __construct(AnafService $anafService)
    {
        $this->anafService = $anafService;
    }

    /**
     * Lookup company by registration code and country
     */
    public function lookup(string $registrationCode, string $country): array
    {
        $country = strtoupper(trim($country));

        try {
            return match($country) {
                'RO', 'ROM', 'ROMANIA' => $this->lookupRomania($registrationCode),
                'US', 'USA', 'UNITED STATES' => $this->lookupUSA($registrationCode),
                'GB', 'UK', 'UNITED KINGDOM' => $this->lookupUK($registrationCode),
                'DE', 'GERMANY', 'FR', 'FRANCE', 'ES', 'SPAIN', 'IT', 'ITALY',
                'NL', 'NETHERLANDS', 'BE', 'BELGIUM', 'AT', 'AUSTRIA', 'PL', 'POLAND',
                'SE', 'SWEDEN', 'DK', 'DENMARK', 'FI', 'FINLAND', 'NO', 'NORWAY'
                    => $this->lookupEU($registrationCode, $country),
                default => $this->lookupOpenCorporates($registrationCode, $country),
            };
        } catch (\Exception $e) {
            Log::error('Company lookup error', [
                'country' => $country,
                'registration_code' => $registrationCode,
                'error' => $e->getMessage(),
            ]);

            return [
                'found' => false,
                'error' => 'Could not lookup company: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Lookup Romanian company via ANAF
     */
    protected function lookupRomania(string $cui): array
    {
        $result = $this->anafService->validateCUI($cui);

        if (!$result['valid']) {
            return [
                'found' => false,
                'message' => $result['message'],
            ];
        }

        return [
            'found' => true,
            'source' => 'ANAF (Romania)',
            'data' => [
                'name' => $result['name'],
                'registration_code' => $result['cui'],
                'vat_number' => 'RO' . $result['cui'],
                'address' => $result['address'],
                'phone' => $result['phone'],
                'registration_number' => $result['registration_number'],
                'vat_registered' => $result['vat_registered'],
                'country' => 'Romania',
                'country_code' => 'RO',
            ],
            'raw' => $result,
        ];
    }

    /**
     * Lookup EU company via VIES VAT API
     * Free API, no key required
     */
    protected function lookupEU(string $vatNumber, string $countryCode): array
    {
        // Clean VAT number
        $vatNumber = preg_replace('/[^A-Z0-9]/', '', strtoupper($vatNumber));

        // Extract country code if included in VAT
        if (strlen($vatNumber) > 2 && preg_match('/^[A-Z]{2}/', $vatNumber)) {
            $countryCode = substr($vatNumber, 0, 2);
            $vatNumber = substr($vatNumber, 2);
        }

        $cacheKey = "vies_vat_{$countryCode}_{$vatNumber}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // VIES SOAP API
            $soapClient = new \SoapClient('http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl', [
                'connection_timeout' => 10,
            ]);

            $response = $soapClient->checkVat([
                'countryCode' => $countryCode,
                'vatNumber' => $vatNumber,
            ]);

            if (!$response->valid) {
                return ['found' => false, 'message' => 'VAT number not found in VIES database'];
            }

            $result = [
                'found' => true,
                'source' => 'VIES (EU VAT)',
                'data' => [
                    'name' => $response->name ?? null,
                    'address' => $response->address ?? null,
                    'vat_number' => $countryCode . $vatNumber,
                    'country_code' => $countryCode,
                    'vat_registered' => true,
                ],
                'raw' => (array) $response,
            ];

            Cache::put($cacheKey, $result, now()->addDay());
            return $result;

        } catch (\Exception $e) {
            Log::warning('VIES VAT lookup failed', ['error' => $e->getMessage()]);

            // Fallback to OpenCorporates
            return $this->lookupOpenCorporates($vatNumber, $countryCode);
        }
    }

    /**
     * Lookup UK company via Companies House API
     * Requires API key (free): https://developer.company-information.service.gov.uk/
     */
    protected function lookupUK(string $companyNumber): array
    {
        $apiKey = config('services.companies_house.api_key');

        if (empty($apiKey)) {
            Log::info('Companies House API key not configured, using OpenCorporates fallback');
            return $this->lookupOpenCorporates($companyNumber, 'GB');
        }

        $companyNumber = preg_replace('/[^A-Z0-9]/', '', strtoupper($companyNumber));
        $cacheKey = "companies_house_{$companyNumber}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::withBasicAuth($apiKey, '')
                ->timeout(10)
                ->get("https://api.company-information.service.gov.uk/company/{$companyNumber}");

            if (!$response->successful()) {
                return ['found' => false, 'message' => 'Company not found in UK register'];
            }

            $data = $response->json();

            $result = [
                'found' => true,
                'source' => 'Companies House (UK)',
                'data' => [
                    'name' => $data['company_name'] ?? null,
                    'registration_code' => $data['company_number'] ?? null,
                    'address' => $this->formatUKAddress($data['registered_office_address'] ?? []),
                    'status' => $data['company_status'] ?? null,
                    'type' => $data['type'] ?? null,
                    'country' => 'United Kingdom',
                    'country_code' => 'GB',
                ],
                'raw' => $data,
            ];

            Cache::put($cacheKey, $result, now()->addDay());
            return $result;

        } catch (\Exception $e) {
            Log::warning('Companies House lookup failed', ['error' => $e->getMessage()]);
            return $this->lookupOpenCorporates($companyNumber, 'GB');
        }
    }

    /**
     * Lookup USA company via OpenCorporates
     * Free tier: 500 requests/month
     */
    protected function lookupUSA(string $ein): array
    {
        // EIN format: XX-XXXXXXX
        $ein = preg_replace('/[^0-9]/', '', $ein);

        // OpenCorporates doesn't search by EIN well, return placeholder
        return [
            'found' => false,
            'message' => 'USA company lookup requires company name. EIN alone is not sufficient.',
            'suggestion' => 'Please enter company name manually or use OpenCorporates website: https://opencorporates.com',
        ];
    }

    /**
     * Lookup company via OpenCorporates API (worldwide fallback)
     * Free tier: 500 requests/month, requires API token for higher limits
     * https://api.opencorporates.com/
     */
    protected function lookupOpenCorporates(string $registrationCode, string $country): array
    {
        $apiToken = config('services.opencorporates.api_token', null);
        $cacheKey = "opencorp_{$country}_{$registrationCode}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $url = 'https://api.opencorporates.com/v0.4/companies/search';
            $params = [
                'q' => $registrationCode,
                'country_code' => $country,
                'per_page' => 1,
            ];

            if ($apiToken) {
                $params['api_token'] = $apiToken;
            }

            $response = Http::timeout(10)->get($url, $params);

            if (!$response->successful()) {
                return [
                    'found' => false,
                    'message' => 'OpenCorporates API error',
                ];
            }

            $data = $response->json();
            $companies = $data['results']['companies'] ?? [];

            if (empty($companies)) {
                return [
                    'found' => false,
                    'message' => 'Company not found in OpenCorporates database',
                ];
            }

            $company = $companies[0]['company'];

            $result = [
                'found' => true,
                'source' => 'OpenCorporates (Worldwide)',
                'data' => [
                    'name' => $company['name'] ?? null,
                    'registration_code' => $company['company_number'] ?? null,
                    'address' => $company['registered_address_in_full'] ?? null,
                    'status' => $company['current_status'] ?? null,
                    'jurisdiction' => $company['jurisdiction_code'] ?? null,
                    'country_code' => $country,
                ],
                'raw' => $company,
            ];

            Cache::put($cacheKey, $result, now()->addDay());
            return $result;

        } catch (\Exception $e) {
            Log::warning('OpenCorporates lookup failed', ['error' => $e->getMessage()]);

            return [
                'found' => false,
                'message' => 'Could not lookup company via OpenCorporates: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Format UK address from Companies House format
     */
    protected function formatUKAddress(array $address): ?string
    {
        if (empty($address)) {
            return null;
        }

        $parts = array_filter([
            $address['address_line_1'] ?? null,
            $address['address_line_2'] ?? null,
            $address['locality'] ?? null,
            $address['postal_code'] ?? null,
            $address['country'] ?? null,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get list of supported countries
     */
    public static function getSupportedCountries(): array
    {
        return [
            'RO' => ['name' => 'Romania', 'api' => 'ANAF', 'free' => true],
            'GB' => ['name' => 'United Kingdom', 'api' => 'Companies House', 'free' => true],
            'DE' => ['name' => 'Germany', 'api' => 'VIES VAT', 'free' => true],
            'FR' => ['name' => 'France', 'api' => 'VIES VAT', 'free' => true],
            'ES' => ['name' => 'Spain', 'api' => 'VIES VAT', 'free' => true],
            'IT' => ['name' => 'Italy', 'api' => 'VIES VAT', 'free' => true],
            'NL' => ['name' => 'Netherlands', 'api' => 'VIES VAT', 'free' => true],
            'BE' => ['name' => 'Belgium', 'api' => 'VIES VAT', 'free' => true],
            'AT' => ['name' => 'Austria', 'api' => 'VIES VAT', 'free' => true],
            'PL' => ['name' => 'Poland', 'api' => 'VIES VAT', 'free' => true],
            'SE' => ['name' => 'Sweden', 'api' => 'VIES VAT', 'free' => true],
            'DK' => ['name' => 'Denmark', 'api' => 'VIES VAT', 'free' => true],
            'FI' => ['name' => 'Finland', 'api' => 'VIES VAT', 'free' => true],
            'US' => ['name' => 'United States', 'api' => 'Manual', 'free' => false],
            'OTHER' => ['name' => 'Other Countries', 'api' => 'OpenCorporates', 'free' => true, 'limited' => '500/month'],
        ];
    }
}
