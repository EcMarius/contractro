<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-3xl font-bold text-gray-900">{{ $contract->title }}</h1>
                    <span class="px-3 py-1 text-sm font-semibold rounded-full
                        @if($contract->status === 'draft') bg-gray-100 text-gray-800
                        @elseif($contract->status === 'signed') bg-green-100 text-green-800
                        @elseif($contract->status === 'pending_signature' || $contract->status === 'partially_signed') bg-yellow-100 text-yellow-800
                        @elseif($contract->status === 'completed') bg-blue-100 text-blue-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ ucfirst(str_replace('_', ' ', $contract->status)) }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-600">Contract #{{ $contract->contract_number }}</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('contracts.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Back to List
                </a>
                @can('update', $contract)
                    <a href="{{ route('contracts.edit', $contract->id) }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </a>
                @endcan
                @can('downloadPdf', $contract)
                    <button wire:click="downloadPDF" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Download PDF
                    </button>
                @endcan
            </div>
        </div>

        <!-- Signature Progress -->
        @if($contract->signatures->count() > 0)
            <div class="mb-6 bg-white shadow rounded-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-700">Signature Progress</h3>
                    <span class="text-sm text-gray-600">{{ $contract->signatures->where('status', 'signed')->count() }} / {{ $contract->signatures->count() }} signed</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-green-600 h-2.5 rounded-full transition-all duration-300" style="width: {{ $progressPercentage }}%"></div>
                </div>
            </div>
        @endif

        <!-- Tabs -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                    <button
                        wire:click="setActiveTab('content')"
                        class="@if($activeTab === 'content') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                    >
                        Content
                    </button>
                    <button
                        wire:click="setActiveTab('signatures')"
                        class="@if($activeTab === 'signatures') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                    >
                        Signatures ({{ $contract->signatures->count() }})
                    </button>
                    <button
                        wire:click="setActiveTab('comments')"
                        class="@if($activeTab === 'comments') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                    >
                        Comments ({{ $contract->comments->count() }})
                    </button>
                    <button
                        wire:click="setActiveTab('versions')"
                        class="@if($activeTab === 'versions') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                    >
                        Versions ({{ $contract->versions->count() }})
                    </button>
                    <button
                        wire:click="setActiveTab('details')"
                        class="@if($activeTab === 'details') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                    >
                        Details
                    </button>
                </nav>
            </div>

            <div class="p-6">
                <!-- Content Tab -->
                @if($activeTab === 'content')
                    <div class="prose max-w-none">
                        {!! $contract->content !!}
                    </div>
                @endif

                <!-- Signatures Tab -->
                @if($activeTab === 'signatures')
                    <div class="space-y-4">
                        @forelse($contract->signatures as $signature)
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3">
                                            <h4 class="text-lg font-medium text-gray-900">{{ $signature->signer_name }}</h4>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                @if($signature->status === 'signed') bg-green-100 text-green-800
                                                @elseif($signature->status === 'pending') bg-yellow-100 text-yellow-800
                                                @elseif($signature->status === 'declined') bg-red-100 text-red-800
                                                @else bg-gray-100 text-gray-800
                                                @endif">
                                                {{ ucfirst($signature->status) }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1">{{ $signature->signer_email }}</p>
                                        @if($signature->signer_role)
                                            <p class="text-sm text-gray-500 mt-1">Role: {{ $signature->signer_role }}</p>
                                        @endif

                                        @if($signature->status === 'signed')
                                            <div class="mt-3 text-sm text-gray-700">
                                                <p><strong>Signed on:</strong> {{ $signature->signed_at->format('F d, Y \a\t g:i A') }}</p>
                                                @if($signature->ip_address)
                                                    <p><strong>IP Address:</strong> {{ $signature->ip_address }}</p>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-gray-500 py-8">No signatures requested yet.</p>
                        @endforelse

                        @can('sendForSignature', $contract)
                            <button wire:click="sendForSignature" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Add Signers
                            </button>
                        @endcan
                    </div>
                @endif

                <!-- Comments Tab -->
                @if($activeTab === 'comments')
                    <div class="space-y-6">
                        <!-- Add Comment Form -->
                        <div class="border-b border-gray-200 pb-6">
                            <form wire:submit.prevent="addComment">
                                <label for="newComment" class="block text-sm font-medium text-gray-700 mb-2">
                                    Add a comment
                                </label>
                                <textarea
                                    id="newComment"
                                    wire:model="newComment"
                                    rows="3"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    placeholder="Write your comment..."
                                ></textarea>
                                @error('newComment') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                <button type="submit" class="mt-2 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                    Post Comment
                                </button>
                            </form>
                        </div>

                        <!-- Comments List -->
                        <div class="space-y-4">
                            @forelse($contract->comments as $comment)
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0">
                                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold">
                                                {{ substr($comment->user->name, 0, 2) }}
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <h4 class="text-sm font-medium text-gray-900">{{ $comment->user->name }}</h4>
                                                <span class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p class="mt-1 text-sm text-gray-700">{{ $comment->comment }}</p>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-center text-gray-500 py-8">No comments yet. Be the first to comment!</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                <!-- Versions Tab -->
                @if($activeTab === 'versions')
                    <div class="space-y-4">
                        @forelse($contract->versions as $version)
                            <div class="border-l-4 border-blue-500 bg-blue-50 p-4 rounded-r-lg">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">Version {{ $version->version_number }}</h4>
                                        <p class="text-xs text-gray-600 mt-1">{{ $version->created_at->format('F d, Y \a\t g:i A') }}</p>
                                        @if($version->change_summary)
                                            <p class="text-sm text-gray-700 mt-2">{{ $version->change_summary }}</p>
                                        @endif
                                        <p class="text-xs text-gray-500 mt-1">Changed by: {{ $version->changedBy->name }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-gray-500 py-8">No version history available.</p>
                        @endforelse
                    </div>
                @endif

                <!-- Details Tab -->
                @if($activeTab === 'details')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Created By</h3>
                            <p class="text-base text-gray-900">{{ $contract->user->name }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Created At</h3>
                            <p class="text-base text-gray-900">{{ $contract->created_at->format('F d, Y \a\t g:i A') }}</p>
                        </div>
                        @if($contract->template)
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-1">Template</h3>
                                <p class="text-base text-gray-900">{{ $contract->template->name }}</p>
                            </div>
                        @endif
                        @if($contract->contract_value)
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-1">Contract Value</h3>
                                <p class="text-base text-gray-900">${{ number_format($contract->contract_value, 2) }}</p>
                            </div>
                        @endif
                        @if($contract->effective_date)
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-1">Effective Date</h3>
                                <p class="text-base text-gray-900">{{ \Carbon\Carbon::parse($contract->effective_date)->format('F d, Y') }}</p>
                            </div>
                        @endif
                        @if($contract->expiration_date)
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-1">Expiration Date</h3>
                                <p class="text-base text-gray-900">{{ \Carbon\Carbon::parse($contract->expiration_date)->format('F d, Y') }}</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
