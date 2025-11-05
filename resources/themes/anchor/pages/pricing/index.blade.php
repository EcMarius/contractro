<?php
    use function Laravel\Folio\{middleware, name};
    name('pricing');
?>

<x-layouts.marketing
    :seo="[
        'title' => 'Prețuri - ContractRO',
        'description' => 'Planuri flexibile pentru afaceri de toate dimensiunile. Test gratuit 14 zile, fără card necesar.',
    ]"
>

    {{-- Hero --}}
    <section class="py-16 bg-gradient-to-br from-blue-600 to-purple-600">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                Prețuri Simple și Transparente
            </h1>
            <p class="text-xl text-blue-100">
                Alege planul potrivit pentru afacerea ta. Fără costuri ascunse.
            </p>
            <p class="mt-4 text-lg text-blue-50">
                ✓ Test gratuit 14 zile  ✓ Fără card necesar  ✓ Anulare oricând
            </p>
        </div>
    </section>

    <x-container class="py-10 sm:py-20">
        <x-marketing.sections.pricing />
    </x-container>

    {{-- FAQ Pricing --}}
    <section class="py-16 bg-gray-50 dark:bg-gray-800">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center text-gray-900 dark:text-white mb-12">
                Întrebări Frecvente despre Prețuri
            </h2>
            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-900 rounded-lg p-6 shadow">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        Pot testa gratuit înainte să plătesc?
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Da! Oferim 14 zile de încercare gratuită pentru toate planurile, fără să fie necesar cardul.
                    </p>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-lg p-6 shadow">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        Pot anula abonamentul oricând?
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Da, poți anula abonamentul oricând din setări. Nu există penalizări sau costuri de anulare.
                    </p>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-lg p-6 shadow">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        Este inclus TVA în preț?
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Nu, prețurile afișate sunt fără TVA. Pentru persoane fizice autorizate și firme din România, se adaugă TVA 19%.
                    </p>
                </div>
            </div>
        </div>
    </section>

</x-layouts.marketing>
