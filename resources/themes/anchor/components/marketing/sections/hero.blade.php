@php
    use Wave\Setting;
    $trialDays = Setting::get('trial_days', 7);
@endphp

<section class="flex relative top-0 flex-col justify-center items-center -mt-24 w-full min-h-screen bg-white lg:min-h-screen">
    <div class="flex flex-col flex-1 gap-6 justify-between items-center px-8 pt-32 mx-auto w-full max-w-2xl text-left md:px-12 xl:px-20 lg:pt-32 lg:pb-16 lg:max-w-7xl lg:flex-row">
        <div class="w-full lg:w-1/2">
            <div class="inline-flex items-center px-4 py-2 mb-6 text-sm font-medium rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Începe Perioada de Probă Gratuită de {{ $trialDays }} Zile
            </div>
            <h1 class="text-6xl font-bold tracking-tighter text-left sm:text-7xl md:text-[84px] sm:text-center lg:text-left text-zinc-900 text-balance">
                <span class="block origin-left lg:scale-90">Gestionează Contracte</span> <span class="pr-4 text-transparent bg-clip-text bg-gradient-to-b text-neutral-600 from-neutral-900 to-neutral-500">cu Ușurință</span>
            </h1>
            <p class="mx-auto mt-5 text-lg font-normal text-left md:text-xl sm:max-w-md lg:ml-0 lg:max-w-lg sm:text-center lg:text-left text-zinc-500">
                Creează, semnează și gestionează contracte în conformitate cu legislația română<span class="hidden sm:inline">. Semnături electronice, șabloane pregătite și monitorizare automată</span>.
            </p>
            <div class="flex flex-col gap-3 justify-center items-center mx-auto mt-8 md:gap-2 lg:justify-start md:ml-0 md:flex-row">
                <x-button tag="a" href="/register" size="lg" class="w-full lg:w-auto">Începe Gratuit</x-button>
                <x-button tag="a" href="#pricing" size="lg" color="secondary" class="w-full lg:w-auto">Vezi Prețuri</x-button>
            </div>
            <div class="flex flex-col gap-2 mt-8 text-sm text-zinc-500 sm:flex-row sm:items-center">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-1.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Fără card de credit
                </div>
                <div class="hidden sm:block text-zinc-300">•</div>
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-1.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Anulare oricând
                </div>
                <div class="hidden sm:block text-zinc-300">•</div>
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-1.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Configurare în 5 minute
                </div>
            </div>
        </div>
        <div class="relative w-full lg:w-1/2">
            <div class="relative">
                <div class="absolute -inset-4 bg-gradient-to-r from-blue-100 to-indigo-100 rounded-2xl blur-2xl opacity-20"></div>
                <img src="/images/contract-hero.png" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27800%27 height=%27600%27%3E%3Crect fill=%27%23f3f4f6%27 width=%27800%27 height=%27600%27/%3E%3Ctext x=%2750%25%27 y=%2750%25%27 dominant-baseline=%27middle%27 text-anchor=%27middle%27 font-family=%27sans-serif%27 font-size=%2724%27 fill=%27%239ca3af%27%3EContract Management%3C/text%3E%3C/svg%3E'" alt="Contract Management Platform" class="relative w-full rounded-2xl shadow-2xl">
            </div>
        </div>
    </div>
</section>
