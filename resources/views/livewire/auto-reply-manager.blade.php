<div class="panel-card" wire:key="auto-reply-manager">
    <h3 class="panel-section-title">{{ __('Auto Reply Rules') }}</h3>
    <p class="panel-section-subtitle">{{ __('Define keyword-based responses per device.') }}</p>

    <form class="mt-5 space-y-5" wire:submit.prevent="save">
        @if (! empty($templates))
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 space-y-2">
                <div class="flex flex-wrap items-center gap-3">
                    <div>
                        <p class="text-sm font-semibold text-neutral-800">{{ __('Template') }}</p>
                        <p class="text-xs text-neutral-500">{{ __('Pilih template siap pakai atau kosongkan untuk mengetik manual.') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <select wire:model.live="replyTemplate" class="panel-select text-sm">
                            <option value="">{{ __('Pilih template...') }}</option>
                            @foreach ($templates as $key => $template)
                                <option value="{{ $key }}">{{ $template['label'] }}</option>
                            @endforeach
                        </select>
                        @if ($replyTemplate)
                            <button type="button" wire:click="clearTemplate" class="text-xs font-semibold text-neutral-500 hover:text-neutral-800">
                                {{ __('Bersihkan') }}
                            </button>
                        @endif
                    </div>
                </div>
                @error('replyTemplate') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
            <label class="text-sm font-semibold text-neutral-800">{{ __('Target Device') }}</label>
            <p class="text-xs text-neutral-500">{{ __('Tentukan perangkat yang akan mengeksekusi auto reply ini.') }}</p>
            <select wire:model="deviceId" class="panel-select mt-3">
                <option value="">{{ __('Select device...') }}</option>
                @foreach ($devices as $device)
                    <option value="{{ $device->id }}">{{ $device->name }}</option>
                @endforeach
            </select>
            @error('deviceId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-neutral-800">{{ __('Trigger & Response') }}</p>
                    <p class="text-xs text-neutral-500">{{ __('Sesuaikan kata kunci, mode pencocokan, dan pesan balasan.') }}</p>
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-neutral-600">
                    <input type="checkbox" wire:model="isActive" class="h-4 w-4 rounded border-slate-300 text-slate-700 focus:ring-slate-400" />
                    {{ __('Rule aktif') }}
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-neutral-700">{{ __('Keyword') }}</label>
                    <input type="text" id="rule-keyword" wire:model.live="keyword" class="panel-input mt-1" placeholder="{{ __('e.g. INFO') }}" />
                    @error('keyword') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-neutral-700">{{ __('Match Mode') }}</label>
                    <select id="rule-match-mode" wire:model.live="matchMode" class="panel-select mt-1">
                        <option value="exact">{{ __('Exact') }}</option>
                        <option value="contains">{{ __('Contains') }}</option>
                    </select>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-neutral-700">{{ __('Reply Type') }}</label>
                    <select id="rule-reply-type" wire:model.live="replyType" class="panel-select mt-1">
                        <option value="text">{{ __('Plain Text') }}</option>
                        <option value="template">{{ __('Template Text') }}</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="text-sm font-medium text-neutral-700">{{ __('Reply Message') }}</label>
                <textarea id="rule-reply-text" wire:model.live="replyText" rows="4" class="panel-input mt-1" placeholder="{{ __('Hello, thanks for reaching out...') }}"></textarea>
                @error('replyText') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            @if ($ruleId)
                <flux:button type="button" variant="ghost" wire:click="resetForm">{{ __('Cancel') }}</flux:button>
            @endif
            <flux:button
                type="submit"
                variant="primary"
                class="!bg-[var(--primary)] !text-white hover:!bg-[var(--primary-strong)]"
            >
                {{ $ruleId ? __('Update Rule') : __('Create Rule') }}
            </flux:button>
        </div>
    </form>

    <div class="mt-8">
        <h4 class="text-sm font-semibold text-neutral-700">{{ __('Existing Rules') }}</h4>

        @if ($rules->isEmpty())
            <div class="mt-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-neutral-500">
                {{ __('No rules have been created yet.') }}
            </div>
        @else
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                @foreach ($rules as $rule)
                    <div class="rounded-2xl border border-slate-200 bg-white/90 p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-neutral-900">{{ $rule->keyword }}</p>
                                <p class="text-xs uppercase tracking-wide text-neutral-400">{{ $rule->match_mode }}</p>
                            </div>
                            <span class="panel-pill {{ $rule->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $rule->is_active ? __('Active') : __('Paused') }}
                            </span>
                        </div>
                        <p class="mt-3 text-xs text-neutral-500">{{ __('Device: :name', ['name' => $rule->device->name ?? __('Unknown')]) }}</p>
                        <p class="mt-2 text-sm text-neutral-700">{{ \Illuminate\Support\Str::limit($rule->reply_text, 120) }}</p>
                        <div class="mt-4 flex flex-wrap gap-2 text-xs text-neutral-400">
                            <span>{{ __('Updated :date', ['date' => $rule->updated_at->diffForHumans()]) }}</span>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
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

@push('scripts')
    <script data-auto-reply-manager>
        (function registerAutoReplyTemplateListener() {
            const listenerName = '__autoReplyTemplateListener';
            if (window[listenerName]) {
                window.removeEventListener('auto-template-filled', window[listenerName]);
            }

            const handler = (event) => {
                const detail = event.detail || {};
                const keywordInput = document.getElementById('rule-keyword');
                if (keywordInput && 'keyword' in detail) {
                    keywordInput.value = detail.keyword ?? '';
                    keywordInput.dispatchEvent(new Event('input', { bubbles: true }));
                }

                const matchModeSelect = document.getElementById('rule-match-mode');
                if (matchModeSelect && 'matchMode' in detail) {
                    matchModeSelect.value = detail.matchMode ?? 'exact';
                    matchModeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }

                const replyTypeSelect = document.getElementById('rule-reply-type');
                if (replyTypeSelect && 'replyType' in detail) {
                    replyTypeSelect.value = detail.replyType ?? 'text';
                    replyTypeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }

                const replyTextArea = document.getElementById('rule-reply-text');
                if (replyTextArea && 'replyText' in detail) {
                    replyTextArea.value = detail.replyText ?? '';
                    replyTextArea.dispatchEvent(new Event('input', { bubbles: true }));
                }
            };

            window[listenerName] = handler;
            window.addEventListener('auto-template-filled', handler);
        })();
    </script>

    <script data-auto-reply-swal>
        (function registerAutoReplySwalListener() {
            const listenerName = '__autoReplySwalListener';
            if (window[listenerName]) {
                window.removeEventListener('swal', window[listenerName]);
            }

            const handler = (event) => {
                const detail = event.detail || {};
                const type = detail.type || 'success';
                const message = detail.message || '';

                if (!window.Swal || !message) {
                    return;
                }

                window.Swal.fire({
                    icon: type === 'error' ? 'error' : 'success',
                    title: message,
                    timer: 2200,
                    showConfirmButton: false,
                    timerProgressBar: true,
                });
            };

            window[listenerName] = handler;
            window.addEventListener('swal', handler);
        })();
    </script>
@endpush
