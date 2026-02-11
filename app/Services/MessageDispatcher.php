<?php

namespace App\Services;

use App\Models\MessageLog;
use App\Models\PanelNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

class MessageDispatcher
{
    public function __construct(private readonly WhatsAppGateway $gateway)
    {
    }

    public function send(MessageLog $log, array $options = []): void
    {
        $log->refresh();
        $log->update([
            'status' => MessageLog::STATUS_QUEUED,
            'error_message' => null,
        ]);

        try {
            $response = $this->gateway->send($log, $options);

            $gatewayMessageId = $response['message_id'] ?? $response['id'] ?? null;

            $log->update([
                'status' => MessageLog::STATUS_SENT,
                'raw_payload' => $response,
                'gateway_message_id' => $gatewayMessageId,
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
                'body' => sprintf('Could not send WhatsApp message to %s (%s).', $log->phone, $log->type),
                'type' => 'message',
            ]);

            Log::error('Failed to send WhatsApp message', [
                'log_id' => $log->getKey(),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
