<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <form wire:submit.prevent="save">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        {{ $contractId ? 'Edit Contract' : 'Create Contract' }}
                    </h1>
                    @if($contractId)
                        <p class="mt-1 text-sm text-gray-600">Contract #{{ $contract->contract_number }}</p>
                    @endif
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('contracts.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save
                    </button>
                    @if($contractId)
                        <button type="button" wire:click="saveAndContinue" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                            Save & View
                        </button>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Editor -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Title -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Contract Title <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="title"
                            wire:model="title"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="Enter contract title..."
                        >
                        @error('title') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Content Editor -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <label for="content" class="block text-sm font-medium text-gray-700">
                                Contract Content <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center space-x-2">
                                @if($canUseAI())
                                    <button
                                        type="button"
                                        wire:click="toggleAIPanel"
                                        class="inline-flex items-center px-3 py-1 border border-purple-300 shadow-sm text-xs font-medium rounded text-purple-700 bg-purple-50 hover:bg-purple-100"
                                    >
                                        <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                        AI Assistant
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="improveWithAI"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center px-3 py-1 border border-indigo-300 shadow-sm text-xs font-medium rounded text-indigo-700 bg-indigo-50 hover:bg-indigo-100"
                                    >
                                        <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                        </svg>
                                        Improve
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if($showAIPanel)
                            <div class="mb-4 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                                <label class="block text-sm font-medium text-purple-900 mb-2">
                                    AI Generation Prompt
                                </label>
                                <textarea
                                    wire:model="aiPrompt"
                                    rows="3"
                                    class="block w-full border-purple-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 sm:text-sm"
                                    placeholder="Describe what you want the AI to generate..."
                                ></textarea>
                                <div class="mt-2 flex items-center justify-end space-x-2">
                                    <button
                                        type="button"
                                        wire:click="toggleAIPanel"
                                        class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="generateWithAI"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700"
                                    >
                                        <span wire:loading.remove wire:target="generateWithAI">Generate</span>
                                        <span wire:loading wire:target="generateWithAI">Generating...</span>
                                    </button>
                                </div>
                            </div>
                        @endif

                        <div class="border border-gray-300 rounded-lg">
                            <div id="editor-toolbar" class="flex flex-wrap items-center gap-1 p-2 border-b border-gray-300 bg-gray-50">
                                <!-- Text Formatting -->
                                <button type="button" class="p-2 hover:bg-gray-200 rounded" title="Bold" onclick="document.execCommand('bold', false, null);">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"></path>
                                    </svg>
                                </button>
                                <button type="button" class="p-2 hover:bg-gray-200 rounded" title="Italic" onclick="document.execCommand('italic', false, null);">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m6 4l-4-4-4 4M6 16l-2-2 2-2"></path>
                                    </svg>
                                </button>
                                <button type="button" class="p-2 hover:bg-gray-200 rounded" title="Underline" onclick="document.execCommand('underline', false, null);">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18h12"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 2v8a4 4 0 008 0V2"></path>
                                    </svg>
                                </button>

                                <div class="w-px h-6 bg-gray-300 mx-1"></div>

                                <!-- Lists -->
                                <button type="button" class="p-2 hover:bg-gray-200 rounded" title="Bullet List" onclick="document.execCommand('insertUnorderedList', false, null);">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                                    </svg>
                                </button>
                                <button type="button" class="p-2 hover:bg-gray-200 rounded" title="Numbered List" onclick="document.execCommand('insertOrderedList', false, null);">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                </button>

                                <div class="w-px h-6 bg-gray-300 mx-1"></div>

                                <!-- Headings -->
                                <select onchange="document.execCommand('formatBlock', false, this.value); this.selectedIndex=0;" class="text-sm border-gray-300 rounded">
                                    <option selected>Paragraph</option>
                                    <option value="h1">Heading 1</option>
                                    <option value="h2">Heading 2</option>
                                    <option value="h3">Heading 3</option>
                                </select>
                            </div>

                            <div
                                contenteditable="true"
                                id="content-editor"
                                class="min-h-[500px] p-4 prose max-w-none focus:outline-none"
                                wire:model="content"
                                wire:ignore
                            >{!! $content !!}</div>
                        </div>
                        @error('content') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const editor = document.getElementById('content-editor');

                                editor.addEventListener('blur', function() {
                                    @this.set('content', editor.innerHTML);
                                });

                                editor.addEventListener('input', function() {
                                    @this.set('content', editor.innerHTML);
                                });
                            });
                        </script>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Contract Details -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Contract Details</h3>

                        <div class="space-y-4">
                            <div>
                                <label for="contractValue" class="block text-sm font-medium text-gray-700 mb-1">
                                    Contract Value ($)
                                </label>
                                <input
                                    type="number"
                                    id="contractValue"
                                    wire:model="contractValue"
                                    step="0.01"
                                    min="0"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    placeholder="0.00"
                                >
                                @error('contractValue') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="effectiveDate" class="block text-sm font-medium text-gray-700 mb-1">
                                    Effective Date
                                </label>
                                <input
                                    type="date"
                                    id="effectiveDate"
                                    wire:model="effectiveDate"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                                @error('effectiveDate') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="expirationDate" class="block text-sm font-medium text-gray-700 mb-1">
                                    Expiration Date
                                </label>
                                <input
                                    type="date"
                                    id="expirationDate"
                                    wire:model="expirationDate"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                                @error('expirationDate') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Variables -->
                    @if(!empty($availableVariables))
                        <div class="bg-white shadow rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Available Variables</h3>
                            <p class="text-sm text-gray-600 mb-4">
                                These variables are detected in your contract content:
                            </p>
                            <div class="space-y-2">
                                @foreach($availableVariables as $variable)
                                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                        <code class="text-sm text-blue-600">{{ '{{' . $variable . '}}' }}</code>
                                        <button
                                            type="button"
                                            wire:click="insertVariable('{{ $variable }}')"
                                            class="text-xs text-gray-600 hover:text-gray-900"
                                        >
                                            Insert
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Tips -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <h3 class="text-sm font-medium text-blue-900 mb-2">Tips</h3>
                        <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
                            <li>Use variables like {{company_name}} for dynamic content</li>
                            <li>Save regularly to avoid losing your work</li>
                            @if($canUseAI())
                                <li>Use AI Assistant to generate or improve content</li>
                            @endif
                            <li>Changes create automatic version history</li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>

        <!-- Flash Messages -->
        @if(session()->has('message'))
            <div class="fixed bottom-4 right-4 bg-green-50 border-l-4 border-green-400 p-4 shadow-lg rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">{{ session('message') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(session()->has('error'))
            <div class="fixed bottom-4 right-4 bg-red-50 border-l-4 border-red-400 p-4 shadow-lg rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
