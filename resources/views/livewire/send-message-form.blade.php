<div class="panel-card">
    <h3 class="panel-section-title">{{ __('Send Single Message') }}</h3>
    <p class="panel-section-subtitle">{{ __('Choose a device and dispatch a message instantly.') }}</p>

    @if (session()->has('message_sent'))
        <div class="mt-3 rounded-xl bg-green-50 px-4 py-2 text-sm text-green-700">
            {{ session('message_sent') }}
        </div>
    @endif

    <form class="mt-5 space-y-4" wire:submit.prevent="send">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-sm font-medium text-neutral-700">{{ __('Sender Device') }}</label>
                <select wire:model="deviceId" class="panel-select mt-1">
                    <option value="">{{ __('Select device…') }}</option>
                    @foreach ($devices as $device)
                        <option value="{{ $device->id }}">{{ $device->name }}</option>
                    @endforeach
                </select>
                @error('deviceId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-neutral-700">{{ __('Message Type') }}</label>
                <select wire:model="type" class="panel-select mt-1">
                    <option value="text">{{ __('Text') }}</option>
                    <option value="image">{{ __('Image') }}</option>
                    <option value="document">{{ __('Document') }}</option>
                    <option value="button">{{ __('Button Template') }}</option>
                </select>
                @error('type') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="text-sm font-medium text-neutral-700">{{ __('Destination Phone Number') }}</label>
            <input type="text" wire:model.defer="phone" class="panel-input mt-1" placeholder="+6281…" />
            @error('phone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        @if ($type === 'text')
            <div>
                <label class="text-sm font-medium text-neutral-700">{{ __('Message Body') }}</label>
                <textarea wire:model.defer="message" rows="4" class="panel-input mt-1" placeholder="{{ __('Type your WhatsApp message here…') }}"></textarea>
                @error('message') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-neutral-700">{{ __('Media URL') }}</label>
                    <input type="url" wire:model.defer="mediaUrl" class="panel-input mt-1" placeholder="https://…" />
                    @error('mediaUrl') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-neutral-700">{{ __('Upload File') }}</label>
                    <input type="file" wire:model="mediaFile" class="block w-full text-sm text-neutral-600" />
                    @error('mediaFile') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    @if ($mediaFile)
                        <p class="mt-1 text-xs text-neutral-500">{{ __('Selected:') }} {{ $mediaFile->getClientOriginalName() }}</p>
                    @endif
                </div>
            </div>
        @endif

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">{{ __('Queue Message') }}</flux:button>
        </div>
    </form>
</div>
