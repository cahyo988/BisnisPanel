<?php

namespace App\Services;

use App\Models\MessageLog;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;

class WhatsAppGateway
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    /**
     * Send a message through the Node/Baileys gateway.
     *
     * @return array<mixed>
     */
    public function send(MessageLog $log, array $overrides = []): array
    {
        $payload = array_merge([
            'device_id' => $log->whatsapp_device_id,
            'device_phone' => optional($log->device)->phone_number,
            'to' => $log->phone,
            'type' => $log->type,
            'message' => $log->message,
            'log_id' => $log->id,
            'raw_payload' => $log->raw_payload,
        ], $overrides);

        $baseUrl = config('services.whatsapp.base_url');

        if (blank($baseUrl)) {
            return [
                'driver' => 'mock',
                'payload' => $payload,
            ];
        }

        $response = $this->client()
            ->post('/messages', $payload)
            ->throw();

        return $response->json() ?? Arr::wrap($response->body());
    }

    /**
     * Ask the gateway to start a device session and emit a QR payload.
     *
     * @return array<mixed>
     */
    public function connectDevice(int $deviceId, ?string $devicePhone, ?string $name = null, bool $force = false): array
    {
        $payload = [
            'device_id' => $deviceId,
            'device_phone' => $devicePhone,
            'name' => $name,
            'force' => $force,
        ];

        $baseUrl = config('services.whatsapp.base_url');

        if (blank($baseUrl)) {
            return [
                'driver' => 'mock',
                'payload' => $payload,
            ];
        }

        $response = $this->client()
            ->post('/devices/connect', $payload)
            ->throw();

        return $response->json() ?? Arr::wrap($response->body());
    }

    /**
     * Disconnect a device session from the gateway.
     *
     * @return array<mixed>
     */
    public function disconnectDevice(int $deviceId): array
    {
        $payload = [
            'device_id' => $deviceId,
        ];

        $baseUrl = config('services.whatsapp.base_url');

        if (blank($baseUrl)) {
            return [
                'driver' => 'mock',
                'payload' => $payload,
            ];
        }

        $response = $this->client()
            ->post('/devices/disconnect', $payload)
            ->throw();

        return $response->json() ?? Arr::wrap($response->body());
    }

    private function client(): PendingRequest
    {
        $request = $this->http->baseUrl(config('services.whatsapp.base_url'))
            ->acceptJson()
            ->timeout(config('services.whatsapp.timeout', 10))
            ->connectTimeout(config('services.whatsapp.connect_timeout', 5));

        if ($token = config('services.whatsapp.token')) {
            $request = $request->withToken($token);
        }

        return $request;
    }
}
