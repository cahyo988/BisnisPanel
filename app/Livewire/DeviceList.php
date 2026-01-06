<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\WhatsAppDevice;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class DeviceList extends Component
{
    use AuthorizesRequests;

    public string $status = 'all';
    public ?int $selectedUserId = null;
    public string $search = '';

    protected $listeners = [
        'device-created' => '$refresh',
    ];

    public function mount(): void
    {
        if (! auth()->user()->isAdmin()) {
            $this->selectedUserId = auth()->id();
        }
    }

    public function render(): View
    {
        return view('livewire.device-list', [
            'devices' => $this->devices,
            'userOptions' => auth()->user()->isAdmin()
                ? User::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function getDevicesProperty()
    {
        return WhatsAppDevice::query()
            ->withCount('messageLogs')
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->when($this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $builder) {
                    $builder
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('phone_number', 'like', '%'.$this->search.'%');
                });
            })
            ->orderByDesc('updated_at')
            ->get();
    }

    public function remove(int $deviceId): void
    {
        $device = WhatsAppDevice::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($deviceId);

        $this->authorize('delete', $device);

        $device->delete();

        session()->flash('device_removed', 'Device removed.');
        $this->dispatch('device-removed');
    }

    public function showQr(int $deviceId): void
    {
        $device = WhatsAppDevice::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($deviceId);

        $this->authorize('view', $device);

        $this->dispatch('qr-device-selected', deviceId: $device->id);
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
