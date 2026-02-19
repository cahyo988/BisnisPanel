<?php

namespace App\Livewire;

use App\Models\ChannelAccount;
use App\Models\MessageLog;
use App\Models\User;
use App\Services\MessageDispatcher;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class LogTable extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $status = 'all';

    public string $direction = 'all';

    public string $channel = 'all';

    public string $search = '';

    public int $perPage = 10;

    public ?int $selectedUserId = null;

    public ?int $selectedChannelAccountId = null;

    protected $listeners = [
        'message-sent' => '$refresh',
    ];

    public function mount(int $perPage = 10): void
    {
        $this->perPage = $perPage;

        if (! auth()->user()->isAdmin()) {
            $this->selectedUserId = auth()->id();
        }
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingDirection(): void
    {
        $this->resetPage();
    }

    public function updatingChannel(): void
    {
        $this->selectedChannelAccountId = null;
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedUserId(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedChannelAccountId(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.log-table', [
            'logs' => $this->logs(),
            'userOptions' => auth()->user()->isAdmin()
                ? User::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'channelAccountOptions' => $this->channelAccountOptions(),
        ]);
    }

    #[On('retry-log-delivery')]
    public function retryDelivery(int $logId, MessageDispatcher $dispatcher): void
    {
        try {
            $log = MessageLog::query()
                ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
                ->findOrFail($logId);

            $this->authorize('update', $log);

            if ($log->direction !== MessageLog::DIRECTION_OUTGOING) {
                $this->dispatchBrowserEvent('notify', [
                    'type' => 'error',
                    'message' => __('Only outgoing messages can be retried.'),
                ]);

                return;
            }

            if ($log->scheduled_at && $log->scheduled_at->isFuture()) {
                $this->dispatchBrowserEvent('notify', [
                    'type' => 'error',
                    'message' => __('Scheduled messages cannot be retried until due.'),
                ]);

                return;
            }

            dispatch(function () use ($dispatcher, $log): void {
                $dispatcher->send($log);
            })->afterResponse();

            $this->dispatchBrowserEvent('notify', [
                'type' => 'success',
                'message' => __('Retry queued. The gateway will resend shortly.'),
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to retry message delivery', [
                'log_id' => $logId,
                'message' => $exception->getMessage(),
            ]);

            $this->dispatchBrowserEvent('notify', [
                'type' => 'error',
                'message' => __('Failed to retry delivery.'),
            ]);
        }
    }

    private function logs()
    {
        return MessageLog::query()
            ->with(['device:id,name', 'channelAccount:id,name,channel'])
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->when($this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->direction !== 'all', fn (Builder $query) => $query->where('direction', $this->direction))
            ->when($this->channel !== 'all', fn (Builder $query) => $query->where('channel', $this->channel))
            ->when($this->selectedChannelAccountId, fn (Builder $query) => $query->where('channel_account_id', $this->selectedChannelAccountId))
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $builder) {
                    $builder->where('phone', 'like', '%'.$this->search.'%')
                        ->orWhere('message', 'like', '%'.$this->search.'%');
                });
            })
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    private function channelAccountOptions()
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
}
