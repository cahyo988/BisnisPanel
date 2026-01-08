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
        $deviceBase = WhatsAppDevice::query();
        $this->applyUserFilter($deviceBase, $targetUser);
        $devicesTotal = (clone $deviceBase)->count();
        $devicesCreatedToday = (clone $deviceBase)->whereDate('created_at', Carbon::today())->count();
        $devicesPrevious = max($devicesTotal - $devicesCreatedToday, 0);

        $outgoingBase = MessageLog::query()
            ->where('direction', MessageLog::DIRECTION_OUTGOING)
            ->whereIn('status', [
                MessageLog::STATUS_SENT,
                MessageLog::STATUS_DELIVERED,
                MessageLog::STATUS_READ,
            ]);
        $this->applyUserFilter($outgoingBase, $targetUser);
        $outgoingToday = (clone $outgoingBase)->whereDate('created_at', Carbon::today())->count();
        $outgoingYesterday = (clone $outgoingBase)->whereDate('created_at', Carbon::yesterday())->count();

        $failedBase = MessageLog::query()->where('status', MessageLog::STATUS_FAILED);
        $this->applyUserFilter($failedBase, $targetUser);
        $failedToday = (clone $failedBase)->whereDate('created_at', Carbon::today())->count();
        $failedYesterday = (clone $failedBase)->whereDate('created_at', Carbon::yesterday())->count();

        $incomingBase = MessageLog::query()->where('direction', MessageLog::DIRECTION_INCOMING);
        $this->applyUserFilter($incomingBase, $targetUser);
        $incomingToday = (clone $incomingBase)->whereDate('created_at', Carbon::today())->count();
        $incomingYesterday = (clone $incomingBase)->whereDate('created_at', Carbon::yesterday())->count();

        return [
            [
                'label' => 'WhatsApp Devices',
                'value' => $devicesTotal,
                'description' => 'Total devices connected for this view.',
                'trend' => $this->formatTrend($devicesTotal, $devicesPrevious),
            ],
            [
                'label' => 'Messages Sent Today',
                'value' => $outgoingToday,
                'description' => 'Successful outgoing messages in the last 24 hours.',
                'trend' => $this->formatTrend($outgoingToday, $outgoingYesterday),
            ],
            [
                'label' => 'Failed Messages',
                'value' => $failedToday,
                'description' => 'Messages that failed to send (today).',
                'trend' => $this->formatTrend($failedToday, $failedYesterday),
            ],
            [
                'label' => 'Incoming Today',
                'value' => $incomingToday,
                'description' => 'Incoming device messages captured today.',
                'trend' => $this->formatTrend($incomingToday, $incomingYesterday),
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

    /**
     * @return array{direction: string, value: float, caption: string}
     */
    private function formatTrend(int $current, int $previous): array
    {
        $difference = $current - $previous;

        $direction = 'neutral';
        if ($difference > 0) {
            $direction = 'up';
        } elseif ($difference < 0) {
            $direction = 'down';
        }

        $percentage = $previous === 0
            ? ($current > 0 ? 100.0 : 0.0)
            : round(abs($difference) / max($previous, 1) * 100, 1);

        return [
            'direction' => $direction,
            'value' => $percentage,
            'caption' => __('vs previous period'),
        ];
    }
}
