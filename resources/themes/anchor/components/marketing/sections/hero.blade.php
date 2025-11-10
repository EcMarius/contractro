@php
    use Wave\Setting;
    $trialDays = Setting::get('trial_days', 7);
@endphp

<section class="flex relative top-0 flex-col justify-center items-center -mt-24 w-full min-h-screen lg:min-h-screen" style="background-color: #f5e42a;">
    <div class="flex flex-col flex-1 gap-6 justify-between items-center px-8 pt-32 mx-auto w-full max-w-2xl text-left md:px-12 xl:px-20 lg:pt-32 lg:pb-16 lg:max-w-7xl lg:flex-row">
        <div class="w-full lg:w-1/2">
            <div class="inline-flex items-center px-4 py-2 mb-6 text-sm font-medium rounded-full bg-black text-yellow-400 border border-black">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Începe Perioada de Probă Gratuită de {{ $trialDays }} Zile
            </div>
            <h1 class="text-6xl font-bold tracking-tighter text-left sm:text-7xl md:text-[84px] sm:text-center lg:text-left text-black text-balance">
                <span class="block origin-left lg:scale-90">Gestionează Contracte</span> <span class="pr-4">cu Ușurință</span>
            </h1>
            <p class="mx-auto mt-5 text-lg font-normal text-left md:text-xl sm:max-w-md lg:ml-0 lg:max-w-lg sm:text-center lg:text-left text-black">
                Creează, semnează și gestionează contracte în conformitate cu legislația română<span class="hidden sm:inline">. Semnături electronice, șabloane pregătite și monitorizare automată</span>.
            </p>
            <div class="flex flex-col gap-3 justify-center items-center mx-auto mt-8 md:gap-2 lg:justify-start md:ml-0 md:flex-row">
                <x-button tag="a" href="/register" size="lg" class="w-full lg:w-auto bg-black hover:bg-gray-900 text-yellow-400 border-black">Începe Gratuit</x-button>
                <x-button tag="a" href="#pricing" size="lg" color="secondary" class="w-full lg:w-auto bg-white hover:bg-gray-50 text-black border-black">Vezi Prețuri</x-button>
            </div>
        </div>
        <div class="relative w-full lg:w-1/2">
            <div class="relative">
                <dotlottie-player
                    src="https://lottie.host/f1e2cf8c-7ef1-4383-924c-fbbb669ad235/CTZ8B4nrvb.lottie"
                    background="transparent"
                    speed="1"
                    style="width: 100%; height: auto;"
                    loop
                    autoplay>
                </dotlottie-player>
            </div>
        </div>
    </div>
    <script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script>
</section>
