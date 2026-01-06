<?php

namespace App\Livewire;

use App\Models\MessageLog;
use App\Models\User;
use App\Models\WhatsAppDevice;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Component;

class DashboardStats extends Component
{
    public ?int $filterUserId = null;

    public function mount(): void
    {
        if (! auth()->user()->isAdmin()) {
            $this->filterUserId = auth()->id();
        }
    }

    public function render(): View
    {
        $viewer = auth()->user();

        $targetUser = $viewer->isAdmin() && $this->filterUserId
            ? User::query()->find($this->filterUserId)
            : ($viewer->isAdmin() ? null : $viewer);

        return view('livewire.dashboard-stats', [
            'stats' => $this->buildStats($targetUser),
            'userOptions' => $viewer->isAdmin()
                ? User::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'targetUser' => $targetUser,
        ]);
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function buildStats(?User $targetUser): array
    {
        $devicesQuery = WhatsAppDevice::query();
        $messagesQuery = MessageLog::query();
        $failedQuery = MessageLog::query()->where('status', MessageLog::STATUS_FAILED);
        $incomingQuery = MessageLog::query()
            ->where('direction', MessageLog::DIRECTION_INCOMING)
            ->whereDate('created_at', Carbon::today());

        collect([$devicesQuery, $messagesQuery, $failedQuery, $incomingQuery])
            ->each(fn (Builder $builder) => $this->applyUserFilter($builder, $targetUser));

        $outgoingToday = $messagesQuery
            ->where('direction', MessageLog::DIRECTION_OUTGOING)
            ->whereIn('status', [
                MessageLog::STATUS_SENT,
                MessageLog::STATUS_DELIVERED,
                MessageLog::STATUS_READ,
            ])
            ->whereDate('created_at', Carbon::today())
            ->count();

        return [
            [
                'label' => 'WhatsApp Devices',
                'value' => $devicesQuery->count(),
                'description' => 'Total devices connected for this view.',
            ],
            [
                'label' => 'Messages Sent Today',
                'value' => $outgoingToday,
                'description' => 'Successful outgoing messages in the last 24 hours.',
            ],
            [
                'label' => 'Failed Messages',
                'value' => $failedQuery->count(),
                'description' => 'Messages that failed to send.',
            ],
            [
                'label' => 'Incoming Today',
                'value' => $incomingQuery->count(),
                'description' => 'Incoming device messages captured today.',
            ],
        ];
    }

    private function applyUserFilter(Builder $builder, ?User $targetUser): void
    {
        $viewer = auth()->user();

        if ($targetUser) {
            $builder->where('user_id', $targetUser->id);

            return;
        }

        if (! $viewer->isAdmin()) {
            $builder->where('user_id', $viewer->id);
        }
    }
}

