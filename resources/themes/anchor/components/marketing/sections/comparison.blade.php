@php
    use Wave\Setting;
    $trialDays = Setting::get('trial_days', 7);
@endphp

<section id="comparison" class="relative">
    <div class="relative px-8 mx-auto max-w-7xl md:px-12 lg:px-16">
        <div class="max-w-3xl mx-auto text-center">
            <p class="text-sm font-medium tracking-widest text-blue-600 uppercase">Comparație</p>
            <h2 class="mt-8 text-3xl font-semibold tracking-tight md:text-5xl lg:text-6xl font-display text-balance">
                De Ce ContractRO?
            </h2>
            <p class="mx-auto mt-4 text-base text-zinc-500 lg:text-xl text-balance">
                Economisește timp și elimină erorile cu un sistem profesional de gestionare contracte
            </p>
        </div>

        <div class="max-w-7xl mx-auto mt-16">
            <div class="overflow-hidden bg-white border-2 shadow-2xl rounded-3xl border-yellow-400">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr style="background-color: #f5e42a;">
                                <th class="px-8 py-5 text-left">
                                    <span class="text-base font-bold text-black">Funcționalitate</span>
                                </th>
                                <th class="px-8 py-5 text-center">
                                    <span class="text-base font-bold text-black">Manual / Excel</span>
                                </th>
                                <th class="px-8 py-5 text-center bg-white rounded-t-xl">
                                    <div class="inline-flex items-center gap-2">
                                        <span class="text-xl font-bold text-black">ContractRO</span>
                                        <span class="px-3 py-1.5 text-xs font-bold text-black rounded-full" style="background-color: #f5e42a;">Recomandat</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <tr>
                                <td class="px-6 py-4 font-medium text-zinc-900">Șabloane Contracte Pregătite</td>
                                <td class="px-6 py-4 text-center">
                                    <svg class="inline w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </td>
                                <td class="px-6 py-4 text-center bg-blue-50/50">
                                    <svg class="inline w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-medium text-zinc-900">Semnături Electronice</td>
                                <td class="px-6 py-4 text-center">
                                    <svg class="inline w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </td>
                                <td class="px-6 py-4 text-center bg-blue-50/50">
                                    <svg class="inline w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-medium text-zinc-900">Workflow Aprobare</td>
                                <td class="px-6 py-4 text-center text-sm text-zinc-500">Email</td>
                                <td class="px-6 py-4 text-center bg-blue-50/50">
                                    <svg class="inline w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-medium text-zinc-900">Notificări Automate</td>
                                <td class="px-6 py-4 text-center">
                                    <svg class="inline w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </td>
                                <td class="px-6 py-4 text-center bg-blue-50/50">
                                    <svg class="inline w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-medium text-zinc-900">Raportare & Analytics</td>
                                <td class="px-6 py-4 text-center text-sm text-zinc-500">Manual</td>
                                <td class="px-6 py-4 text-center bg-blue-50/50">
                                    <span class="text-sm font-semibold text-blue-600">Timp Real</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-medium text-zinc-900">Securitate & Backup</td>
                                <td class="px-6 py-4 text-center text-sm text-zinc-500">Risc Pierdere</td>
                                <td class="px-6 py-4 text-center bg-blue-50/50">
                                    <span class="text-sm font-semibold text-blue-600">Cloud Securizat</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-medium text-zinc-900">Conformitate GDPR</td>
                                <td class="px-6 py-4 text-center text-sm text-zinc-500">Manual</td>
                                <td class="px-6 py-4 text-center bg-blue-50/50">
                                    <svg class="inline w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-medium text-zinc-900">Colaborare în Echipă</td>
                                <td class="px-6 py-4 text-center text-sm text-zinc-500">Limitată</td>
                                <td class="px-6 py-4 text-center bg-blue-50/50">
                                    <span class="text-sm font-semibold text-blue-600">Multi-Utilizator</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-12 text-center">
                <p class="text-lg font-semibold text-zinc-900 mb-4">
                    Încercați ContractRO Gratuit pentru {{ $trialDays }} Zile
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="/register" class="inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-white bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg hover:from-blue-700 hover:to-indigo-700 transition shadow-lg">
                        Începe Acum
                    </a>
                    <a href="#pricing" class="inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-zinc-900 bg-white border-2 border-zinc-200 rounded-lg hover:bg-zinc-50 transition">
                        Vezi Prețuri
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
