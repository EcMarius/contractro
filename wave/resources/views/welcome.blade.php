<?php
    use function Laravel\Folio\{middleware, name};
    name('subscription.welcome');
    middleware('auth');

    $user = auth()->user();
    $subscription = null;
    $plan = null;
    $showCountrySelector = $user && !$user->country;

    if ($user) {
        try {
            $subscription = \Wave\Subscription::where('billable_id', $user->id)
                ->where('billable_type', 'user')
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->first();
            $plan = $subscription ? $subscription->plan : null;
        } catch (\Exception $e) {
            \Log::error('Subscription welcome page error: ' . $e->getMessage());
        }
    }
?>

<x-layouts.app>
	<x-app.container x-data class="space-y-6" x-cloak>
        <div class="w-full">
            <x-app.heading
                title="Bun venit la {{ $plan ? $plan->name : 'ContractRO' }}! 🎉"
                description="Abonamentul dumneavoastră a fost activat cu succes."
            />

            @if($plan && $user)
                <div class="py-5 space-y-6">
                    <!-- Selector Țară (Onboarding) -->
                    @if($showCountrySelector)
                        <div class="p-6 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl border-2 border-indigo-200 dark:border-indigo-700 shadow-sm" x-data="{ country: '{{ $user->country ?? '' }}', saving: false }">
                            <h3 class="text-lg font-semibold text-indigo-900 dark:text-indigo-100 mb-2">📍 Un ultim pas!</h3>
                            <p class="text-sm text-indigo-800 dark:text-indigo-200 mb-4">Ajutați-ne să personalizăm experiența selectând țara dumneavoastră:</p>

                            <form @submit.prevent="
                                saving = true;
                                fetch('/api/user/update-country', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                    },
                                    body: JSON.stringify({ country: country })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        $el.closest('[x-data]').remove();
                                    }
                                    saving = false;
                                })
                                .catch(() => { saving = false; })
                            " class="flex gap-3 items-end">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-indigo-900 dark:text-indigo-100 mb-2">Țară</label>
                                    <select x-model="country" required class="w-full rounded-lg border-indigo-300 dark:border-indigo-600 dark:bg-indigo-900/30 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Selectați țara</option>
                                        <option value="RO">România</option>
                                        <option value="MD">Republica Moldova</option>
                                        <option value="US">Statele Unite</option>
                                        <option value="GB">Regatul Unit</option>
                                        <option value="CA">Canada</option>
                                        <option value="AU">Australia</option>
                                        <option value="DE">Germania</option>
                                        <option value="FR">Franța</option>
                                        <option value="ES">Spania</option>
                                        <option value="IT">Italia</option>
                                        <option value="NL">Olanda</option>
                                        <option value="SE">Suedia</option>
                                        <option value="NO">Norvegia</option>
                                        <option value="DK">Danemarca</option>
                                        <option value="FI">Finlanda</option>
                                        <option value="PL">Polonia</option>
                                        <option value="Other">Altele</option>
                                    </select>
                                </div>
                                <button type="submit" :disabled="!country || saving" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white font-medium rounded-lg transition-colors disabled:cursor-not-allowed flex items-center gap-2">
                                    <span x-show="!saving">Salvează</span>
                                    <span x-show="saving" class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Se salvează...
                                    </span>
                                </button>
                            </form>
                        </div>
                    @endif

                    <!-- Detalii Plan -->
                    <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Detalii Plan</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">Nume Plan</p>
                                <p class="text-base font-semibold text-zinc-900 dark:text-white">{{ $plan->name }}</p>
                            </div>

                            @if($subscription)
                                <div>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Ciclu de Facturare</p>
                                    <p class="text-base font-semibold text-zinc-900 dark:text-white">{{ ucfirst($subscription->cycle ?? 'lunar') }}</p>
                                </div>

                                <div>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Status</p>
                                    <p class="text-base font-semibold text-green-600 dark:text-green-400">
                                        {{ ucfirst($subscription->status) }}
                                    </p>
                                </div>

                                @if($subscription->trial_ends_at && \Carbon\Carbon::parse($subscription->trial_ends_at)->isFuture())
                                    <div>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Perioadă de Probă</p>
                                        <p class="text-base font-semibold text-orange-600 dark:text-orange-400">
                                            {{ \Carbon\Carbon::parse($subscription->trial_ends_at)->diffInDays(now()) }} zile rămase
                                        </p>
                                    </div>
                                @endif

                                @if($subscription->ends_at)
                                    <div>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Se Termină La</p>
                                        <p class="text-base font-semibold text-zinc-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($subscription->ends_at)->format('d M Y') }}
                                        </p>
                                    </div>
                                @endif

                                @if($plan && $plan->is_seated_plan && $subscription->seats_purchased)
                                    <div class="md:col-span-2">
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Locuri Echipă</p>
                                        <p class="text-base font-semibold text-zinc-900 dark:text-white">
                                            {{ $subscription->seats_purchased }} {{ $subscription->seats_purchased == 1 ? 'loc' : 'locuri' }}
                                            ({{ $subscription->seats_used ?? 0 }} folosite, {{ ($subscription->seats_purchased - ($subscription->seats_used ?? 0)) }} disponibile)
                                        </p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    @if($plan && $plan->is_seated_plan)
                        <!-- Card Configurare Echipă pentru Planuri cu Locuri -->
                        <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                            <div class="flex items-start gap-4">
                                <svg class="w-10 h-10 text-zinc-900 dark:text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">
                                        Pregătit să Construiești Echipa?
                                    </h3>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                                        Aveți {{ $subscription->seats_purchased ?? 1 }} locuri disponibile. Configurați organizația și invitați membri pentru colaborare.
                                    </p>
                                    <a href="/team" class="inline-flex items-center gap-2 px-6 py-3 bg-zinc-900 hover:bg-black dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-900 font-semibold rounded-lg transition-colors shadow-sm">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Configurează Echipa
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($subscription && $subscription->scheduled_plan_id)
                        @php
                            $scheduledPlan = \Wave\Plan::find($subscription->scheduled_plan_id);
                            $scheduledDate = \Carbon\Carbon::parse($subscription->scheduled_plan_date);
                        @endphp

                        <!-- Avertisment Schimbare Plan Programată -->
                        <div class="p-6 bg-orange-50 dark:bg-orange-900/20 rounded-xl border-2 border-orange-300 dark:border-orange-700">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-orange-900 dark:text-orange-200 mb-2">
                                        Schimbare Plan Programată
                                    </h3>
                                    <p class="text-sm text-orange-800 dark:text-orange-300 mb-3">
                                        Planul dumneavoastră se va schimba de la <strong>{{ $subscription->plan->name }}</strong> la
                                        <strong>{{ $scheduledPlan->name }}</strong> pe data de
                                        <strong>{{ $scheduledDate->format('d F Y') }}</strong>.
                                        Veți continua să beneficiați de funcționalitățile planului curent până atunci.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Funcționalități Plan -->
                    @php
                        $features = [];
                        if (is_string($plan->features)) {
                            $decoded = json_decode($plan->features, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $features = $decoded;
                            } else {
                                $features = array_map('trim', explode(',', $plan->features));
                            }
                        } elseif (is_array($plan->features)) {
                            $features = $plan->features;
                        }
                    @endphp

                    @if(!empty($features))
                        <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                            @php
                                $headingText = "Ce este inclus";
                                if ($plan && $plan->is_seated_plan) {
                                    $headingText .= " (per loc)";
                                }
                            @endphp
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ $headingText }}</h3>
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($features as $feature)
                                    <li class="flex items-start">
                                        <svg class="mr-2 w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Pași Următori -->
                    <div class="p-6 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3">Pași Următori</h3>
                        <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                            <li class="flex items-start">
                                <span class="mr-2">1.</span>
                                <span>Creați primul contract folosind șabloanele disponibile</span>
                            </li>
                            <li class="flex items-start">
                                <span class="mr-2">2.</span>
                                <span>Adăugați părțile implicate și configurați semnăturile electronice</span>
                            </li>
                            <li class="flex items-start">
                                <span class="mr-2">3.</span>
                                <span>Gestionați și monitorizați contractele active din tabloul de bord</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Butoane Acțiune -->
                    <div class="flex flex-wrap gap-3">
                        <a href="/dashboard" class="inline-flex items-center px-6 py-3 bg-zinc-900 hover:bg-black dark:bg-zinc-600 dark:hover:bg-zinc-500 text-white font-medium rounded-lg transition-colors">
                            <x-phosphor-house-duotone class="w-5 h-5 mr-2" />
                            Tablou de Bord
                        </a>
                        <a href="/settings/subscription" class="inline-flex items-center px-6 py-3 bg-white hover:bg-zinc-50 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-900 dark:text-white border border-zinc-200 dark:border-zinc-700 font-medium rounded-lg transition-colors">
                            <x-phosphor-gear-duotone class="w-5 h-5 mr-2" />
                            Gestionează Abonamentul
                        </a>
                    </div>
                </div>
            @else
                <div class="py-5 space-y-5">
                    @if(!$user)
                        <div class="p-6 bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-200 dark:border-orange-800">
                            <p class="text-orange-800 dark:text-orange-200">Vă rugăm să vă autentificați pentru a vizualiza detaliile abonamentului.</p>
                        </div>
                        <div class="flex gap-3">
                            <a href="/login" class="inline-flex items-center px-6 py-3 bg-zinc-900 hover:bg-black dark:bg-zinc-600 dark:hover:bg-zinc-500 text-white font-medium rounded-lg transition-colors">
                                Autentificare
                            </a>
                            <a href="/register" class="inline-flex items-center px-6 py-3 bg-white hover:bg-zinc-50 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-900 dark:text-white border border-zinc-200 dark:border-zinc-700 font-medium rounded-lg transition-colors">
                                Înregistrare
                            </a>
                        </div>
                    @else
                        <div class="p-6 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">Vă mulțumim pentru interes!</h3>
                            <p class="text-blue-800 dark:text-blue-200">Abonamentul dumneavoastră este în curs de procesare. Dacă nu vedeți detaliile abonamentului, vă rugăm să verificați din nou peste câteva momente sau contactați suportul.</p>
                        </div>
                        <div class="flex gap-3">
                            <a href="/dashboard" class="inline-flex items-center px-6 py-3 bg-zinc-900 hover:bg-black dark:bg-zinc-600 dark:hover:bg-zinc-500 text-white font-medium rounded-lg transition-colors">
                                <x-phosphor-house-duotone class="w-5 h-5 mr-2" />
                                Tablou de Bord
                            </a>
                            <a href="/pricing" class="inline-flex items-center px-6 py-3 bg-white hover:bg-zinc-50 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-900 dark:text-white border border-zinc-200 dark:border-zinc-700 font-medium rounded-lg transition-colors">
                                <x-phosphor-shopping-cart-duotone class="w-5 h-5 mr-2" />
                                Vezi Planuri
                            </a>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </x-app.container>
    <x-slot name="javascript">
        <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
        <script>
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
        </script>
    </x-slot>
</x-layouts.app>
