<div class="min-h-screen bg-gray-100 py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow rounded-lg mb-6 p-6">
            <h1 class="text-2xl font-bold text-gray-900">{{ $contract->title }}</h1>
            <p class="mt-2 text-sm text-gray-600">Contract #{{ $contract->contract_number }}</p>
            <div class="mt-4 flex items-center space-x-2">
                <span class="text-sm font-medium text-gray-700">Signer:</span>
                <span class="text-sm text-gray-900">{{ $signature->signer_name }} ({{ $signature->signer_email }})</span>
            </div>
        </div>

        @if($signature->status === 'signed')
            <!-- Already Signed -->
            <div class="bg-green-50 border-l-4 border-green-400 p-6 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Contract Signed</h3>
                        <div class="mt-2 text-sm text-green-700">
                            <p>You signed this contract on {{ $signature->signed_at->format('F d, Y \a\t g:i A') }}.</p>
                            <p class="mt-2">Thank you for completing this process.</p>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($signature->status === 'declined')
            <!-- Declined -->
            <div class="bg-red-50 border-l-4 border-red-400 p-6 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Contract Declined</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>You declined to sign this contract on {{ $signature->declined_at->format('F d, Y \a\t g:i A') }}.</p>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($signature->isExpired())
            <!-- Expired -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Signature Request Expired</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>This signature request expired on {{ $signature->expires_at->format('F d, Y \a\t g:i A') }}.</p>
                            <p class="mt-2">Please contact the contract owner for a new signature request.</p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- Contract Content -->
            <div class="bg-white shadow rounded-lg mb-6 p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Contract Terms</h2>
                <div class="prose max-w-none border border-gray-200 rounded-lg p-4 max-h-96 overflow-y-auto">
                    {!! $contract->content !!}
                </div>
            </div>

            <!-- Signature Section -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Sign Contract</h2>

                <!-- Signature Type Selector -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Signature Method</label>
                    <div class="flex space-x-4">
                        <button
                            type="button"
                            wire:click="setSignatureType('drawn')"
                            class="flex-1 py-2 px-4 border rounded-md text-sm font-medium @if($signatureType === 'drawn') border-blue-500 bg-blue-50 text-blue-700 @else border-gray-300 text-gray-700 hover:bg-gray-50 @endif"
                        >
                            Draw Signature
                        </button>
                        <button
                            type="button"
                            wire:click="setSignatureType('typed')"
                            class="flex-1 py-2 px-4 border rounded-md text-sm font-medium @if($signatureType === 'typed') border-blue-500 bg-blue-50 text-blue-700 @else border-gray-300 text-gray-700 hover:bg-gray-50 @endif"
                        >
                            Type Signature
                        </button>
                    </div>
                </div>

                <!-- Draw Signature -->
                @if($signatureType === 'drawn')
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Draw Your Signature</label>
                        <div class="border-2 border-gray-300 rounded-lg bg-white">
                            <canvas
                                id="signature-canvas"
                                width="760"
                                height="200"
                                class="w-full cursor-crosshair"
                                style="touch-action: none;"
                            ></canvas>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <p class="text-sm text-gray-500">Sign above using your mouse or touchscreen</p>
                            <button
                                type="button"
                                onclick="clearCanvas()"
                                class="text-sm text-red-600 hover:text-red-800"
                            >
                                Clear
                            </button>
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const canvas = document.getElementById('signature-canvas');
                            const ctx = canvas.getContext('2d');
                            let isDrawing = false;
                            let lastX = 0;
                            let lastY = 0;

                            // Set canvas size properly
                            function resizeCanvas() {
                                const rect = canvas.getBoundingClientRect();
                                canvas.width = rect.width * 2;
                                canvas.height = 400;
                                ctx.scale(2, 2);
                                ctx.strokeStyle = '#000';
                                ctx.lineWidth = 2;
                                ctx.lineCap = 'round';
                                ctx.lineJoin = 'round';
                            }

                            resizeCanvas();
                            window.addEventListener('resize', resizeCanvas);

                            function startDrawing(e) {
                                isDrawing = true;
                                const rect = canvas.getBoundingClientRect();
                                [lastX, lastY] = [
                                    e.clientX - rect.left || e.touches[0].clientX - rect.left,
                                    e.clientY - rect.top || e.touches[0].clientY - rect.top
                                ];
                            }

                            function draw(e) {
                                if (!isDrawing) return;
                                e.preventDefault();

                                const rect = canvas.getBoundingClientRect();
                                const currentX = e.clientX - rect.left || e.touches[0].clientX - rect.left;
                                const currentY = e.clientY - rect.top || e.touches[0].clientY - rect.top;

                                ctx.beginPath();
                                ctx.moveTo(lastX, lastY);
                                ctx.lineTo(currentX, currentY);
                                ctx.stroke();

                                [lastX, lastY] = [currentX, currentY];
                            }

                            function stopDrawing() {
                                if (isDrawing) {
                                    isDrawing = false;
                                    // Save signature as base64
                                    const signatureData = canvas.toDataURL('image/png');
                                    @this.set('signatureData', signatureData);
                                }
                            }

                            // Mouse events
                            canvas.addEventListener('mousedown', startDrawing);
                            canvas.addEventListener('mousemove', draw);
                            canvas.addEventListener('mouseup', stopDrawing);
                            canvas.addEventListener('mouseout', stopDrawing);

                            // Touch events
                            canvas.addEventListener('touchstart', startDrawing);
                            canvas.addEventListener('touchmove', draw);
                            canvas.addEventListener('touchend', stopDrawing);

                            // Clear function
                            window.clearCanvas = function() {
                                ctx.clearRect(0, 0, canvas.width, canvas.height);
                                @this.set('signatureData', null);
                            };
                        });
                    </script>
                @endif

                <!-- Type Signature -->
                @if($signatureType === 'typed')
                    <div class="mb-6">
                        <label for="typedSignature" class="block text-sm font-medium text-gray-700 mb-2">
                            Type Your Full Name
                        </label>
                        <input
                            type="text"
                            id="typedSignature"
                            wire:model="typedSignature"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="John Doe"
                        >
                        @if($typedSignature)
                            <div class="mt-4 p-4 border-2 border-gray-300 rounded-lg bg-gray-50">
                                <p class="text-3xl font-signature text-center" style="font-family: 'Brush Script MT', cursive;">
                                    {{ $typedSignature }}
                                </p>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Agreement Checkbox -->
                <div class="mb-6">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input
                                id="agreedToTerms"
                                type="checkbox"
                                wire:model="agreedToTerms"
                                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                            >
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="agreedToTerms" class="font-medium text-gray-700">
                                I agree to the terms and conditions outlined in this contract
                            </label>
                            <p class="text-gray-500">By signing, you acknowledge that you have read, understood, and agree to be bound by the terms of this contract.</p>
                        </div>
                    </div>
                    @error('agreedToTerms') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between">
                    <button
                        type="button"
                        wire:click="decline"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    >
                        Decline to Sign
                    </button>
                    <button
                        type="button"
                        wire:click="sign"
                        class="inline-flex items-center px-6 py-3 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="-ml-1 mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Sign Contract
                    </button>
                </div>
            </div>

            <!-- Decline Modal -->
            @if($declined)
                <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Decline to Sign
                                </h3>
                                <div class="mt-2">
                                    <label for="declineReason" class="block text-sm font-medium text-gray-700 mb-2">
                                        Please provide a reason (optional)
                                    </label>
                                    <textarea
                                        id="declineReason"
                                        wire:model="declineReason"
                                        rows="4"
                                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        placeholder="Enter your reason for declining..."
                                    ></textarea>
                                    @error('declineReason') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button
                                    wire:click="decline"
                                    type="button"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm"
                                >
                                    Confirm Decline
                                </button>
                                <button
                                    wire:click="$set('declined', false)"
                                    type="button"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif

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
