<div wire:poll.15s class="relative">
    <flux:dropdown position="top" align="end">
        <flux:button variant="ghost" icon="bell" class="relative">
            @if ($unreadCount > 0)
                <span class="absolute -right-1 -top-1 inline-flex items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">
                    {{ $unreadCount }}
                </span>
            @endif
        </flux:button>

        <flux:menu class="w-80">
            <div class="flex items-center justify-between px-3 py-2">
                <p class="text-sm font-semibold text-neutral-800 dark:text-neutral-200">{{ __('Notifications') }}</p>
                <button type="button" wire:click="markAll" class="text-xs text-sky-600 hover:underline">{{ __('Mark all as read') }}</button>
            </div>
            <flux:menu.separator />

            @forelse ($notifications as $notification)
                <div class="px-3 py-2">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-neutral-800 dark:text-neutral-200">{{ $notification->title }}</p>
                            <p class="text-xs text-neutral-500">{{ $notification->body }}</p>
                            <p class="mt-1 text-[11px] uppercase tracking-wide text-neutral-400">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        @if (is_null($notification->read_at))
                            <button type="button" class="text-xs text-sky-600 hover:underline" wire:click="markAsRead({{ $notification->id }})">
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
