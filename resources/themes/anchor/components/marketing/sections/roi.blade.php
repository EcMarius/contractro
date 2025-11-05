@php
    use Wave\Plugins\ContractRO\Models\Setting;
    use Wave\Plan;

    $trialDays = Setting::getValue('trial_days', 7);
    $plans = Plan::where('active', 1)->whereNotNull('monthly_price')->where('monthly_price', '>', 0)->orderBy('monthly_price')->get();
@endphp

<section id="roi" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-8 md:px-12 xl:px-20">
        <!-- Section Header -->
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold tracking-tight text-zinc-900 mb-4">
                {{ __('marketing.roi_title') }}
            </h2>
            <p class="text-lg text-zinc-600 max-w-3xl mx-auto">
                {{ __('marketing.roi_subtitle') }}
            </p>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16">
            <div class="bg-white rounded-lg border-2 border-zinc-200 p-8 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-emerald-50 rounded-full mb-4">
                    <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="text-4xl font-bold text-zinc-900 mb-2">15+ {{ __('marketing.roi_time_saved') }}</div>
                <div class="text-zinc-600">{{ __('marketing.roi_time_saved_label') }}</div>
            </div>

            <div class="bg-white rounded-lg border-2 border-zinc-200 p-8 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-50 rounded-full mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="text-4xl font-bold text-zinc-900 mb-2">{{ __('marketing.roi_lead_quality') }}</div>
                <div class="text-zinc-600">{{ __('marketing.roi_lead_quality_label') }}</div>
            </div>

            <div class="bg-white rounded-lg border-2 border-zinc-200 p-8 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-50 rounded-full mb-4">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <div class="text-4xl font-bold text-zinc-900 mb-2">{{ __('marketing.roi_lower_cac') }}</div>
                <div class="text-zinc-600">{{ __('marketing.roi_lower_cac_label') }}</div>
            </div>
        </div>

        <!-- ROI Calculator -->
        <div class="bg-white rounded-lg border-2 border-zinc-200 overflow-hidden max-w-3xl mx-auto"
             x-data="{
                selectedPlan: 'growth',
                plans: {
                    @foreach($plans as $plan)
                        '{{ strtolower(str_replace(' ', '-', $plan->name)) }}': {
                            name: '{{ $plan->name }}',
                            price: {{ $plan->monthly_price }},
                            leadsPerMonth: {{ ($plan->leads_per_sync ?? 60) }} * 2.5
                        },
                    @endforeach
                },
                avgDealValue: 100,
                conversionRate: 2.5,
                get currentPlan() {
                    return this.plans[this.selectedPlan];
                },
                get leadsPerMonth() {
                    return this.currentPlan.leadsPerMonth;
                },
                get cost() {
                    return this.currentPlan.price;
                },
                get conversions() {
                    return (this.leadsPerMonth * this.conversionRate) / 100;
                },
                get revenue() {
                    return this.conversions * this.avgDealValue;
                },
                get netProfit() {
                    return this.revenue - this.cost;
                },
                get roi() {
                    return this.cost > 0 ? ((this.netProfit / this.cost) * 100).toFixed(0) : 0;
                }
             }">

            <div class="p-8">
                <h3 class="text-2xl font-bold text-zinc-900 mb-2 text-center">{{ __('marketing.roi_calculator') }}</h3>
                <p class="text-zinc-600 mb-8 text-center text-sm">
                    {{ __('marketing.roi_calculator_desc') }}
                </p>

                <!-- Calculator Form -->
                <div class="space-y-6 mb-8">
                    <!-- Plan Selector -->
                    <div>
                        <label class="flex justify-between text-sm font-medium text-zinc-700 mb-2">
                            <span>{{ __('marketing.roi_select_plan') }}</span>
                            <span class="font-bold text-emerald-600" x-text="currentPlan.name + ' - €' + currentPlan.price + '/mo'"></span>
                        </label>
                        <select x-model="selectedPlan"
                                class="block w-full rounded-lg border-2 border-zinc-200 px-4 py-3 text-sm focus:border-zinc-900 focus:ring-0">
                            @foreach($plans as $plan)
                                <option value="{{ strtolower(str_replace(' ', '-', $plan->name)) }}">
                                    {{ $plan->name }} - €{{ $plan->monthly_price }}/mo
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-zinc-500">{{ __('marketing.roi_select_plan') }}</p>
                    </div>

                    <!-- Average Deal Value -->
                    <div>
                        <label class="flex justify-between text-sm font-medium text-zinc-700 mb-2">
                            <span>{{ __('marketing.roi_avg_deal_value') }}</span>
                            <span class="font-bold text-emerald-600">€<span x-text="avgDealValue.toLocaleString()"></span></span>
                        </label>
                        <input type="range" x-model.number="avgDealValue" min="20" max="50000" step="10"
                               class="w-full h-2 bg-zinc-200 rounded-lg appearance-none cursor-pointer accent-zinc-900">
                        <p class="mt-1.5 text-xs text-zinc-500">{{ __('marketing.roi_avg_deal_value_desc') }}</p>
                    </div>
                </div>

                <!-- Results -->
                <div class="bg-zinc-50 rounded-lg p-6 border-2 border-zinc-200 mb-6">
                    <div class="grid grid-cols-2 gap-6 mb-4">
                        <div>
                            <div class="text-xs text-zinc-600 mb-1">{{ __('marketing.roi_monthly_investment') }}</div>
                            <div class="text-2xl font-bold text-zinc-900">€<span x-text="cost"></span></div>
                        </div>
                        <div>
                            <div class="text-xs text-zinc-600 mb-1">{{ __('marketing.roi_expected_conversions') }}</div>
                            <div class="text-2xl font-bold text-zinc-900" x-text="Math.round(conversions)"></div>
                        </div>
                        <div>
                            <div class="text-xs text-zinc-600 mb-1">{{ __('marketing.roi_monthly_revenue') }}</div>
                            <div class="text-2xl font-bold text-emerald-600">€<span x-text="revenue.toLocaleString()"></span></div>
                        </div>
                        <div>
                            <div class="text-xs text-zinc-600 mb-1">{{ __('marketing.roi_net_profit') }}</div>
                            <div class="text-2xl font-bold text-emerald-600">€<span x-text="netProfit.toLocaleString()"></span></div>
                        </div>
                    </div>
                    <div class="pt-4 border-t-2 border-zinc-200">
                        <div class="text-sm text-zinc-600 mb-2">{{ __('marketing.roi_return') }}</div>
                        <div class="text-5xl font-bold text-emerald-600">
                            <span x-text="roi"></span>%
                        </div>
                        <p class="text-sm text-zinc-500 mt-2">
                            {{ __('marketing.roi_return_desc', ['amount' => '<span x-text="(revenue / cost).toFixed(2)"></span>']) }}
                        </p>
                    </div>
                </div>

                <!-- Disclaimer -->
                <div class="bg-zinc-50 border border-zinc-200 rounded-lg p-4 mb-6">
                    <p class="text-xs text-zinc-600 text-center">
                        <svg class="w-4 h-4 inline-block mr-1 text-zinc-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        {!! __('marketing.roi_disclaimer') !!}
                    </p>
                </div>

                <!-- CTA -->
                <div class="text-center">
                    <a href="/register" class="inline-flex items-center gap-2 px-8 py-4 text-base font-semibold text-white bg-zinc-900 hover:bg-zinc-800 rounded-lg transition-all">
                        {{ __('marketing.roi_see_roi_cta') }}
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
