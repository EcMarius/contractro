<?php

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    // Company Lookup APIs
    // For auto-filling company data from registration codes

    // UK Companies House API (Free)
    // Get API key: https://developer.company-information.service.gov.uk/
    'companies_house' => [
        'api_key' => env('COMPANIES_HOUSE_API_KEY'),
    ],

    // OpenCorporates API (Free tier: 500 requests/month)
    // For worldwide company lookup
    // Get API token: https://opencorporates.com/api_accounts/new
    'opencorporates' => [
        'api_token' => env('OPENCORPORATES_API_TOKEN'),
    ],

];
