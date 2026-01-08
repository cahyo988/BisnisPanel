<div>
    @if ($showModal)
        <div class="qr-modal-overlay">
            <div class="qr-modal-content">
                <!-- Close X Button -->
                <button type="button" wire:click="closeModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors z-10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <div class="text-center p-6">
                    <h3 class="text-lg font-semibold mb-2">{{ __('QR Connection') }}</h3>
                    
                    @if ($device)
                        <p class="text-sm text-neutral-600 mb-4">
                            {{ __('Scan this code with WhatsApp to connect ":name".', ['name' => $device->name]) }}
                        </p>

                        @if ($qrSvg)
                            <div class="mx-auto w-72 rounded-2xl border border-slate-200 bg-white p-4">
                                {!! $qrSvg !!}
                            </div>
                            <p class="text-xs text-neutral-400 mt-3">
                                {{ __('Generated at') }}: {{ now()->format('H:i:s') }}
                            </p>
                        @else
                            <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-8" wire:poll.2s="showQrModal({{ $device->id }})">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <svg class="h-8 w-8 animate-spin text-neutral-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-sm text-neutral-500">{{ __('Waiting for QR code from WhatsApp...') }}</span>
                                </div>
                            </div>
                        @endif

                        <div class="mt-6 flex gap-2 justify-center">
                            <button type="button" wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600 transition-colors font-semibold">
                                {{ __('Close') }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <style>
        .qr-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.2s ease-in-out;
        }

        .qr-modal-content {
            position: relative;
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            max-width: 28rem;
            width: 100%;
            margin: 1rem;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</div>
