<div>
    <flux:modal
        name="device-qr-modal"
        class="max-w-md w-full"
        wire:model="showModal"
        @close="closeModal"
    >
        @if ($device)
            <div class="p-6 space-y-6 text-center">
                <div class="space-y-2">
                    <flux:heading size="lg">{{ __('QR Connection') }}</flux:heading>
                    <flux:text>
                        {{ __('Scan this code with WhatsApp to connect ":name".', ['name' => $device->name]) }}
                    </flux:text>
                </div>

                @if ($qrSvg)
                    <div class="mx-auto w-72 rounded-2xl border border-slate-200 bg-white p-4">
                        {!! $qrSvg !!}
                    </div>
                    <p class="text-xs text-neutral-400">
                        {{ __('Generated at') }}: {{ now()->format('H:i:s') }}
                    </p>
                @else
                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-8" wire:poll.2s="pollQrStatus">
                        <div class="flex flex-col items-center justify-center space-y-3">
                            <svg class="h-8 w-8 animate-spin text-neutral-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm text-neutral-500">{{ __('Waiting for QR code from WhatsApp...') }}</span>
                        </div>
                    </div>
                @endif

                <div class="flex justify-center">
                    <flux:modal.close>
                        <flux:button variant="outline">
                            {{ __('Close') }}
                        </flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
