<div class="panel-card">
    <h3 class="panel-section-title">{{ __('Broadcast / Bulk Messaging') }}</h3>
    <p class="panel-section-subtitle">{{ __('Upload recipient lists and send sequential updates.') }}</p>

    @if (session()->has('broadcast_started'))
        <div class="mt-3 rounded-xl bg-sky-50 px-4 py-2 text-sm text-sky-700">
            {{ session('broadcast_started') }}
        </div>
    @endif

    <form class="mt-5 space-y-4" wire:submit.prevent="start">
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
                <label class="text-sm font-medium text-neutral-700">{{ __('Delay Between Messages (ms)') }}</label>
                <input type="number" min="0" wire:model.defer="delayMs" class="panel-input mt-1" />
                @error('delayMs') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="text-sm font-medium text-neutral-700">{{ __('Message Template') }}</label>
            <textarea wire:model.defer="message" rows="4" class="panel-input mt-1" placeholder="{{ __('Hi :name, thanks for contacting us…', ['name' => '{name}']) }}"></textarea>
            @error('message') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-sm font-medium text-neutral-700">{{ __('Upload Recipients (CSV/XLSX)') }}</label>
                <input type="file" wire:model="upload" class="mt-1 block w-full text-sm text-neutral-600" />
                @error('upload') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                @if ($upload)
                    <p class="mt-1 text-xs text-neutral-500">{{ __('Selected file: :name', ['name' => $upload->getClientOriginalName()]) }}</p>
                @endif
            </div>
            <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-xs text-neutral-500">
                {{ __('Tip: Use the first column for phone numbers. Up to 500 recipients per batch are supported.') }}
            </div>
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">{{ __('Start Broadcast') }}</flux:button>
        </div>
    </form>

    @if ($progress)
        <div class="mt-8 space-y-2" wire:poll.5s>
            <div class="flex items-center justify-between text-sm text-neutral-600">
                <span>{{ __('Progress') }}</span>
                <span>{{ $progress['sent'] }} / {{ $progress['total'] }} {{ __('sent') }}</span>
            </div>
            <div class="h-3 w-full overflow-hidden rounded-full bg-slate-200">
                <div class="h-full bg-emerald-500 transition-all" style="width: {{ min(100, ($progress['sent'] / $progress['total']) * 100) }}%"></div>
            </div>
            <p class="text-xs text-neutral-500">{{ __('Failed: :count', ['count' => $progress['failed']]) }}</p>
        </div>
    @endif

    @if ($recentLogs->isNotEmpty())
        <div class="mt-6">
            <h4 class="text-sm font-semibold text-neutral-700">{{ __('Recent Broadcast Activity') }}</h4>
            <div class="mt-3 divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white">
                @foreach ($recentLogs as $log)
                    <div class="flex flex-col gap-1 px-4 py-3 text-sm md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="font-medium text-neutral-900">{{ $log->phone }}</p>
                            <p class="text-xs text-neutral-500">{{ $log->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="panel-pill
                            @class([
                                'bg-emerald-100 text-emerald-700' => $log->status === 'sent',
                                'bg-rose-100 text-rose-700' => $log->status === 'failed',
                                'bg-amber-100 text-amber-700' => ! in_array($log->status, ['sent', 'failed']),
                            ])">
                            {{ ucfirst($log->status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
