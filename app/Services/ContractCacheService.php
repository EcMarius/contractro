<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTemplate;
use Illuminate\Support\Facades\Cache;

class ContractCacheService
{
    /**
     * Cache duration in seconds (1 hour)
     */
    protected int $cacheDuration = 3600;

    /**
     * Get contract with caching
     */
    public function getContract(int $contractId): ?Contract
    {
        $cacheKey = "contract.{$contractId}";

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($contractId) {
            return Contract::with(['user', 'template', 'signatures', 'approvals', 'tags'])
                ->find($contractId);
        });
    }

    /**
     * Get user's contracts with caching
     */
    public function getUserContracts(int $userId, array $filters = []): mixed
    {
        $cacheKey = "user.{$userId}.contracts." . md5(json_encode($filters));

        return Cache::remember($cacheKey, 600, function () use ($userId, $filters) { // 10 minutes
            $query = Contract::where('user_id', $userId)
                ->with(['template', 'signatures']);

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            return $query->orderBy('created_at', 'desc')
                ->limit($filters['limit'] ?? 20)
                ->get();
        });
    }

    /**
     * Get popular templates with caching
     */
    public function getPopularTemplates(int $limit = 10): mixed
    {
        $cacheKey = "templates.popular.{$limit}";

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($limit) {
            return ContractTemplate::where('is_public', true)
                ->orderBy('usage_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get template by ID with caching
     */
    public function getTemplate(int $templateId): ?ContractTemplate
    {
        $cacheKey = "template.{$templateId}";

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($templateId) {
            return ContractTemplate::find($templateId);
        });
    }

    /**
     * Get contract statistics with caching
     */
    public function getContractStats(int $userId = null): array
    {
        $cacheKey = $userId ? "stats.user.{$userId}" : "stats.global";

        return Cache::remember($cacheKey, 1800, function () use ($userId) { // 30 minutes
            $query = Contract::query();

            if ($userId) {
                $query->where('user_id', $userId);
            }

            return [
                'total' => $query->count(),
                'draft' => (clone $query)->where('status', 'draft')->count(),
                'pending_signature' => (clone $query)->where('status', 'pending_signature')->count(),
                'signed' => (clone $query)->where('status', 'signed')->count(),
                'completed' => (clone $query)->where('status', 'completed')->count(),
                'total_value' => (clone $query)->whereIn('status', ['signed', 'completed'])->sum('contract_value'),
            ];
        });
    }

    /**
     * Clear contract cache
     */
    public function clearContractCache(int $contractId): void
    {
        Cache::forget("contract.{$contractId}");
    }

    /**
     * Clear user contracts cache
     */
    public function clearUserContractsCache(int $userId): void
    {
        // Clear all cache keys that start with user's contract cache prefix
        Cache::tags(["user.{$userId}.contracts"])->flush();
    }

    /**
     * Clear template cache
     */
    public function clearTemplateCache(int $templateId): void
    {
        Cache::forget("template.{$templateId}");
        Cache::forget("templates.popular.*");
    }

    /**
     * Clear stats cache
     */
    public function clearStatsCache(int $userId = null): void
    {
        if ($userId) {
            Cache::forget("stats.user.{$userId}");
        }
        Cache::forget("stats.global");
    }

    /**
     * Clear all contract-related caches
     */
    public function clearAllCaches(): void
    {
        Cache::tags(['contracts'])->flush();
    }

    /**
     * Warm up cache for frequently accessed data
     */
    public function warmUpCache(): void
    {
        // Warm up popular templates
        $this->getPopularTemplates(20);

        // Warm up global stats
        $this->getContractStats();

        \Log::info('Contract cache warmed up successfully');
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        return [
            'cache_driver' => config('cache.default'),
            'cache_prefix' => config('cache.prefix'),
            'cached_items' => [
                'contracts' => Cache::has('contract.*') ? 'yes' : 'no',
                'templates' => Cache::has('template.*') ? 'yes' : 'no',
                'stats' => Cache::has('stats.*') ? 'yes' : 'no',
            ],
        ];
    }
}
