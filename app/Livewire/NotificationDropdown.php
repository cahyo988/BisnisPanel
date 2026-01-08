<?php

namespace App\Livewire;

use App\Models\PanelNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class NotificationDropdown extends Component
{
    use AuthorizesRequests;

    public function render(): View
    {
        return view('livewire.notification-dropdown', [
            'notifications' => $this->notifications,
            'unreadCount' => $this->unreadCount,
        ]);
    }

    public function markAsRead(int $notificationId): void
    {
        $notification = PanelNotification::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($notificationId);

        $this->authorize('update', $notification);

        $notification->markAsRead();
    }

    public function markAll(): void
    {
        PanelNotification::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public $limit = 2;

    public function loadMore(): void
    {
        $this->limit += 5;
    }

    public function getNotificationsProperty()
    {
        return PanelNotification::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->latest()
            ->limit($this->limit)
            ->get();
    }

    public function getUnreadCountProperty(): int
    {
        return PanelNotification::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->whereNull('read_at')
            ->count();
    }

    private function applyUserScope(Builder $builder): Builder
    {
        $viewer = auth()->user();

        if (! $viewer->isAdmin()) {
            $builder->where('user_id', $viewer->id);
        }

        return $builder;
    }
}
