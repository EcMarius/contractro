<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bun venit la ContractRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-3xl w-full">
            <!-- Main Card -->
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-8 text-white text-center">
                    <h1 class="text-5xl font-bold mb-2">ContractRO</h1>
                    <p class="text-xl opacity-90">Platforma Română de Gestionare Contracte</p>
                </div>

                <!-- Content -->
                <div class="p-8 space-y-6">
                    <div class="text-center">
                        <h2 class="text-3xl font-bold text-gray-800 mb-3">Bun venit!</h2>
                        <p class="text-lg text-gray-600">
                            Pentru a începe să folosiți platforma, trebuie să finalizați instalarea.
                        </p>
                    </div>

                    <!-- Features Grid -->
                    <div class="grid md:grid-cols-3 gap-4 py-6">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <svg class="w-12 h-12 mx-auto mb-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="font-semibold text-gray-800">Șabloane Contracte</h3>
                            <p class="text-sm text-gray-600 mt-1">Modele pregătite</p>
                        </div>
                        <div class="text-center p-4 bg-indigo-50 rounded-lg">
                            <svg class="w-12 h-12 mx-auto mb-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                            </svg>
                            <h3 class="font-semibold text-gray-800">Semnături Electronice</h3>
                            <p class="text-sm text-gray-600 mt-1">Semnare digitală</p>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <svg class="w-12 h-12 mx-auto mb-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <h3 class="font-semibold text-gray-800">Rapoarte & Analytics</h3>
                            <p class="text-sm text-gray-600 mt-1">Monitorizare avansată</p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center pt-4">
                        <a href="/install" class="flex items-center justify-center gap-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-4 rounded-lg hover:from-blue-700 hover:to-indigo-700 transition font-semibold text-lg shadow-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Începe Instalarea
                        </a>
                        <a href="/auth/login" class="flex items-center justify-center gap-2 bg-white border-2 border-gray-300 text-gray-700 px-8 py-4 rounded-lg hover:bg-gray-50 transition font-semibold text-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            Autentificare
                        </a>
                    </div>

                    <!-- Footer Note -->
                    <div class="text-center pt-6 border-t border-gray-200">
                        <p class="text-sm text-gray-500">
                            Powered by <span class="font-semibold">Wave SaaS Framework</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
