<div class="panel-card">
    <h3 class="panel-section-title">{{ __('Auto Reply Rules') }}</h3>
    <p class="panel-section-subtitle">{{ __('Define keyword-based responses per device.') }}</p>

    @if (session()->has('auto_reply_saved'))
        <div class="mt-3 rounded-xl bg-green-50 px-4 py-2 text-sm text-green-700">
            {{ session('auto_reply_saved') }}
        </div>
    @endif

    <form class="mt-5 space-y-4" wire:submit.prevent="save">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-sm font-medium text-neutral-700">{{ __('Device') }}</label>
                <select wire:model="deviceId" class="panel-select mt-1">
                    <option value="">{{ __('Select device...') }}</option>
                    @foreach ($devices as $device)
                        <option value="{{ $device->id }}">{{ $device->name }}</option>
                    @endforeach
                </select>
                @error('deviceId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-neutral-700">{{ __('Keyword') }}</label>
                <input type="text" wire:model.defer="keyword" class="panel-input mt-1" placeholder="{{ __('e.g. INFO') }}" />
                @error('keyword') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label class="text-sm font-medium text-neutral-700">{{ __('Match Mode') }}</label>
                <select wire:model="matchMode" class="panel-select mt-1">
                    <option value="exact">{{ __('Exact') }}</option>
                    <option value="contains">{{ __('Contains') }}</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-neutral-700">{{ __('Reply Type') }}</label>
                <select wire:model="replyType" class="panel-select mt-1">
                    <option value="text">{{ __('Plain Text') }}</option>
                    <option value="template">{{ __('Template Text') }}</option>
                </select>
            </div>
            <div class="flex items-center gap-2 pt-6">
                <input type="checkbox" wire:model="isActive" class="h-4 w-4 rounded border-slate-300 text-slate-700 focus:ring-slate-400" id="rule-active" />
                <label for="rule-active" class="text-sm text-neutral-700">{{ __('Active') }}</label>
            </div>
        </div>

        <div>
            <label class="text-sm font-medium text-neutral-700">{{ __('Reply Message') }}</label>
            <textarea wire:model.defer="replyText" rows="4" class="panel-input mt-1" placeholder="{{ __('Hello, thanks for reaching out...') }}"></textarea>
            @error('replyText') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end gap-3">
            @if ($ruleId)
                <flux:button type="button" variant="ghost" wire:click="resetForm">{{ __('Cancel') }}</flux:button>
            @endif
            <flux:button type="submit" variant="primary">{{ $ruleId ? __('Update Rule') : __('Create Rule') }}</flux:button>
        </div>
    </form>

    <div class="mt-8">
        <h4 class="text-sm font-semibold text-neutral-700">{{ __('Existing Rules') }}</h4>

        @if ($rules->isEmpty())
            <div class="mt-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-neutral-500">
                {{ __('No rules have been created yet.') }}
            </div>
        @else
            <div class="mt-3 divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white">
                @foreach ($rules as $rule)
                    <div class="flex flex-col gap-2 px-4 py-3 text-sm md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="font-semibold text-neutral-900">{{ $rule->keyword }} <span class="text-xs font-normal uppercase text-neutral-500">({{ $rule->match_mode }})</span></p>
                            <p class="text-xs text-neutral-500">{{ __('Device: :name', ['name' => $rule->device->name ?? __('Unknown')]) }}</p>
                            <p class="text-xs text-neutral-500">{{ \Illuminate\Support\Str::limit($rule->reply_text, 80) }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="panel-pill {{ $rule->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $rule->is_active ? __('Active') : __('Paused') }}
                            </span>
                            <flux:button size="sm" variant="outline" wire:click="toggle({{ $rule->id }})">
                                {{ $rule->is_active ? __('Pause') : __('Activate') }}
                            </flux:button>
                            <flux:button size="sm" wire:click="edit({{ $rule->id }})">{{ __('Edit') }}</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $rule->id }})">{{ __('Delete') }}</flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
