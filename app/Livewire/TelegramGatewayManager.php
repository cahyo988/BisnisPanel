<?php

namespace App\Livewire;

use App\Models\ChannelAccount;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Validation\Rule;
use Livewire\Component;

class TelegramGatewayManager extends Component
{
    public string $status = 'connected';

    public string $name = '';

    public string $externalId = '';

    public ?int $selectedUserId = null;

    public function mount(): void
    {
        $this->selectedUserId = auth()->id();
    }

    public function render(): View
    {
        return view('livewire.telegram-gateway-manager', [
            'accounts' => $this->accounts(),
            'users' => auth()->user()->isAdmin()
                ? User::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'externalId' => [
                'required',
                'string',
                'max:120',
                Rule::unique('channel_accounts', 'external_id')->where(
                    fn (QueryBuilder $builder) => $builder->where('channel', ChannelAccount::CHANNEL_TELEGRAM)
                ),
            ],
            'status' => ['required', Rule::in(['connected', 'disconnected'])],
            'selectedUserId' => auth()->user()->isAdmin()
                ? ['required', 'integer', 'exists:users,id']
                : ['nullable'],
        ]);

        $viewer = auth()->user();
        $targetUserId = $viewer->isAdmin()
            ? (int) ($validated['selectedUserId'] ?? $this->selectedUserId)
            : $viewer->id;

        ChannelAccount::query()->create([
            'user_id' => $targetUserId,
            'channel' => ChannelAccount::CHANNEL_TELEGRAM,
            'name' => $validated['name'],
            'external_id' => trim($validated['externalId']),
            'status' => $validated['status'],
        ]);

        $this->reset(['name', 'externalId']);
        $this->status = 'connected';

        if ($viewer->isAdmin()) {
            $this->selectedUserId = null;
        }

        session()->flash('telegram_gateway_saved', __('Telegram gateway saved.'));

        $this->dispatch('close-modal', 'add-telegram-gateway');
    }

    public function remove(int $accountId): void
    {
        $account = ChannelAccount::query()
            ->where('channel', ChannelAccount::CHANNEL_TELEGRAM)
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($accountId);

        $account->delete();

        session()->flash('telegram_gateway_saved', __('Telegram gateway removed.'));
    }

    private function accounts()
    {
        return ChannelAccount::query()
            ->where('channel', ChannelAccount::CHANNEL_TELEGRAM)
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
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
}
