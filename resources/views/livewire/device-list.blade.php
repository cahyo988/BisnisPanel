<div class="panel-card" wire:poll.5s>
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h3 class="panel-section-title">{{ __('WhatsApp Devices') }}</h3>
            <p class="panel-section-subtitle">{{ __('Monitor status and manage sender numbers.') }}</p>
        </div>

        <div class="flex flex-1 flex-col gap-3 md:flex-row md:items-center md:justify-end">
            @if ($userOptions->isNotEmpty())
                <select wire:model.live="selectedUserId" class="panel-select md:w-48">
                    <option value="">{{ __('All users') }}</option>
                    @foreach ($userOptions as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>
            @endif

            <select wire:model.live="status" class="panel-select md:w-40">
                <option value="all">{{ __('All statuses') }}</option>
                <option value="connected">{{ __('Connected') }}</option>
                <option value="disconnected">{{ __('Disconnected') }}</option>
            </select>

            <input type="text" wire:model.live="search" placeholder="{{ __('Search name or number') }}" class="panel-input md:w-56" />
        </div>
    </div>

    @if (session()->has('device_removed'))
        <div class="mt-4 rounded-xl bg-amber-50 px-4 py-2 text-sm text-amber-800">
            {{ session('device_removed') }}
        </div>
    @endif

    <div class="mt-6 space-y-3">
        @forelse ($devices as $device)
            <div class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-base font-semibold text-neutral-900">{{ $device->name }}</p>
                    <p class="text-sm text-neutral-500">{{ $device->phone_number }}</p>
                    <div class="mt-2 flex flex-wrap gap-3 text-xs text-neutral-500">
                        <span class="panel-pill {{ $device->status === 'connected' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ ucfirst($device->status) }}
                        </span>
                        <span>{{ __('Last seen: :date', ['date' => $device->last_seen_at?->diffForHumans() ?? __('never')]) }}</span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <flux:button wire:click="showQr({{ $device->id }})" variant="outline">{{ __('Show QR') }}</flux:button>
                    <flux:button
                        wire:click="disconnect({{ $device->id }})"
                        variant="outline"
                        :disabled="$device->status !== 'connected'"
                    >
                        {{ __('Disconnect') }}
                    </flux:button>
                    <flux:button wire:click="remove({{ $device->id }})" variant="danger">{{ __('Remove') }}</flux:button>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-sm text-neutral-500">
                {{ __('No devices found yet.') }}
            </div>
        @endforelse
    </div>
</div>
