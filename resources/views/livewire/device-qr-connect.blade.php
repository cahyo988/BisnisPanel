<div class="panel-card text-center">
    <h3 class="panel-section-title">{{ __('QR Connection') }}</h3>
    <p class="panel-section-subtitle">{{ __('Select a device to reveal its latest QR code.') }}</p>

    @if ($device)
        <div class="mt-5 space-y-4">
            <p class="text-sm text-neutral-500">
                {{ __('Scan this code with WhatsApp to connect ":name".', ['name' => $device->name]) }}
            </p>

            @if ($qrSvg)
                <div class="mx-auto w-64 rounded-2xl border border-slate-200 bg-white p-4">
                    {!! $qrSvg !!}
                </div>
            @else
                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-neutral-500">
                    {{ __('QR code is not available yet. Wait for the session payload.') }}
                </div>
            @endif

            <flux:button variant="outline" wire:click="clear">{{ __('Close preview') }}</flux:button>
        </div>
    @else
        <div class="mt-6 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-sm text-neutral-500">
            {{ __('Choose a device from the list to display its QR code.') }}
        </div>
    @endif
</div>
