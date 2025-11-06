<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIContractService
{
    protected $apiKey;
    protected $model;
    protected $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');
        $this->model = config('services.openai.contract_model', 'gpt-4');
    }

    /**
     * Generate contract from natural language description
     */
    public function generateFromDescription(string $description, array $context = []): array
    {
        $prompt = $this->buildGenerationPrompt($description, $context);

        $response = $this->callOpenAI($prompt, [
            'max_tokens' => 4000,
            'temperature' => 0.7,
        ]);

        return [
            'title' => $this->extractTitle($response),
            'content' => $this->extractContent($response),
            'variables' => $this->extractVariables($response),
            'category' => $this->extractCategory($response),
        ];
    }

    /**
     * Review contract for risks and issues
     */
    public function reviewContract(Contract $contract): array
    {
        $prompt = $this->buildReviewPrompt($contract);

        $response = $this->callOpenAI($prompt, [
            'max_tokens' => 2000,
            'temperature' => 0.5,
        ]);

        return $this->parseReviewResponse($response);
    }

    /**
     * Suggest improvements for contract
     */
    public function suggestImprovements(Contract $contract): array
    {
        $prompt = <<<PROMPT
Review the following contract and suggest improvements:

{$contract->content}

Provide:
1. Overall assessment
2. Specific suggestions for improvement
3. Missing clauses or sections
4. Legal compliance considerations
PROMPT;

        $response = $this->callOpenAI($prompt, [
            'max_tokens' => 2000,
            'temperature' => 0.7,
        ]);

        return [
            'assessment' => $response,
            'suggestions' => $this->extractSuggestions($response),
        ];
    }

    /**
     * Simplify legal jargon
     */
    public function simplifyLanguage(string $text): string
    {
        $prompt = <<<PROMPT
Simplify the following legal text to make it easier to understand for non-lawyers.
Maintain the legal accuracy but use plain language:

{$text}
PROMPT;

        return $this->callOpenAI($prompt, [
            'max_tokens' => 1500,
            'temperature' => 0.5,
        ]);
    }

    /**
     * Extract key points from contract
     */
    public function extractKeyPoints(Contract $contract): array
    {
        $prompt = <<<PROMPT
Extract the key points from this contract in bullet point format:

{$contract->content}

Provide:
- Parties involved
- Main obligations
- Payment terms
- Duration/term
- Termination conditions
- Key risks or considerations
PROMPT;

        $response = $this->callOpenAI($prompt, [
            'max_tokens' => 1000,
            'temperature' => 0.3,
        ]);

        return $this->parseKeyPoints($response);
    }

    /**
     * Compare two contracts
     */
    public function compareContracts(Contract $contract1, Contract $contract2): array
    {
        $prompt = <<<PROMPT
Compare these two contracts and highlight the key differences:

Contract 1:
{$contract1->content}

Contract 2:
{$contract2->content}

Provide:
1. Main differences
2. Which contract is more favorable (if applicable)
3. Missing clauses in either contract
PROMPT;

        $response = $this->callOpenAI($prompt, [
            'max_tokens' => 2000,
            'temperature' => 0.5,
        ]);

        return [
            'differences' => $response,
            'recommendation' => $this->extractRecommendation($response),
        ];
    }

    /**
     * Translate contract
     */
    public function translateContract(Contract $contract, string $targetLanguage): string
    {
        $prompt = <<<PROMPT
Translate the following contract to {$targetLanguage}.
Maintain legal accuracy and formal tone:

{$contract->content}
PROMPT;

        return $this->callOpenAI($prompt, [
            'max_tokens' => 4000,
            'temperature' => 0.3,
        ]);
    }

    /**
     * Fill contract variables intelligently
     */
    public function suggestVariableValues(Contract $contract, array $context): array
    {
        $prompt = <<<PROMPT
Given this context: {$this->formatContext($context)}

Suggest appropriate values for the following contract variables:
{$this->formatVariables($contract->template->variables ?? [])}

Provide realistic values based on the context.
PROMPT;

        $response = $this->callOpenAI($prompt, [
            'max_tokens' => 1000,
            'temperature' => 0.7,
        ]);

        return $this->parseVariableSuggestions($response);
    }

    /**
     * Call OpenAI API
     */
    protected function callOpenAI(string $prompt, array $options = []): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a legal expert assistant specializing in contract analysis and generation. Provide accurate, professional, and helpful responses.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => $options['max_tokens'] ?? 2000,
                'temperature' => $options['temperature'] ?? 0.7,
            ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'];
            }

            Log::error('OpenAI API error', ['response' => $response->json()]);
            throw new \Exception('Failed to generate AI response');
        } catch (\Exception $e) {
            Log::error('AI Contract Service error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Build generation prompt
     */
    protected function buildGenerationPrompt(string $description, array $context): string
    {
        $contextStr = !empty($context) ? "\n\nAdditional Context:\n" . json_encode($context, JSON_PRETTY_PRINT) : '';

        return <<<PROMPT
Generate a professional contract based on this description:

{$description}{$contextStr}

Please provide:
1. A clear contract title
2. Complete contract content in HTML format with proper sections
3. List of variables that should be filled in (format: {{variable_name}})
4. Contract category (Service, Legal, Employment, etc.)

Format the response as:
TITLE: [contract title]
CATEGORY: [category]
VARIABLES: [comma-separated list]
CONTENT:
[full HTML contract content]
PROMPT;
    }

    /**
     * Build review prompt
     */
    protected function buildReviewPrompt(Contract $contract): string
    {
        return <<<PROMPT
Review this contract for potential issues, risks, and compliance:

Title: {$contract->title}
Content:
{$contract->content}

Provide:
1. Risk Level (Low/Medium/High)
2. Key Issues (if any)
3. Compliance Concerns
4. Missing Clauses
5. Recommendations
PROMPT;
    }

    // Helper methods for parsing responses
    protected function extractTitle(string $response): string
    {
        preg_match('/TITLE:\s*(.+)$/m', $response, $matches);
        return $matches[1] ?? 'Untitled Contract';
    }

    protected function extractContent(string $response): string
    {
        preg_match('/CONTENT:\s*(.+)/s', $response, $matches);
        return trim($matches[1] ?? $response);
    }

    protected function extractVariables(string $response): array
    {
        preg_match('/VARIABLES:\s*(.+)$/m', $response, $matches);
        if (empty($matches[1])) return [];

        return array_map('trim', explode(',', $matches[1]));
    }

    protected function extractCategory(string $response): string
    {
        preg_match('/CATEGORY:\s*(.+)$/m', $response, $matches);
        return $matches[1] ?? 'General';
    }

    protected function parseReviewResponse(string $response): array
    {
        return [
            'full_review' => $response,
            'risk_level' => $this->extractRiskLevel($response),
            'issues' => $this->extractIssues($response),
            'recommendations' => $this->extractRecommendations($response),
        ];
    }

    protected function extractRiskLevel(string $response): string
    {
        if (preg_match('/Risk Level:\s*(Low|Medium|High)/i', $response, $matches)) {
            return $matches[1];
        }
        return 'Unknown';
    }

    protected function extractIssues(string $response): array
    {
        // Simple extraction - can be improved
        preg_match_all('/[-•]\s*(.+)$/m', $response, $matches);
        return $matches[1] ?? [];
    }

    protected function extractRecommendations(string $response): array
    {
        // Extract recommendations section
        if (preg_match('/Recommendations?:(.+)/si', $response, $matches)) {
            preg_match_all('/[-•]\s*(.+)$/m', $matches[1], $recs);
            return $recs[1] ?? [];
        }
        return [];
    }

    protected function extractSuggestions(string $response): array
    {
        preg_match_all('/\d+\.\s*(.+)$/m', $response, $matches);
        return $matches[1] ?? [];
    }

    protected function parseKeyPoints(string $response): array
    {
        $lines = explode("\n", $response);
        $keyPoints = [];

        foreach ($lines as $line) {
            if (preg_match('/^[-•*]\s*(.+)$/', trim($line), $matches)) {
                $keyPoints[] = $matches[1];
            }
        }

        return $keyPoints;
    }

    protected function extractRecommendation(string $response): string
    {
        if (preg_match('/Recommendation:(.+)/si', $response, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    protected function formatContext(array $context): string
    {
        return json_encode($context, JSON_PRETTY_PRINT);
    }

    protected function formatVariables(array $variables): string
    {
        return implode(', ', array_column($variables, 'name'));
    }

    protected function parseVariableSuggestions(string $response): array
    {
        // Parse AI response to extract variable values
        // Implementation depends on response format
        return [];
    }
}
