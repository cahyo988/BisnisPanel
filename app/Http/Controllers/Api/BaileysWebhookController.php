<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendMessageJob;
use App\Models\MessageLog;
use App\Models\PanelNotification;
use App\Models\WhatsAppDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BaileysWebhookController extends Controller
{
    public function incomingMessage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['nullable', 'integer'],
            'device_phone' => ['nullable', 'string'],
            'from' => ['required', 'string'],
            'type' => ['required', 'string', Rule::in(['text', 'image', 'document', 'button'])],
            'message' => ['nullable', 'string'],
        ]);

        $device = $this->resolveDevice($data['device_id'] ?? null, $data['device_phone'] ?? null);

        if (! $device) {
            return response()->json(['status' => 'ignored', 'message' => 'Device not found'], 200);
        }

        $log = MessageLog::create([
            'user_id' => $device->user_id,
            'whatsapp_device_id' => $device->id,
            'direction' => MessageLog::DIRECTION_INCOMING,
            'type' => $data['type'],
            'phone' => $data['from'],
            'message' => $data['message'] ?? '',
            'status' => MessageLog::STATUS_DELIVERED,
            'raw_payload' => $request->all(),
        ]);

        PanelNotification::create([
            'user_id' => $device->user_id,
            'title' => 'Incoming WhatsApp message',
            'body' => sprintf('Message from %s on %s', $data['from'], $device->name),
            'type' => 'message',
        ]);

        $this->runAutoReplies($device, $data['message'] ?? '', $data['from']);

        return response()->json(['status' => 'ok', 'log_id' => $log->id]);
    }

    public function deviceStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['nullable', 'integer'],
            'phone_number' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['connected', 'disconnected'])],
            'session' => ['nullable', 'array'],
            'last_connected_at' => ['nullable', 'date'],
            'last_seen_at' => ['nullable', 'date'],
        ]);

        $device = $this->resolveDevice($data['device_id'] ?? null, $data['phone_number'] ?? null);

        if (! $device) {
            return response()->json(['status' => 'ignored', 'message' => 'Device not found'], 200);
        }

        $previousStatus = $device->status;

        $device->fill([
            'status' => $data['status'],
            'session' => $data['session'] ?? $device->session,
            'last_connected_at' => $data['last_connected_at'] ?? $device->last_connected_at,
            'last_seen_at' => $data['last_seen_at'] ?? $device->last_seen_at,
        ]);
        $device->save();

        if ($previousStatus !== $device->status) {
            PanelNotification::create([
                'user_id' => $device->user_id,
                'title' => sprintf('Device %s is now %s', $device->name, $device->status),
                'body' => sprintf('Phone %s reported a %s status update.', $device->phone_number, $device->status),
                'type' => 'device',
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    private function resolveDevice(?int $deviceId, ?string $phoneNumber): ?WhatsAppDevice
    {
        if (! $deviceId && ! $phoneNumber) {
            return null;
        }

        return WhatsAppDevice::query()
            ->when($deviceId, fn ($query) => $query->where('id', $deviceId))
            ->when($phoneNumber, function ($query) use ($phoneNumber, $deviceId) {
                return $deviceId
                    ? $query->orWhere('phone_number', $phoneNumber)
                    : $query->where('phone_number', $phoneNumber);
            })
            ->first();
    }

    private function runAutoReplies(WhatsAppDevice $device, string $incoming, string $sender): void
    {
        $incomingNormalized = mb_strtolower(trim($incoming));

        if ($incomingNormalized === '') {
            return;
        }

        $rule = $device->autoReplyRules()
            ->where('is_active', true)
            ->get()
            ->first(function ($rule) use ($incomingNormalized) {
                $keyword = mb_strtolower($rule->keyword);

                return $rule->match_mode === 'exact'
                    ? $incomingNormalized === $keyword
                    : str_contains($incomingNormalized, $keyword);
            });

        if (! $rule) {
            return;
        }

        $log = MessageLog::create([
            'user_id' => $device->user_id,
            'whatsapp_device_id' => $device->id,
            'direction' => MessageLog::DIRECTION_OUTGOING,
            'type' => MessageLog::TYPE_TEXT,
            'phone' => $sender,
            'message' => $rule->reply_text,
            'status' => MessageLog::STATUS_PENDING,
            'raw_payload' => ['auto_reply_rule_id' => $rule->id],
        ]);

        SendMessageJob::dispatch($log->id);
    }
}
