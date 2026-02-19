<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChannelAccount;
use App\Models\MessageLog;
use App\Models\PanelNotification;
use App\Services\ConversationRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class TelegramWebhookController extends Controller
{
    public function __construct(private readonly ConversationRegistry $conversations) {}

    public function incomingMessage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'account_id' => ['nullable', 'integer'],
            'account_external_id' => ['nullable', 'string'],
            'chat_id' => ['required', 'string'],
            'type' => ['required', 'string', Rule::in(['text', 'image', 'document', 'button', 'list'])],
            'message' => ['nullable', 'string'],
            'from_name' => ['nullable', 'string'],
            'message_id' => ['nullable', 'string'],
            'raw_payload' => ['nullable', 'array'],
        ]);

        $account = $this->resolveAccount($data['account_id'] ?? null, $data['account_external_id'] ?? null);

        if (! $account) {
            return response()->json(['status' => 'ignored', 'message' => 'Telegram account not found'], 200);
        }

        $log = MessageLog::create([
            'user_id' => $account->user_id,
            'channel' => ChannelAccount::CHANNEL_TELEGRAM,
            'channel_account_id' => $account->id,
            'direction' => MessageLog::DIRECTION_INCOMING,
            'type' => $data['type'],
            'phone' => $data['chat_id'],
            'message' => $data['message'] ?? '',
            'status' => MessageLog::STATUS_DELIVERED,
            'gateway_message_id' => $data['message_id'] ?? null,
            'external_message_id' => $data['message_id'] ?? null,
            'raw_payload' => $data['raw_payload'] ?? $request->all(),
        ]);

        $this->conversations->assign($log, $account, (string) $data['chat_id'], $data['from_name'] ?? null);

        PanelNotification::create([
            'user_id' => $account->user_id,
            'title' => 'Incoming Telegram message',
            'body' => sprintf('Message from %s on %s', $data['chat_id'], $account->name),
            'type' => 'message',
        ]);

        return response()->json(['status' => 'ok', 'log_id' => $log->id]);
    }

    public function deliveryStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message_id' => ['nullable', 'string'],
            'external_message_id' => ['nullable', 'string'],
            'log_id' => ['nullable', 'integer'],
            'account_id' => ['nullable', 'integer'],
            'account_external_id' => ['nullable', 'string'],
            'chat_id' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['sent', 'delivered', 'read', 'failed'])],
            'error' => ['nullable', 'string'],
            'timestamp' => ['nullable', 'date'],
        ]);

        if (empty($data['message_id']) && empty($data['external_message_id']) && empty($data['log_id']) && empty($data['account_id']) && empty($data['account_external_id']) && empty($data['chat_id'])) {
            return response()->json(['status' => 'ignored', 'message' => 'No identifiers provided'], 200);
        }

        $account = $this->resolveAccount($data['account_id'] ?? null, $data['account_external_id'] ?? null);

        $log = MessageLog::query()
            ->where('channel', ChannelAccount::CHANNEL_TELEGRAM)
            ->where('direction', MessageLog::DIRECTION_OUTGOING)
            ->when($data['log_id'] ?? null, fn ($query, $id) => $query->where('id', $id))
            ->when($account, fn ($query) => $query->where('channel_account_id', $account->id))
            ->when($data['chat_id'] ?? null, fn ($query, $chatId) => $query->where('phone', $chatId))
            ->when($data['message_id'] ?? null, fn ($query, $messageId) => $query->where('gateway_message_id', $messageId))
            ->when($data['external_message_id'] ?? null, fn ($query, $messageId) => $query->where('external_message_id', $messageId))
            ->latest()
            ->first();

        if (! $log) {
            return response()->json(['status' => 'ignored', 'message' => 'Log not found'], 200);
        }

        $timestamp = $data['timestamp'] ? Carbon::parse($data['timestamp']) : now();

        $update = [
            'status' => $data['status'],
        ];

        if ($data['status'] === MessageLog::STATUS_SENT) {
            $update['sent_at'] = $timestamp;
        }

        if ($data['status'] === MessageLog::STATUS_DELIVERED) {
            $update['delivered_at'] = $timestamp;
        }

        if ($data['status'] === MessageLog::STATUS_READ) {
            $update['read_at'] = $timestamp;
        }

        if ($data['status'] === MessageLog::STATUS_FAILED) {
            $update['error_message'] = $data['error'] ?? $log->error_message;
        } else {
            $update['error_message'] = null;
        }

        $messageId = $data['message_id'] ?? $data['external_message_id'] ?? null;

        if ($messageId) {
            $update['gateway_message_id'] = $messageId;
            $update['external_message_id'] = $messageId;
        }

        $log->update($update);

        return response()->json(['status' => 'ok', 'log_id' => $log->id]);
    }

    private function resolveAccount(?int $accountId, ?string $accountExternalId): ?ChannelAccount
    {
        if (! $accountId && blank($accountExternalId)) {
            return null;
        }

        return ChannelAccount::query()
            ->where('channel', ChannelAccount::CHANNEL_TELEGRAM)
            ->when($accountId, fn ($query) => $query->where('id', $accountId))
            ->when($accountExternalId, function ($query) use ($accountExternalId, $accountId) {
                return $accountId
                    ? $query->orWhere('external_id', $accountExternalId)
                    : $query->where('external_id', $accountExternalId);
            })
            ->first();
    }
}
