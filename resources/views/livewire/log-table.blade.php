<div class="panel-card">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h3 class="panel-section-title">{{ __('Message Logs') }}</h3>
            <p class="panel-section-subtitle">{{ __('Every incoming/outgoing event is recorded here.') }}</p>
        </div>
        <div class="flex flex-1 flex-col gap-3 md:flex-row md:items-center md:justify-end">
            @if ($userOptions->isNotEmpty())
                <select wire:model.live="selectedUserId" class="panel-select md:w-44">
                    <option value="">{{ __('All users') }}</option>
                    @foreach ($userOptions as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>
            @endif

            <select wire:model.live="direction" class="panel-select md:w-36">
                <option value="all">{{ __('All directions') }}</option>
                <option value="incoming">{{ __('Incoming') }}</option>
                <option value="outgoing">{{ __('Outgoing') }}</option>
            </select>

            <select wire:model.live="status" class="panel-select md:w-36">
                <option value="all">{{ __('All statuses') }}</option>
                <option value="queued">{{ __('Queued') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="sent">{{ __('Sent') }}</option>
                <option value="failed">{{ __('Failed') }}</option>
                <option value="delivered">{{ __('Delivered') }}</option>
                <option value="read">{{ __('Read') }}</option>
            </select>

            <input type="text" wire:model.live="search" placeholder="{{ __('Search phone or message…') }}" class="panel-input md:w-48" />
        </div>
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-left text-sm text-neutral-700">
            <thead>
                <tr class="border-b border-slate-200 text-xs uppercase tracking-wider text-neutral-500">
                    <th class="px-3 py-2">{{ __('Date') }}</th>
                    <th class="px-3 py-2">{{ __('Device') }}</th>
                    <th class="px-3 py-2">{{ __('Phone') }}</th>
                    <th class="px-3 py-2">{{ __('Message') }}</th>
                    <th class="px-3 py-2">{{ __('Direction') }}</th>
                    <th class="px-3 py-2">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr class="border-b border-slate-100 last:border-0">
                        <td class="px-3 py-2 text-xs text-neutral-500">{{ $log->created_at->format('d M Y H:i') }}</td>
                        <td class="px-3 py-2">{{ $log->device->name ?? __('N/A') }}</td>
                        <td class="px-3 py-2 font-mono text-xs">{{ $log->phone }}</td>
                        <td class="px-3 py-2">
                            <p class="break-words text-sm text-neutral-700">{{ \Illuminate\Support\Str::limit($log->message, 80) }}</p>
                            @if ($log->error_message)
                                <p class="text-xs text-rose-500">{{ $log->error_message }}</p>
                            @endif
                        </td>
                        <td class="px-3 py-2 capitalize text-neutral-500">{{ $log->direction }}</td>
                        <td class="px-3 py-2">
                            <span class="panel-pill
                                @class([
                                    'bg-emerald-100 text-emerald-700' => in_array($log->status, ['sent', 'delivered', 'read']),
                                    'bg-rose-100 text-rose-700' => $log->status === 'failed',
                                    'bg-amber-100 text-amber-700' => in_array($log->status, ['queued', 'pending']),
                                ])">
                                {{ ucfirst($log->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center text-sm text-neutral-500">{{ __('No logs found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>
