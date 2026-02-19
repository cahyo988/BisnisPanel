<?php

namespace App\Livewire;

use App\Jobs\SendMessageJob;
use App\Models\ChannelAccount;
use App\Models\Conversation;
use App\Models\MessageLog;
use App\Models\User;
use App\Support\ContactKeyNormalizer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class UnifiedInbox extends Component
{
    use AuthorizesRequests;

    public string $channel = 'all';

    public ?int $selectedAccountId = null;

    public ?int $selectedConversationId = null;

    public ?int $selectedUserId = null;

    public string $search = '';

    public string $replyMessage = '';

    public function mount(): void
    {
        if (! auth()->user()->isAdmin()) {
            $this->selectedUserId = auth()->id();
        }
    }

    public function render(): View
    {
        $this->syncSelectedConversationUnread();

        $conversations = $this->conversations();
        $selectedConversation = $this->selectedConversation();

        return view('livewire.unified-inbox', [
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'messages' => $selectedConversation
                ? $selectedConversation->messages()->latest()->limit(80)->get()->reverse()->values()
                : collect(),
            'channelAccounts' => $this->accountOptions(),
            'userOptions' => auth()->user()->isAdmin()
                ? User::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function updatedChannel(): void
    {
        $this->selectedAccountId = null;
        $this->selectedConversationId = null;
    }

    public function updatedSelectedAccountId(): void
    {
        $this->selectedConversationId = null;
    }

    public function updatedSelectedUserId(): void
    {
        $this->selectedConversationId = null;
    }

    public function selectConversation(int $conversationId): void
    {
        $conversation = Conversation::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($conversationId);

        $this->authorize('view', $conversation);

        $this->selectedConversationId = $conversation->id;
        $this->replyMessage = '';

        if ($conversation->unread_count > 0) {
            $conversation->update(['unread_count' => 0]);
        }
    }

    public function refreshInbox(): void
    {
        $this->syncSelectedConversationUnread();
    }

    public function sendReply(): void
    {
        $this->validate([
            'selectedConversationId' => ['required', 'integer', 'exists:conversations,id'],
            'replyMessage' => ['required', 'string', 'max:2000'],
        ]);

        $conversation = Conversation::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->with('channelAccount')
            ->findOrFail($this->selectedConversationId);

        $this->authorize('view', $conversation);

        $log = MessageLog::create([
            'user_id' => $conversation->user_id,
            'channel' => $conversation->channel,
            'channel_account_id' => $conversation->channel_account_id,
            'conversation_id' => $conversation->id,
            'whatsapp_device_id' => $conversation->channel === 'whatsapp'
                ? (int) ($conversation->channelAccount?->external_id ?? 0) ?: null
                : null,
            'direction' => MessageLog::DIRECTION_OUTGOING,
            'type' => MessageLog::TYPE_TEXT,
            'phone' => ContactKeyNormalizer::normalizeForChannel($conversation->channel, $conversation->contact_key),
            'message' => $this->replyMessage,
            'status' => MessageLog::STATUS_PENDING,
            'raw_payload' => ['from_inbox' => true],
        ]);

        $conversation->update([
            'last_message_preview' => mb_substr($this->replyMessage, 0, 140),
            'last_message_at' => $log->created_at,
            'last_outgoing_at' => $log->created_at,
        ]);

        SendMessageJob::dispatch($log->id);

        $this->replyMessage = '';
    }

    private function selectedConversation(): ?Conversation
    {
        if (! $this->selectedConversationId) {
            return null;
        }

        return Conversation::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->with('channelAccount')
            ->find($this->selectedConversationId);
    }

    private function conversations()
    {
        return Conversation::query()
            ->with('channelAccount:id,name,channel')
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->when($this->channel !== 'all', fn (Builder $builder) => $builder->where('channel', $this->channel))
            ->when($this->selectedAccountId, fn (Builder $builder) => $builder->where('channel_account_id', $this->selectedAccountId))
            ->when($this->search, function (Builder $builder) {
                $builder->where(function (Builder $query) {
                    $query->where('contact_key', 'like', '%'.$this->search.'%')
                        ->orWhere('contact_name', 'like', '%'.$this->search.'%')
                        ->orWhere('last_message_preview', 'like', '%'.$this->search.'%');
                });
            })
            ->orderByDesc('last_message_at')
            ->limit(200)
            ->get();
    }

    private function accountOptions()
    {
        return ChannelAccount::query()
            ->select(['id', 'name', 'channel'])
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->when($this->channel !== 'all', fn (Builder $builder) => $builder->where('channel', $this->channel))
            ->orderBy('channel')
            ->orderBy('name')
            ->get();
    }

    private function applyUserScope(Builder $builder): Builder
    {
        $viewer = auth()->user();

        if ($viewer->isAdmin()) {
            if ($this->selectedUserId) {
                $builder->where('user_id', $this->selectedUserId);
            }

            return $builder;
        }

        return $builder->where('user_id', $viewer->id);
    }

    private function syncSelectedConversationUnread(): void
    {
        if (! $this->selectedConversationId) {
            return;
        }

        $conversation = Conversation::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->select(['id', 'unread_count'])
            ->find($this->selectedConversationId);

        if ($conversation && $conversation->unread_count > 0) {
            $conversation->update(['unread_count' => 0]);
        }
    }
}
