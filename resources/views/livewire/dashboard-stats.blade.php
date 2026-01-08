<div class="panel-card space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="panel-section-title">{{ __('Operational performance') }}</p>
            <p class="panel-section-subtitle">
                {{ __('Live KPIs for WhatsApp automation health and throughput.') }}
            </p>
        </div>

        <div class="flex flex-col gap-3 md:flex-row md:items-center">
            @if ($targetUser)
                <div class="inline-flex items-center gap-2 rounded-full border border-[var(--surface-border)] bg-[var(--surface-muted)] px-3 py-1 text-xs text-[var(--text-muted)]">
                    <span class="size-2 rounded-full bg-[var(--primary)]"></span>
                    <span>{{ __('Scoped to :name', ['name' => $targetUser->name]) }}</span>
                </div>
            @endif

            @if ($userOptions->isNotEmpty())
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">{{ __('Viewing') }}</span>
                    <select class="panel-select md:w-64" wire:model.live="filterUserId">
                        <option value="">{{ __('All businesses') }}</option>
                        @foreach ($userOptions as $option)
                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </div>

    <div class="kpi-grid">
        @foreach ($stats as $stat)
            @php
                $trend = $stat['trend'] ?? ['direction' => 'neutral', 'value' => 0, 'caption' => __('vs previous period')];
                $trendClass = [
                    'up' => 'trend-up',
                    'down' => 'trend-down',
                    'neutral' => 'trend-neutral',
                ][$trend['direction']] ?? 'trend-neutral';

                $iconPath = match ($trend['direction']) {
                    'up' => 'M4 12l6-6 6 6',
                    'down' => 'M20 12l-6 6-6-6',
                    default => 'M5 12h14',
                };
            @endphp
            <div class="kpi-card">
                <p class="kpi-label">{{ strtoupper($stat['label']) }}</p>
                <p class="kpi-metric">{{ number_format($stat['value']) }}</p>
                <p class="kpi-description">{{ $stat['description'] }}</p>
                <div class="kpi-trend {{ $trendClass }}">
                    <span class="inline-flex size-6 items-center justify-center rounded-full border border-current/30 bg-white">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="{{ $iconPath }}" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <span>
                        {{ $trend['direction'] === 'down' ? '-' : '+' }}
                        {{ number_format($trend['value'], 1) }}%
                    </span>
                    <span class="text-xs font-normal text-[var(--text-muted)]">
                        {{ $trend['caption'] }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>
</div>
