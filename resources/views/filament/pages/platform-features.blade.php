<div>
    <div class="space-y-6">
        {{-- Hero Section --}}
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg p-8 text-white">
            <h1 class="text-4xl font-bold mb-2">Contractro Platform</h1>
            <p class="text-xl opacity-90">Enterprise Contract & License Management System</p>
            <div class="mt-6 grid grid-cols-4 gap-4">
                <div class="bg-white/10 rounded-lg p-4 backdrop-blur">
                    <div class="text-3xl font-bold">{{ number_format($statistics['codebase']['Lines of Code']) }}</div>
                    <div class="text-sm opacity-75">Lines of Code</div>
                </div>
                <div class="bg-white/10 rounded-lg p-4 backdrop-blur">
                    <div class="text-3xl font-bold">{{ $statistics['api']['Total Routes'] }}</div>
                    <div class="text-sm opacity-75">API Routes</div>
                </div>
                <div class="bg-white/10 rounded-lg p-4 backdrop-blur">
                    <div class="text-3xl font-bold">{{ $statistics['codebase']['Filament Resources'] }}</div>
                    <div class="text-sm opacity-75">Admin Resources</div>
                </div>
                <div class="bg-white/10 rounded-lg p-4 backdrop-blur">
                    <div class="text-3xl font-bold">{{ $statistics['database']['Total Records'] }}</div>
                    <div class="text-sm opacity-75">Total Records</div>
                </div>
            </div>
        </div>

        {{-- Contract Management System --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold">Contract Management System</h2>
                        <p class="text-sm text-gray-500">AI-powered digital contract creation and signing</p>
                    </div>
                </div>
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($contractFeatures['core'] as $name => $feature)
                    <div class="border rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="font-semibold text-gray-900">{{ $name }}</h3>
                            @if($feature['enabled'])
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 mb-3">{{ $feature['description'] }}</p>

                        @if(isset($feature['routes']))
                            <div class="text-xs text-gray-500">
                                <strong>Routes:</strong> {{ implode(', ', array_slice($feature['routes'], 0, 2)) }}
                                @if(count($feature['routes']) > 2)
                                    <span class="text-gray-400">+{{ count($feature['routes']) - 2 }} more</span>
                                @endif
                            </div>
                        @endif

                        @if(isset($feature['count']))
                            <div class="text-xs text-gray-500"><strong>Count:</strong> {{ $feature['count'] }}</div>
                        @endif

                        @if(isset($feature['library']))
                            <div class="text-xs text-gray-500"><strong>Library:</strong> {{ $feature['library'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-6 grid grid-cols-5 gap-4 border-t pt-4">
                @foreach($contractFeatures['statistics'] as $label => $value)
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ number_format($value) }}</div>
                        <div class="text-xs text-gray-500 uppercase">{{ str_replace('_', ' ', $label) }}</div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- License Management System --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold">License Management System</h2>
                        <p class="text-sm text-gray-500">Software licensing with validation and automation</p>
                    </div>
                </div>
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($licenseFeatures['core'] as $name => $feature)
                    <div class="border rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="font-semibold text-gray-900">{{ $name }}</h3>
                            @if($feature['enabled'])
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 mb-3">{{ $feature['description'] }}</p>

                        @if(isset($feature['types']))
                            <div class="text-xs text-gray-500 mb-1">
                                <strong>Types:</strong>
                                @foreach($feature['types'] as $type)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700 mr-1">{{ $type }}</span>
                                @endforeach
                            </div>
                        @endif

                        @if(isset($feature['limits']))
                            <div class="text-xs text-gray-500">
                                <strong>Limits:</strong>
                                @foreach($feature['limits'] as $limit)
                                    <div class="ml-2">- {{ $limit }}</div>
                                @endforeach
                            </div>
                        @endif

                        @if(isset($feature['modes']))
                            <div class="text-xs text-gray-500">
                                <strong>Modes:</strong> {{ implode(', ', $feature['modes']) }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-6 grid grid-cols-4 gap-4 border-t pt-4">
                @foreach($licenseFeatures['statistics'] as $label => $value)
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">{{ number_format($value) }}</div>
                        <div class="text-xs text-gray-500 uppercase">{{ str_replace('_', ' ', $label) }}</div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Lead Management (EvenLeads) --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold">Lead Management (EvenLeads)</h2>
                        <p class="text-sm text-gray-500">AI-powered lead discovery and engagement</p>
                    </div>
                </div>
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($leadFeatures['core'] as $name => $feature)
                    <div class="border rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="font-semibold text-gray-900">{{ $name }}</h3>
                            @if($feature['enabled'])
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 mb-3">{{ $feature['description'] }}</p>

                        @if(isset($feature['platforms']))
                            <div class="text-xs text-gray-500">
                                <strong>Platforms:</strong>
                                @foreach($feature['platforms'] as $platform)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-50 text-green-700 mr-1">{{ $platform }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-6 grid grid-cols-3 gap-4 border-t pt-4">
                @foreach($leadFeatures['statistics'] as $label => $value)
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">{{ number_format($value) }}</div>
                        <div class="text-xs text-gray-500 uppercase">{{ str_replace('_', ' ', $label) }}</div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Security & Infrastructure --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Security Features --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <h2 class="text-lg font-bold">Security Features</h2>
                    </div>
                </x-slot>

                <div class="space-y-4">
                    @foreach($securityFeatures as $category => $features)
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 uppercase mb-2">{{ ucfirst($category) }}</h3>
                            <div class="space-y-1">
                                @foreach($features as $feature => $description)
                                    <div class="flex items-start gap-2 text-sm">
                                        <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <div>
                                            <span class="font-medium">{{ $feature }}:</span>
                                            <span class="text-gray-600">{{ $description }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>

            {{-- Integrations --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                        </svg>
                        <h2 class="text-lg font-bold">Integrations</h2>
                    </div>
                </x-slot>

                <div class="space-y-3">
                    @foreach($integrations as $category => $services)
                        @foreach($services as $service => $details)
                            <div class="border rounded-lg p-3">
                                <div class="flex items-center justify-between mb-1">
                                    <h3 class="font-semibold text-gray-900">{{ $service }}</h3>
                                    @if($details['enabled'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            Connected
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-600 mb-2">{{ $details['type'] }}</p>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($details['features'] as $feature)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700">{{ $feature }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </x-filament::section>
        </div>

        {{-- Technical Stack --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    <h2 class="text-lg font-bold">Technical Stack</h2>
                </div>
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($technicalStack as $category => $technologies)
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">{{ ucfirst($category) }}</h3>
                        <div class="space-y-2">
                            @foreach($technologies as $tech => $version)
                                <div class="text-sm">
                                    <span class="font-medium text-gray-900">{{ $tech }}:</span>
                                    <span class="text-gray-600">{{ $version }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Platform Statistics --}}
        <x-filament::section>
            <x-slot name="heading">
                <h2 class="text-lg font-bold">Platform Statistics</h2>
            </x-slot>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($statistics['codebase'] as $label => $value)
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <div class="text-xl font-bold text-gray-900">{{ is_numeric($value) ? number_format($value) : $value }}</div>
                        <div class="text-xs text-gray-500">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Footer --}}
        <div class="text-center text-sm text-gray-500 py-4">
            <p>Platform Version: 1.0.0 | Last Updated: {{ now()->format('F j, Y') }}</p>
            <p class="mt-1">Production Ready | Enterprise Grade | Fully Featured</p>
        </div>
    </div>
</div>
