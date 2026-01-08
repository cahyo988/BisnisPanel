<div class="panel-card space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h3 class="panel-section-title">{{ __('Message Intelligence') }}</h3>
            <p class="panel-section-subtitle">{{ __('Consolidated feed of all messaging activity across devices.') }}</p>
        </div>

        <div class="grid gap-3 md:grid-cols-2 lg:flex lg:flex-1 lg:items-center lg:justify-end">
            @if ($userOptions->isNotEmpty())
                <select wire:model.live="selectedUserId" class="panel-select md:w-48">
                    <option value="">{{ __('All users') }}</option>
                    @foreach ($userOptions as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>
            @endif

            <select wire:model.live="direction" class="panel-select md:w-40">
                <option value="all">{{ __('All directions') }}</option>
                <option value="incoming">{{ __('Incoming') }}</option>
                <option value="outgoing">{{ __('Outgoing') }}</option>
            </select>

            <select wire:model.live="status" class="panel-select md:w-40">
                <option value="all">{{ __('All statuses') }}</option>
                <option value="queued">{{ __('Queued') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="sent">{{ __('Sent') }}</option>
                <option value="failed">{{ __('Failed') }}</option>
                <option value="delivered">{{ __('Delivered') }}</option>
                <option value="read">{{ __('Read') }}</option>
            </select>

            <input type="text" wire:model.live="search" placeholder="{{ __('Search phone or message...') }}" class="panel-input md:w-56" />
        </div>
    </div>

    <div class="data-table-wrapper">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('Timestamp') }}</th>
                        <th>{{ __('Device') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th>{{ __('Message') }}</th>
                        <th>{{ __('Direction') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="text-xs text-[var(--text-muted)]">{{ $log->created_at->format('d M Y - H:i') }}</td>
                            <td class="font-medium">{{ $log->device->name ?? __('N/A') }}</td>
                            <td class="font-mono text-xs">{{ $log->phone }}</td>
                            <td>
                                <p class="text-sm text-[var(--text-primary)]">{{ \Illuminate\Support\Str::limit($log->message, 90) }}</p>
                                @if ($log->error_message)
                                    <p class="text-xs text-[var(--accent-error)]">{{ $log->error_message }}</p>
                                @endif
                            </td>
                            <td class="capitalize text-[var(--text-muted)]">{{ $log->direction }}</td>
                            <td>
                                <span class="panel-pill
                                    @class([
                                        'bg-emerald-100 text-emerald-700' => in_array($log->status, ['sent', 'delivered', 'read']),
                                        'bg-rose-100 text-rose-700' => $log->status === 'failed',
                                        'bg-amber-100 text-amber-700' => in_array($log->status, ['queued', 'pending']),
                                        'bg-slate-100 text-slate-600' => ! in_array($log->status, ['pending', 'queued', 'sent', 'delivered', 'read']) && $log->status !== 'failed',
                                    ])">
                                    {{ ucfirst($log->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="table-actions justify-end">
                                    <button type="button" class="table-icon-btn" wire:click="$dispatch('show-log-details', { logId: {{ $log->id }} })" title="{{ __('View details') }}">
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12Z" />
                                            <circle cx="12" cy="12" r="2.25" />
                                        </svg>
                                    </button>
                                    <button type="button" class="table-icon-btn" wire:click="$dispatch('retry-log-delivery', { logId: {{ $log->id }} })" title="{{ __('Retry delivery') }}">
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 4.5v6h6M19.5 19.5v-6h-6M5.25 12A6.75 6.75 0 0 1 12 5.25h.75M18.75 12A6.75 6.75 0 0 1 12 18.75h-.75" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <svg class="mb-3 size-8 text-[var(--text-muted)]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8.25h18M3 12h18M3 15.75h18" />
                                    </svg>
                                    {{ __('No logs match the selected filters yet.') }}
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex flex-col gap-3 text-sm text-[var(--text-muted)] sm:flex-row sm:items-center sm:justify-between">
        <p>{{ __('Showing :count results', ['count' => $logs->total()]) }}</p>
        <div class="shrink-0">
            {{ $logs->links() }}
        </div>
    </div>
</div>
