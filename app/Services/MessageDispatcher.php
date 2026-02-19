<?php

namespace App\Services;

use App\Models\MessageLog;
use App\Models\PanelNotification;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class MessageDispatcher
{
    public function __construct(
        private readonly TelegramGateway $telegramGateway,
        private readonly WhatsAppGateway $whatsAppGateway
    ) {}

    public function send(MessageLog $log, array $options = []): void
    {
        $log->refresh();
        $log->update([
            'status' => MessageLog::STATUS_QUEUED,
            'error_message' => null,
        ]);

        try {
            $response = $this->resolveGateway($log->channel)->send($log, $options);

            $gatewayMessageId = $response['message_id'] ?? $response['id'] ?? null;

            $log->update([
                'status' => MessageLog::STATUS_SENT,
                'raw_payload' => $response,
                'gateway_message_id' => $gatewayMessageId,
                'external_message_id' => $gatewayMessageId,
                'sent_at' => now(),
                'error_message' => null,
            ]);
        } catch (Throwable $exception) {
            $log->update([
                'status' => MessageLog::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
            ]);

            PanelNotification::create([
                'user_id' => $log->user_id,
                'title' => 'Message delivery failed',
                'body' => sprintf('Could not send %s message to %s (%s).', ucfirst((string) $log->channel), $log->phone, $log->type),
                'type' => 'message',
            ]);

            Log::error('Failed to send outbound message', [
                'log_id' => $log->getKey(),
                'channel' => $log->channel,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function resolveGateway(?string $channel): WhatsAppGateway|TelegramGateway
    {
        $resolvedChannel = $channel ?: 'whatsapp';

        if ($resolvedChannel === 'whatsapp') {
            return $this->whatsAppGateway;
        }

        if ($resolvedChannel === 'telegram') {
            return $this->telegramGateway;
        }

        throw new InvalidArgumentException(sprintf('Gateway for channel [%s] is not configured.', $resolvedChannel));
    }
}
