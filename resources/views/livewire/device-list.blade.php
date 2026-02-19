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
            <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 w-1.5 {{ $device->status === 'connected' ? 'bg-emerald-400' : 'bg-slate-300' }}"></div>

                <div class="ml-3 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-base font-semibold text-neutral-900">{{ $device->name }}</p>
                    <p class="text-sm text-neutral-500">{{ $device->phone_number }}</p>
                    <div class="mt-2 flex flex-wrap gap-3 text-xs text-neutral-500">
                        <span class="panel-pill {{ $device->status === 'connected' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ ucfirst($device->status) }}
                        </span>
                        <span>{{ __('Last seen: :date', ['date' => $device->last_seen_at?->diffForHumans() ?? __('never')]) }}</span>
                    </div>
                    @if ($device->auto_reply_greeting)
                        <p class="mt-2 text-xs text-neutral-500">
                            {{ __('Greeting: :text', ['text' => \Illuminate\Support\Str::limit($device->auto_reply_greeting, 80)]) }}
                        </p>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2">
                    <flux:button wire:click="showQr({{ $device->id }})" variant="outline" :disabled="$device->status === 'connected'">{{ __('Pair') }}</flux:button>
                    <flux:button
                        wire:click="disconnect({{ $device->id }})"
                        variant="outline"
                        :disabled="$device->status !== 'connected'"
                    >
                        {{ __('Disconnect') }}
                    </flux:button>
                    <flux:button wire:click="editGreeting({{ $device->id }})" variant="outline">{{ __('Greeting') }}</flux:button>
                    <flux:button wire:click="editMenu({{ $device->id }})" variant="outline">{{ __('Menu') }}</flux:button>
                    <flux:button wire:click="remove({{ $device->id }})" variant="danger">{{ __('Remove') }}</flux:button>
                </div>
            </div>
            </div>

            @if ($editingGreetingDeviceId === $device->id)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <label class="text-sm font-medium text-neutral-700">{{ __('Auto Reply Greeting') }}</label>
                    <textarea wire:model.defer="editingGreeting" rows="3" class="panel-input mt-2" placeholder="{{ __('Hi, welcome to Severo. How can we help?') }}"></textarea>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <flux:button size="sm" variant="primary" wire:click="saveGreeting">{{ __('Save Greeting') }}</flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="cancelGreeting">{{ __('Cancel') }}</flux:button>
                    </div>
                </div>
            @endif

            @if ($editingMenuDeviceId === $device->id)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <label class="text-sm font-medium text-neutral-700">{{ __('Auto Reply Menu') }}</label>
                        <div class="flex flex-wrap gap-2">
                            <flux:button size="xs" variant="outline" wire:click="loadDefaultMenu">{{ __('Load Default') }}</flux:button>
                            <flux:button size="xs" variant="outline" wire:click="addRootButton">{{ __('Add Item') }}</flux:button>
                            <flux:button size="xs" variant="outline" wire:click="clearMenu">{{ __('Clear All') }}</flux:button>
                        </div>
                    </div>

                    {{-- Session Timeout Setting --}}
                    <div class="mt-3 flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-3">
                        <label class="text-xs font-medium text-neutral-600 whitespace-nowrap">{{ __('Session Timeout') }}</label>
                        <div class="flex items-center gap-2">
                            <input
                                type="number"
                                wire:model.defer="editingSessionTimeout"
                                min="1"
                                max="1440"
                                class="panel-input w-24"
                                placeholder="30"
                            />
                            <span class="text-xs text-neutral-500">{{ __('menit (greeting dikirim ulang setelah timeout)') }}</span>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-6 xl:grid-cols-2">
                        {{-- LEFT: Menu Editor Form --}}
                        <div>
                            <div>
                                <label class="text-xs font-medium text-neutral-600">{{ __('Menu intro text') }}</label>
                                <textarea wire:model.defer="editingMenuForm.root_text" rows="2" class="panel-input mt-2" placeholder="{{ __('Pilih layanan yang kamu butuhkan:') }}"></textarea>
                                @error('editingMenuForm.root_text')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-4 space-y-4">
                                @foreach ($editingMenuForm['root_buttons'] as $index => $button)
                                    <div class="rounded-lg border border-slate-200 bg-white p-3">
                                        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                            <label class="text-xs font-medium text-neutral-600">{{ __('Item :number', ['number' => $index + 1]) }}</label>
                                            <flux:button size="xs" variant="danger" wire:click="removeRootButton({{ $index }})">{{ __('Delete Item') }}</flux:button>
                                        </div>
                                        <input type="text" wire:model.defer="editingMenuForm.root_buttons.{{ $index }}.label" class="panel-input mt-2" placeholder="{{ __('Item label') }}" />
                                        @error("editingMenuForm.root_buttons.{$index}.label")
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror

                                        <div class="mt-3 flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                wire:model.defer="editingMenuForm.root_buttons.{{ $index }}.has_submenu"
                                                class="rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                                id="submenu-toggle-{{ $device->id }}-{{ $index }}"
                                            />
                                            <label for="submenu-toggle-{{ $device->id }}-{{ $index }}" class="text-xs text-neutral-600">
                                                {{ __('This item opens a submenu') }}
                                            </label>
                                        </div>

                                        @if (! empty($button['has_submenu']))
                                            <div class="mt-3">
                                                <label class="text-xs font-medium text-neutral-600">{{ __('Submenu text') }}</label>
                                                <textarea wire:model.defer="editingMenuForm.root_buttons.{{ $index }}.submenu_text" rows="2" class="panel-input mt-2" placeholder="{{ __('Pilih tier joki yang kamu inginkan:') }}"></textarea>
                                                @error("editingMenuForm.root_buttons.{$index}.submenu_text")
                                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div class="mt-3 flex items-center justify-between">
                                                <label class="text-xs font-medium text-neutral-600">{{ __('Submenu items') }}</label>
                                                <flux:button size="xs" variant="outline" wire:click="addSubButton({{ $index }})">{{ __('Add Sub Item') }}</flux:button>
                                            </div>

                                            <div class="mt-3 space-y-3">
                                                @foreach ($button['sub_buttons'] as $subIndex => $subButton)
                                                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-xs font-medium text-neutral-600">{{ __('Sub Item :number', ['number' => $subIndex + 1]) }}</span>
                                                            <flux:button size="xs" variant="danger" wire:click="removeSubButton({{ $index }}, {{ $subIndex }})">{{ __('Delete Sub Item') }}</flux:button>
                                                        </div>
                                                        <input type="text" wire:model.defer="editingMenuForm.root_buttons.{{ $index }}.sub_buttons.{{ $subIndex }}.label" class="panel-input mt-2" placeholder="{{ __('Sub item label') }}" />
                                                        @error("editingMenuForm.root_buttons.{$index}.sub_buttons.{$subIndex}.label")
                                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                        @enderror
                                                        <textarea wire:model.defer="editingMenuForm.root_buttons.{{ $index }}.sub_buttons.{{ $subIndex }}.reply_text" rows="2" class="panel-input mt-2" placeholder="{{ __('Reply text') }}"></textarea>
                                                        @error("editingMenuForm.root_buttons.{$index}.sub_buttons.{$subIndex}.reply_text")
                                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                        @enderror
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="mt-3">
                                                <label class="text-xs font-medium text-neutral-600">{{ __('Reply text') }}</label>
                                                <textarea wire:model.defer="editingMenuForm.root_buttons.{{ $index }}.reply_text" rows="2" class="panel-input mt-2" placeholder="{{ __('Response text for this button') }}"></textarea>
                                                @error("editingMenuForm.root_buttons.{$index}.reply_text")
                                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            @error('editingMenuForm.root_buttons')
                                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- RIGHT: WhatsApp-style Preview --}}
                        <div class="hidden xl:block">
                            <label class="text-xs font-medium text-neutral-600 mb-2 block">{{ __('Preview') }}</label>
                            <div class="rounded-2xl border border-slate-200 bg-[#e5ddd5] p-4 min-h-[360px] max-h-[600px] overflow-y-auto" style="background-image: url('data:image/svg+xml,%3Csvg width=\'200\' height=\'200\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cdefs%3E%3Cpattern id=\'p\' width=\'60\' height=\'60\' patternUnits=\'userSpaceOnUse\'%3E%3Cpath d=\'M30 0L30 60M0 30L60 30\' stroke=\'%23d4cfc4\' stroke-width=\'.5\' fill=\'none\' opacity=\'.3\'/%3E%3C/pattern%3E%3C/defs%3E%3Crect width=\'200\' height=\'200\' fill=\'url(%23p)\'/%3E%3C/svg%3E');">
                                {{-- Root menu preview --}}
                                @if (filled($editingMenuForm['root_text'] ?? ''))
                                    <div class="mb-3 ml-auto max-w-[85%]">
                                        <div class="rounded-xl rounded-tr-sm bg-[#dcf8c6] px-3 py-2 text-sm text-neutral-800 shadow-sm">
                                            <p class="whitespace-pre-line">{{ $editingMenuForm['root_text'] }}</p>
                                        </div>
                                    </div>

                                    @php $rootBtns = $editingMenuForm['root_buttons'] ?? []; @endphp
                                    @if (count($rootBtns) > 0 && count($rootBtns) <= 3)
                                        <div class="mb-3 ml-auto max-w-[85%] space-y-1">
                                            @foreach ($rootBtns as $btn)
                                                @if (filled($btn['label'] ?? ''))
                                                    <div class="rounded-lg bg-white px-3 py-2 text-center text-sm font-medium text-[#00a884] shadow-sm cursor-default">
                                                        {{ $btn['label'] }}
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @elseif (count($rootBtns) > 3)
                                        <div class="mb-3 ml-auto max-w-[85%]">
                                            <div class="rounded-lg bg-white px-3 py-2 text-center text-sm font-medium text-[#00a884] shadow-sm cursor-default">
                                                ☰ {{ __('Lihat Opsi') }} ({{ count($rootBtns) }})
                                            </div>
                                        </div>
                                    @endif
                                @endif

                                {{-- Submenu / reply previews --}}
                                @foreach ($editingMenuForm['root_buttons'] ?? [] as $idx => $btn)
                                    @if (filled($btn['label'] ?? ''))
                                        {{-- User taps button --}}
                                        <div class="mb-2 mr-auto max-w-[85%]">
                                            <div class="rounded-xl rounded-tl-sm bg-white px-3 py-2 text-sm text-neutral-800 shadow-sm">
                                                {{ $btn['label'] }}
                                            </div>
                                        </div>

                                        @if (! empty($btn['has_submenu']) && filled($btn['submenu_text'] ?? ''))
                                            {{-- Bot replies with submenu --}}
                                            <div class="mb-2 ml-auto max-w-[85%]">
                                                <div class="rounded-xl rounded-tr-sm bg-[#dcf8c6] px-3 py-2 text-sm text-neutral-800 shadow-sm">
                                                    <p class="whitespace-pre-line">{{ $btn['submenu_text'] }}</p>
                                                </div>
                                            </div>
                                            @php $subBtns = $btn['sub_buttons'] ?? []; @endphp
                                            @if (count($subBtns) > 0 && count($subBtns) <= 3)
                                                <div class="mb-3 ml-auto max-w-[85%] space-y-1">
                                                    @foreach ($subBtns as $sBtn)
                                                        @if (filled($sBtn['label'] ?? ''))
                                                            <div class="rounded-lg bg-white px-3 py-2 text-center text-sm font-medium text-[#00a884] shadow-sm cursor-default">
                                                                {{ $sBtn['label'] }}
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @elseif (count($subBtns) > 3)
                                                <div class="mb-3 ml-auto max-w-[85%]">
                                                    <div class="rounded-lg bg-white px-3 py-2 text-center text-sm font-medium text-[#00a884] shadow-sm cursor-default">
                                                        ☰ {{ __('Lihat Opsi') }} ({{ count($subBtns) }})
                                                    </div>
                                                </div>
                                            @endif
                                        @elseif (filled($btn['reply_text'] ?? ''))
                                            {{-- Bot replies with leaf text + back button --}}
                                            <div class="mb-2 ml-auto max-w-[85%]">
                                                <div class="rounded-xl rounded-tr-sm bg-[#dcf8c6] px-3 py-2 text-sm text-neutral-800 shadow-sm">
                                                    <p class="whitespace-pre-line">{{ $btn['reply_text'] }}</p>
                                                </div>
                                            </div>
                                            <div class="mb-3 ml-auto max-w-[85%] space-y-1">
                                                <div class="rounded-lg bg-white px-3 py-2 text-center text-sm font-medium text-[#00a884] shadow-sm cursor-default">
                                                    ↩ Kembali ke Menu
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <flux:button size="sm" variant="primary" wire:click="saveMenu">{{ __('Save Menu') }}</flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="cancelMenu">{{ __('Cancel') }}</flux:button>
                    </div>
                </div>
            @endif
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-neutral-500">
                {{ __('No devices found yet.') }}
            </div>
        @endforelse
    </div>
</div>
