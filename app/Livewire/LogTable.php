<?php

namespace App\Livewire;

use App\Models\MessageLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class LogTable extends Component
{
    use WithPagination;

    public string $status = 'all';
    public string $direction = 'all';
    public string $search = '';
    public int $perPage = 10;
    public ?int $selectedUserId = null;

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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedUserId(): void
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
        ]);
    }

    private function logs()
    {
        return MessageLog::query()
            ->with('device:id,name')
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->when($this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->direction !== 'all', fn (Builder $query) => $query->where('direction', $this->direction))
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $builder) {
                    $builder->where('phone', 'like', '%'.$this->search.'%')
                        ->orWhere('message', 'like', '%'.$this->search.'%');
                });
            })
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
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
