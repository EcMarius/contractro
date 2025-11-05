@php
    use Wave\Plugins\ContractRO\Models\Platform;

    // Get active platforms dynamically
    $activePlatformsList = Cache::remember('contractro.active_platforms_list', 3600, function() {
        try {
            return Platform::where('is_active', true)->pluck('display_name')->toArray();
        } catch (\Exception $e) {
            return ['Reddit']; // Fallback
        }
    });

    $platformText = count($activePlatformsList) > 1
        ? implode(', ', array_slice($activePlatformsList, 0, -1)) . ' and ' . end($activePlatformsList)
        : ($activePlatformsList[0] ?? 'multiple platforms');
@endphp

<section id="features">
    <x-marketing.elements.heading
        level="h2"
        title="{{ __('marketing.features_title') }}"
        description="{{ __('marketing.features_subtitle', ['platforms' => $platformText]) }}"
    />
    <div class="text-center">
        <div class="grid grid-cols-2 gap-x-6 gap-y-12 mt-12 text-center lg:mt-16 lg:grid-cols-4 lg:gap-x-8 lg:gap-y-16">
            <div>
                <div class="flex justify-center items-center mx-auto bg-emerald-100 rounded-full size-12">
                    <x-phosphor-magnifying-glass class="w-6 h-6 text-emerald-600" />
                </div>
                <div class="mt-6">
                    <h3 class="font-medium text-zinc-900">{{ __('marketing.feature_multiplatform_title') }}</h3>
                    <p class="mt-2 text-sm text-zinc-500">
                        {{ __('marketing.feature_multiplatform_desc', ['platforms' => $platformText]) }}
                    </p>
                </div>
            </div>
            <div>
                <div class="flex justify-center items-center mx-auto bg-emerald-100 rounded-full size-12">
                    <x-phosphor-robot class="w-6 h-6 text-emerald-600" />
                </div>
                <div class="mt-6">
                    <h3 class="font-medium text-zinc-900">{{ __('marketing.feature_ai_replies_title') }}</h3>
                    <p class="mt-2 text-sm text-zinc-500">
                        {{ __('marketing.feature_ai_replies_desc') }}
                    </p>
                </div>
            </div>
            <div>
                <div class="flex justify-center items-center mx-auto bg-emerald-100 rounded-full size-12">
                    <x-phosphor-target class="w-6 h-6 text-emerald-600" />
                </div>
                <div class="mt-6">
                    <h3 class="font-medium text-zinc-900">{{ __('marketing.feature_smart_scoring_title') }}</h3>
                    <p class="mt-2 text-sm text-zinc-500">
                        {{ __('marketing.feature_smart_scoring_desc') }}
                    </p>
                </div>
            </div>
            <div>
                <div class="flex justify-center items-center mx-auto bg-emerald-100 rounded-full size-12">
                    <x-phosphor-chart-line class="w-6 h-6 text-emerald-600" />
                </div>
                <div class="mt-6">
                    <h3 class="font-medium text-zinc-900">{{ __('marketing.feature_campaign_analytics_title') }}</h3>
                    <p class="mt-2 text-sm text-zinc-500">
                        {{ __('marketing.feature_campaign_analytics_desc') }}
                    </p>
                </div>
            </div>
            <div>
                <div class="flex justify-center items-center mx-auto bg-emerald-100 rounded-full size-12">
                    <x-phosphor-chat-circle-text class="w-6 h-6 text-emerald-600" />
                </div>
                <div class="mt-6">
                    <h3 class="font-medium text-zinc-900">{{ __('marketing.feature_post_management_title') }}</h3>
                    <p class="mt-2 text-sm text-zinc-500">
                        {{ __('marketing.feature_post_management_desc') }}
                    </p>
                </div>
            </div>
            <div>
                <div class="flex justify-center items-center mx-auto bg-emerald-100 rounded-full size-12">
                    <x-phosphor-users-three class="w-6 h-6 text-emerald-600" />
                </div>
                <div class="mt-6">
                    <h3 class="font-medium text-zinc-900">{{ __('marketing.feature_multiaccount_title') }}</h3>
                    <p class="mt-2 text-sm text-zinc-500">
                        {{ __('marketing.feature_multiaccount_desc') }}
                    </p>
                </div>
            </div>
            <div>
                <div class="flex justify-center items-center mx-auto bg-emerald-100 rounded-full size-12">
                    <x-phosphor-folders class="w-6 h-6 text-emerald-600" />
                </div>
                <div class="mt-6">
                    <h3 class="font-medium text-zinc-900">{{ __('marketing.feature_campaign_from_website_title') }}</h3>
                    <p class="mt-2 text-sm text-zinc-500">
                        {{ __('marketing.feature_campaign_from_website_desc') }}
                    </p>
                </div>
            </div>
            <div>
                <div class="flex justify-center items-center mx-auto bg-emerald-100 rounded-full size-12">
                    <x-phosphor-code class="w-6 h-6 text-emerald-600" />
                </div>
                <div class="mt-6">
                    <h3 class="font-medium text-zinc-900">{{ __('marketing.feature_developer_api_title') }}</h3>
                    <p class="mt-2 text-sm text-zinc-500">
                        {{ __('marketing.feature_developer_api_desc') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>