<div class="panel-card" wire:poll.2s="refreshInbox">
    <div class="flex flex-col gap-2">
        <h3 class="panel-section-title">{{ __('Unified Inbox') }}</h3>
        <p class="panel-section-subtitle">{{ __('Single inbox view across channels. Threads remain separated by account/device.') }}</p>
        <p class="text-[11px] text-slate-400">{{ __('Auto-refresh active every 2 seconds.') }}</p>
    </div>

    <div class="mt-5 grid gap-3 md:grid-cols-2 lg:grid-cols-5">
        @if ($userOptions->isNotEmpty())
            <select wire:model.live="selectedUserId" class="panel-select md:w-44">
                <option value="">{{ __('All users') }}</option>
                @foreach ($userOptions as $option)
                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                @endforeach
            </select>
        @endif

        <select wire:model.live="channel" class="panel-select md:w-40">
            <option value="all">{{ __('All channels') }}</option>
            <option value="whatsapp">{{ __('WhatsApp') }}</option>
            <option value="telegram">{{ __('Telegram') }}</option>
        </select>

        <select wire:model.live="selectedAccountId" class="panel-select md:w-56">
            <option value="">{{ __('All accounts') }}</option>
            @foreach ($channelAccounts as $account)
                <option value="{{ $account->id }}">{{ ucfirst($account->channel) }} - {{ $account->name }}</option>
            @endforeach
        </select>

        <input type="text" wire:model.live="search" placeholder="{{ __('Search contact or message...') }}" class="panel-input lg:col-span-2" />
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-12">
        <div class="lg:col-span-4 rounded-2xl border border-slate-200 bg-white">
            <div class="max-h-[620px] overflow-y-auto divide-y divide-slate-100">
                @forelse ($conversations as $conversation)
                    <button
                        type="button"
                        wire:click="selectConversation({{ $conversation->id }})"
                        @class([
                            'w-full px-4 py-3 text-left transition',
                            'bg-sky-50' => $selectedConversationId === $conversation->id,
                            'hover:bg-slate-50' => $selectedConversationId !== $conversation->id,
                        ])
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $conversation->contact_name ?: $conversation->contact_key }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $conversation->contact_key }}</p>
                                <p class="mt-1 truncate text-xs text-slate-600">{{ $conversation->last_message_preview ?: __('No messages yet') }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="panel-pill bg-slate-100 text-slate-700">{{ ucfirst($conversation->channel) }}</span>
                                @if ($conversation->unread_count > 0)
                                    <p class="mt-1 text-xs font-semibold text-rose-600">{{ __(':count unread', ['count' => $conversation->unread_count]) }}</p>
                                @endif
                            </div>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">
                            {{ $conversation->channelAccount->name ?? __('Unknown account') }}
                            @if ($conversation->last_message_at)
                                - {{ $conversation->last_message_at->diffForHumans() }}
                            @endif
                        </p>
                    </button>
                @empty
                    <div class="px-4 py-10 text-center text-sm text-slate-500">
                        {{ __('No conversations match the selected filters.') }}
                    </div>
                @endforelse
            </div>
        </div>

        <div class="lg:col-span-8 rounded-2xl border border-slate-200 bg-white">
            @if ($selectedConversation)
                <div class="border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ $selectedConversation->contact_name ?: $selectedConversation->contact_key }}</p>
                    <p class="text-xs text-slate-500">
                        {{ ucfirst($selectedConversation->channel) }} - {{ $selectedConversation->channelAccount->name ?? __('Unknown account') }}
                    </p>
                </div>

                <div class="max-h-[480px] space-y-3 overflow-y-auto px-4 py-4">
                    @forelse ($messages as $message)
                        @php
                            $replyTo = is_array($message->raw_payload ?? null) ? ($message->raw_payload['reply_to'] ?? null) : null;
                            $replyText = is_array($replyTo) ? ($replyTo['text'] ?? null) : null;
                            $replyType = is_array($replyTo) ? ($replyTo['type'] ?? null) : null;
                        @endphp

                        <div @class([
                            'max-w-[80%] rounded-2xl px-3 py-2 text-sm',
                            'ml-auto bg-sky-500 text-white' => $message->direction === 'outgoing',
                            'bg-slate-100 text-slate-800' => $message->direction !== 'outgoing',
                        ])>
                            @if (filled($replyText) || is_array($replyTo))
                                <div @class([
                                    'mb-2 rounded-lg border px-2 py-1 text-[11px]',
                                    'border-sky-300/60 bg-sky-400/30 text-sky-100' => $message->direction === 'outgoing',
                                    'border-slate-200 bg-white/70 text-slate-600' => $message->direction !== 'outgoing',
                                ])>
                                    {{ __('Reply to') }}{{ filled($replyType) ? ' ('.ucfirst((string) $replyType).')' : '' }}:
                                    {{ filled($replyText) ? \Illuminate\Support\Str::limit((string) $replyText, 80) : __('Message') }}
                                </div>
                            @endif

                            <p>{{ $message->message }}</p>
                            <p @class([
                                'mt-1 text-[11px]',
                                'text-sky-100' => $message->direction === 'outgoing',
                                'text-slate-500' => $message->direction !== 'outgoing',
                            ])>
                                {{ $message->created_at->format('d M Y H:i') }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No messages in this conversation.') }}</p>
                    @endforelse
                </div>

                <form wire:submit.prevent="sendReply" class="border-t border-slate-100 px-4 py-3">
                    <div class="flex gap-2">
                        <input type="text" wire:model.defer="replyMessage" class="panel-input" placeholder="{{ __('Type a reply...') }}" />
                        <flux:button type="submit" variant="primary">{{ __('Send') }}</flux:button>
                    </div>
                    @error('replyMessage') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </form>
            @else
                <div class="flex h-[620px] items-center justify-center px-4 text-center text-sm text-slate-500">
                    {{ __('Select a conversation to view messages and reply.') }}
                </div>
            @endif
        </div>
    </div>
</div>
