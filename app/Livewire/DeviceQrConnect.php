<?php

namespace App\Livewire;

use App\Models\WhatsAppDevice;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class DeviceQrConnect extends Component
{
    use AuthorizesRequests;

    public ?WhatsAppDevice $device = null;
    public ?string $qrSvg = null;

    #[On('qr-device-selected')]
    public function loadDevice(int $deviceId): void
    {
        $device = WhatsAppDevice::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->find($deviceId);

        if (! $device) {
            return;
        }

        $this->authorize('view', $device);

        $this->device = $device;
        $this->qrSvg = $this->generateQr($device);
    }

    public function clear(): void
    {
        $this->device = null;
        $this->qrSvg = null;
    }

    public function render(): View
    {
        return view('livewire.device-qr-connect');
    }

    private function applyUserScope(Builder $builder): Builder
    {
        $viewer = auth()->user();

        if ($viewer->isAdmin()) {
            return $builder;
        }

        return $builder->where('user_id', $viewer->id);
    }

    private function generateQr(WhatsAppDevice $device): ?string
    {
        $session = $device->session ?? [];
        $payload = data_get($session, 'qr') ?? data_get($session, 'qrCode') ?? data_get($session, 'ref') ?? $device->phone_number;

        if (blank($payload)) {
            return null;
        }

        $renderer = new ImageRenderer(
            new RendererStyle(260),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        return $writer->writeString($payload);
    }
}

