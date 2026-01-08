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
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class DeviceQrConnect extends Component
{
    use AuthorizesRequests;

    public ?WhatsAppDevice $device = null;
    public ?string $qrSvg = null;
    public bool $showModal = false;

    #[On('show-qr-modal')]
    public function showQrModal(int $deviceId): void
    {
        Log::info('DeviceQrConnect: showQrModal called', [
            'device_id' => $deviceId,
        ]);

        if (! $this->loadDevice($deviceId)) {
            return;
        }

        $this->showModal = true;

        Log::info('DeviceQrConnect: Modal opened', [
            'device_id' => $this->device?->id,
            'qrSvg_exists' => filled($this->qrSvg),
        ]);
    }

    public function pollQrStatus(): void
    {
        if (! $this->showModal || $this->qrSvg || ! $this->device?->id) {
            return;
        }

        $this->loadDevice($this->device->id);
    }

    public function closeModal(): void
    {
        Log::info('DeviceQrConnect: closeModal called');

        $this->reset(['showModal', 'device', 'qrSvg']);

        Log::info('DeviceQrConnect: Modal closed', [
            'showModal' => $this->showModal,
        ]);
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

    private function loadDevice(int $deviceId): bool
    {
        $device = WhatsAppDevice::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->find($deviceId);

        if (! $device) {
            Log::warning('DeviceQrConnect: Device not found', [
                'device_id' => $deviceId,
            ]);

            return false;
        }

        $this->authorize('view', $device);

        $this->device = $device->fresh();
        $this->qrSvg = $this->generateQr($this->device);

        return true;
    }

    private function generateQr(WhatsAppDevice $device): ?string
    {
        Log::info('DeviceQrConnect: generateQr started', [
            'device_id' => $device->id,
            'device_name' => $device->name,
        ]);

        $session = $device->session;

        if (is_string($session)) {
            $decoded = json_decode($session, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $session = $decoded;
            }
        }

        if (! is_array($session)) {
            return null;
        }

        $payload = $session['qr'] ?? $session['qrCode'] ?? $session['ref'] ?? null;

        if (blank($payload)) {
            return null;
        }

        try {
            $renderer = new ImageRenderer(
                new RendererStyle(260),
                new SvgImageBackEnd()
            );

            $writer = new Writer($renderer);

            return $writer->writeString($payload);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}
