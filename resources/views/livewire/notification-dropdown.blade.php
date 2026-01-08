<div wire:poll.15s class="relative">
    <flux:dropdown position="top" align="end">
        <button type="button" class="relative sidebar-toggle !border-slate-200 !bg-white !text-slate-600">
            <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a4.001 4.001 0 0 1-5.714 0M18 8a6 6 0 1 0-12 0c0 2.577-.62 4.153-1.293 5.165a1 1 0 0 0 .832 1.559h13.922a1 1 0 0 0 .832-1.559C18.62 12.153 18 10.577 18 8Z" />
            </svg>
            @if ($unreadCount > 0)
                <span class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-rose-500 px-1.5 text-[10px] font-semibold text-white shadow">
                    {{ $unreadCount }}
                </span>
            @endif
        </button>

    <flux:menu class="w-96 rounded-2xl border border-slate-200 !bg-white shadow-xl">
        <div class="flex items-center justify-between px-3 py-2">
            <div>
                <p class="text-sm font-semibold text-neutral-900">{{ __('Notifications') }}</p>
                <p class="text-xs text-neutral-500">
                    {{ trans_choice(':count unread alert|:count unread alerts', $unreadCount) }}
                </p>
            </div>
            <button type="button" wire:click="markAll" class="text-xs font-semibold text-[var(--primary)] hover:text-[var(--primary-strong)]">
                {{ __('Mark all read') }}
            </button>
        </div>
        <flux:menu.separator class="bg-slate-200" />

        <div class="space-y-2 !bg-white px-2 py-2">
            @forelse ($notifications as $notification)
                @php
                    $unread = is_null($notification->read_at);
                @endphp

                <div class="rounded-2xl border border-slate-100 !bg-white px-3 py-3 shadow-sm hover:bg-slate-50 transition-colors">
                    <div class="flex items-start gap-3">
                        <div class="mt-1 flex size-8 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-600">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a4.001 4.001 0 0 1-5.714 0M18 8a6 6 0 1 0-12 0c0 2.577-.62 4.153-1.293 5.165a1 1 0 0 0 .832 1.559h13.922a1 1 0 0 0 .832-1.559C18.62 12.153 18 10.577 18 8Z" />
                            </svg>
                        </div>

                        <div class="flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-semibold text-neutral-900">{{ $notification->title }}</p>
                                <span class="text-[11px] uppercase tracking-wide text-neutral-400">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <p class="text-xs text-neutral-600 mt-0.5">{{ $notification->body }}</p>

                            @if ($unread)
                                <button
                                    type="button"
                                    class="mt-2 text-xs font-semibold text-[var(--primary)] hover:text-[var(--primary-strong)]"
                                    wire:click="markAsRead({{ $notification->id }})"
                                >
                                    {{ __('Mark as read') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-3 py-4 text-center text-sm text-neutral-500">
                    {{ __('No notifications yet.') }}
                </div>
            @endforelse

            @if ($notifications->count() >= $limit)
                <button
                    wire:click.prevent="loadMore"
                    type="button"
                    class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors"
                >
                    {{ __('Show more') }}
                </button>
            @endif
        </div>
    </flux:menu>
    </flux:dropdown>
</div>
