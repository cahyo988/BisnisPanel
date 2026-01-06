<div wire:poll.15s class="relative">
    <flux:dropdown position="top" align="end">
        <flux:button variant="outline" icon="bell" class="relative rounded-xl border-slate-200 bg-white shadow-sm">
            @if ($unreadCount > 0)
                <span class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-rose-500 px-1.5 text-[10px] font-semibold text-white shadow">
                    {{ $unreadCount }}
                </span>
            @endif
        </flux:button>

        <flux:menu class="w-80 rounded-2xl border border-slate-200 bg-white shadow-xl">
            <div class="flex items-center justify-between px-3 py-2">
                <p class="text-sm font-semibold text-neutral-900">{{ __('Notifications') }}</p>
                <button type="button" wire:click="markAll" class="text-xs font-medium text-slate-600 hover:text-slate-900">
                    {{ __('Mark all as read') }}
                </button>
            </div>
            <flux:menu.separator />

            @forelse ($notifications as $notification)
                <div class="px-3 py-2">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-neutral-900">{{ $notification->title }}</p>
                            <p class="text-xs text-neutral-500">{{ $notification->body }}</p>
                            <p class="mt-1 text-[11px] uppercase tracking-wide text-neutral-400">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        @if (is_null($notification->read_at))
                            <button type="button" class="text-xs font-medium text-slate-600 hover:text-slate-900" wire:click="markAsRead({{ $notification->id }})">
                                {{ __('Mark read') }}
                            </button>
                        @endif
                    </div>
                </div>
                <flux:menu.separator />
            @empty
                <div class="px-3 py-4 text-sm text-neutral-500">{{ __('No notifications yet.') }}</div>
            @endforelse
        </flux:menu>
    </flux:dropdown>
</div>
