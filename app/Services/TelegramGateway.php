<?php

namespace App\Services;

use App\Models\ChannelAccount;
use App\Models\MessageLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class TelegramGateway
{
    public function __construct(private readonly HttpFactory $http) {}

    /**
     * @return array<mixed>
     */
    public function send(MessageLog $log, array $overrides = []): array
    {
        if ($log->channel !== ChannelAccount::CHANNEL_TELEGRAM) {
            throw new \InvalidArgumentException(sprintf('TelegramGateway cannot send channel [%s].', (string) $log->channel));
        }

        $payload = array_merge([
            'account_id' => $log->channel_account_id,
            'chat_id' => $log->phone,
            'type' => $log->type,
            'message' => $log->message,
            'log_id' => $log->id,
            'raw_payload' => $log->raw_payload,
        ], $overrides);

        $baseUrl = config('services.telegram.base_url');

        if (blank($baseUrl)) {
            return [
                'driver' => 'mock',
                'payload' => $payload,
            ];
        }

        try {
            $response = $this->client()
                ->post('/messages', $payload)
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            Log::error('Telegram gateway request failed', [
                'log_id' => $log->id,
                'account_id' => $log->channel_account_id,
                'chat_id' => $log->phone,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $response->json() ?? Arr::wrap($response->body());
    }

    private function client(): PendingRequest
    {
        $request = $this->http->baseUrl(config('services.telegram.base_url'))
            ->acceptJson()
            ->timeout(config('services.telegram.timeout', 10))
            ->connectTimeout(config('services.telegram.connect_timeout', 5));

        if ($token = config('services.telegram.token')) {
            $request = $request->withToken($token);
        }

        return $request;
    }
}
