<?php
use function Laravel\Folio\{name};
name('about');
?>

<x-layouts.marketing
    :seo="[
        'title' => 'Despre Noi - ContractRO',
        'description' => 'Aflați mai multe despre ContractRO, platforma românească de gestiune contracte electronice.',
    ]"
>

    <section class="py-20 bg-white dark:bg-gray-900">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-5xl font-bold text-gray-900 dark:text-white mb-8">
                Despre ContractRO
            </h1>

            <div class="prose prose-lg dark:prose-invert max-w-none">
                <p class="text-xl text-gray-600 dark:text-gray-400 mb-8">
                    ContractRO este platforma românească completă pentru gestiunea contractelor și semnăturilor electronice, creată special pentru nevoile afacerilor din România.
                </p>

                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mt-12 mb-4">Misiunea Noastră</h2>
                <p class="text-gray-600 dark:text-gray-400">
                    Simplifică procesul de creare, semnare și gestionare a contractelor pentru afacerile românești. Vrem să eliminăm birocrația inutilă și să facem contractele electronice accesibile pentru toată lumea.
                </p>

                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mt-12 mb-4">De Ce ContractRO?</h2>
                <ul class="space-y-4 text-gray-600 dark:text-gray-400">
                    <li class="flex items-start">
                        <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span><strong>100% Legal</strong> - Conform EU eIDAS Regulation 910/2014</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span><strong>Specific României</strong> - CUI validation, format românesc facturi, integrare ANAF</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span><strong>Simplu și Rapid</strong> - Creează un contract în mai puțin de 1 minut</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span><strong>Securizat</strong> - Encriptare SSL, backup automat, conformitate GDPR</span>
                    </li>
                </ul>

                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mt-12 mb-4">Contact</h2>
                <p class="text-gray-600 dark:text-gray-400">
                    Email: <a href="mailto:contact@contractro.ro" class="text-blue-600 hover:text-blue-700">contact@contractro.ro</a><br>
                    Pentru suport tehnic: <a href="mailto:support@contractro.ro" class="text-blue-600 hover:text-blue-700">support@contractro.ro</a>
                </p>
            </div>

            <div class="mt-12 text-center">
                <a href="{{ route('register') }}"
                   class="inline-flex items-center px-8 py-4 bg-blue-600 border border-transparent rounded-lg font-semibold text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none transition">
                    Începe Acum Gratuit
                </a>
            </div>
        </div>
    </section>

</x-layouts.marketing>
