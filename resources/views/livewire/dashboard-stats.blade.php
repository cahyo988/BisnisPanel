<div class="panel-card space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="panel-section-title">{{ __('Key metrics') }}</p>
            <p class="panel-section-subtitle">{{ __('Realtime overview of WhatsApp activity') }}</p>
        </div>

        @if ($userOptions->isNotEmpty())
            <div class="flex items-center gap-2">
                <span class="text-sm text-neutral-500">{{ __('Viewing') }}:</span>
                <select class="panel-select md:w-64" wire:model.live="filterUserId">
                    <option value="">{{ __('All businesses') }}</option>
                    @foreach ($userOptions as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($stats as $stat)
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="text-sm font-medium text-neutral-500">{{ $stat['label'] }}</p>
                <p class="mt-3 text-3xl font-semibold text-neutral-900">
                    {{ number_format($stat['value']) }}
                </p>
                <p class="mt-1 text-xs text-neutral-500">{{ $stat['description'] }}</p>
            </div>
        @endforeach
    </div>
</div>
