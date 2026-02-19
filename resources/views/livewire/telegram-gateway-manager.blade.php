<div class="panel-card space-y-4">
    <div>
        <h3 class="panel-section-title">{{ __('Telegram Gateways') }}</h3>
        <p class="panel-section-subtitle">{{ __('Tambahkan akun Telegram agar chat masuk dan balasan bisa diproses dari inbox.') }}</p>
    </div>

    @if (session()->has('telegram_gateway_saved'))
        <div class="rounded-xl bg-green-50 px-4 py-2 text-sm text-green-700">
            {{ session('telegram_gateway_saved') }}
        </div>
    @endif

    <form class="space-y-3" wire:submit.prevent="save">
        @if ($users->isNotEmpty())
            <div>
                <label class="text-sm font-medium text-neutral-700">{{ __('Business Owner') }}</label>
                <select wire:model="selectedUserId" class="panel-select mt-1">
                    <option value="">{{ __('Choose user...') }}</option>
                    @foreach ($users as $userOption)
                        <option value="{{ $userOption->id }}">{{ $userOption->name }}</option>
                    @endforeach
                </select>
                @error('selectedUserId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        @endif

        <div>
            <label class="text-sm font-medium text-neutral-700">{{ __('Gateway Name') }}</label>
            <input type="text" wire:model.defer="name" class="panel-input mt-1" placeholder="Telegram Sales" />
            @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="text-sm font-medium text-neutral-700">{{ __('Account External ID') }}</label>
            <input type="text" wire:model.defer="externalId" class="panel-input mt-1" placeholder="tg-sales-1" />
            @error('externalId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-neutral-500">{{ __('ID ini harus sama dengan payload webhook Telegram (account_external_id).') }}</p>
        </div>

        <div>
            <label class="text-sm font-medium text-neutral-700">{{ __('Status') }}</label>
            <select wire:model.defer="status" class="panel-select mt-1">
                <option value="connected">{{ __('Connected') }}</option>
                <option value="disconnected">{{ __('Disconnected') }}</option>
            </select>
            @error('status') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">{{ __('Save Gateway') }}</flux:button>
        </div>
    </form>

    <div class="space-y-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Registered Accounts') }}</p>

        @forelse ($accounts as $account)
            <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $account->name }}</p>
                    <p class="text-xs text-slate-500">{{ $account->external_id }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="panel-pill {{ $account->status === 'connected' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                        {{ ucfirst($account->status) }}
                    </span>
                    <flux:button size="xs" variant="danger" wire:click="remove({{ $account->id }})">{{ __('Remove') }}</flux:button>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-3 text-xs text-slate-500">
                {{ __('No Telegram gateways yet.') }}
            </div>
        @endforelse
    </div>
</div>
