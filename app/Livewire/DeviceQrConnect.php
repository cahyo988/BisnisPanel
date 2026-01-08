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
    public bool $showModal = false;

    #[On('show-qr-modal')]
    public function showQrModal(int $deviceId): void
    {
        \Illuminate\Support\Facades\Log::info('DeviceQrConnect: showQrModal called', [
            'device_id' => $deviceId,
        ]);

        $device = WhatsAppDevice::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->where('id', $deviceId)
            ->first();

        if (! $device) {
            \Illuminate\Support\Facades\Log::warning('DeviceQrConnect: Device not found', [
                'device_id' => $deviceId,
            ]);
            return;
        }

        $this->authorize('view', $device);

        $this->device = $device->fresh();
        $this->qrSvg = $this->generateQr($device);
        $this->showModal = true;
        
        \Illuminate\Support\Facades\Log::info('DeviceQrConnect: Modal opened', [
            'device_id' => $this->device->id,
            'qrSvg_exists' => !is_null($this->qrSvg),
        ]);
    }

    public function closeModal(): void
    {
        \Illuminate\Support\Facades\Log::info('DeviceQrConnect: closeModal called');
        
        $this->showModal = false;
        $this->device = null;
        $this->qrSvg = null;
        
        \Illuminate\Support\Facades\Log::info('DeviceQrConnect: Modal closed', [
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

    public function refresh(): void
    {
        // Stop refreshing if QR is already displayed
        if ($this->qrSvg) {
            return;
        }
        
        // Only refresh if we have a device selected
        if ($this->device) {
            $this->loadDevice($this->device->id);
        }
    }

    private function generateQr(WhatsAppDevice $device): ?string
    {
        \Illuminate\Support\Facades\Log::info('DeviceQrConnect: generateQr started', [
            'device_id' => $device->id,
            'device_name' => $device->name,
        ]);
        
        $session = $device->session;

        // Handle potential string format (if casting fails or double encoded)
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

