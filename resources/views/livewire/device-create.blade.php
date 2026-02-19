<div class="panel-card">
    <h3 class="panel-section-title">{{ __('Add WhatsApp Device') }}</h3>
    <p class="panel-section-subtitle">{{ __('Daftarkan nomor pengirim, lalu pair melalui QR untuk mulai menerima dan membalas chat.') }}</p>

    @if (session()->has('device_created'))
        <div class="mt-3 rounded-xl bg-green-50 px-4 py-2 text-sm text-green-700">
            {{ session('device_created') }}
        </div>
    @endif

    <form class="mt-4 space-y-4" wire:submit.prevent="save">
        @if ($users->isNotEmpty())
            <div>
                <label class="text-sm font-medium text-neutral-700">{{ __('Business Owner') }}</label>
                <select wire:model="selectedUserId" class="panel-select mt-1">
                    <option value="">{{ __('Choose user…') }}</option>
                    @foreach ($users as $userOption)
                        <option value="{{ $userOption->id }}">{{ $userOption->name }}</option>
                    @endforeach
                </select>
                @error('selectedUserId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        @endif

        <div>
            <label class="text-sm font-medium text-neutral-700">{{ __('Device Name') }}</label>
            <input type="text" wire:model.defer="name" class="panel-input mt-1" placeholder="Sales Phone" />
            @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-neutral-500">{{ __('Contoh: Sales A, CS Utama, Admin Store.') }}</p>
        </div>

        <div>
            <label class="text-sm font-medium text-neutral-700">{{ __('Phone Number') }}</label>
            <input type="text" wire:model.defer="phone_number" class="panel-input mt-1" placeholder="+628123456789" />
            @error('phone_number') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-neutral-500">{{ __('Gunakan format internasional, contoh +62...') }}</p>
        </div>

        <div>
            <label class="text-sm font-medium text-neutral-700">{{ __('Baileys Session JSON (optional)') }}</label>
            <textarea wire:model.defer="session" rows="4" class="panel-input mt-1" placeholder='{{ __('Paste session JSON payload') }}'></textarea>
            @error('session') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="text-sm font-medium text-neutral-700">{{ __('Auto Reply Greeting (optional)') }}</label>
            <textarea wire:model.defer="autoReplyGreeting" rows="3" class="panel-input mt-1" placeholder="{{ __('Hi, welcome to Severo. How can we help?') }}"></textarea>
            @error('autoReplyGreeting') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-neutral-500">
                {{ __('This message is sent automatically before the menu when someone chats your device.') }}
            </p>
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">{{ __('Save Device') }}</flux:button>
        </div>
    </form>
</div>
