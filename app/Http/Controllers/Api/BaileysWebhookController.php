<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendMessageJob;
use App\Models\MessageLog;
use App\Models\PanelNotification;
use App\Models\WhatsAppDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class BaileysWebhookController extends Controller
{
    public function incomingMessage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['nullable', 'integer'],
            'device_phone' => ['nullable', 'string'],
            'from' => ['required', 'string'],
            'type' => ['required', 'string', Rule::in(['text', 'image', 'document', 'button', 'list'])],
            'message' => ['nullable', 'string'],
            'push_name' => ['nullable', 'string'],
            'selected_id' => ['nullable', 'string'],
            'selected_text' => ['nullable', 'string'],
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

        $incoming = $data['selected_id'] ?? $data['message'] ?? '';
        $isMenuSelection = in_array($data['type'], ['button', 'list'], true) && filled($data['selected_id'] ?? null);
        $this->runAutoReplies($device, $incoming, $data['from'], $isMenuSelection);

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

    public function deliveryStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message_id' => ['nullable', 'string'],
            'log_id' => ['nullable', 'integer'],
            'device_id' => ['nullable', 'integer'],
            'phone' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['sent', 'delivered', 'read', 'failed'])],
            'error' => ['nullable', 'string'],
            'timestamp' => ['nullable', 'date'],
        ]);

        if (empty($data['message_id']) && empty($data['log_id']) && empty($data['device_id']) && empty($data['phone'])) {
            return response()->json(['status' => 'ignored', 'message' => 'No identifiers provided'], 200);
        }

        $log = MessageLog::query()
            ->where('direction', MessageLog::DIRECTION_OUTGOING)
            ->when($data['log_id'] ?? null, fn ($query, $id) => $query->where('id', $id))
            ->when($data['message_id'] ?? null, fn ($query, $id) => $query->where('gateway_message_id', $id))
            ->when($data['device_id'] ?? null, fn ($query, $id) => $query->where('whatsapp_device_id', $id))
            ->when($data['phone'] ?? null, fn ($query, $phone) => $query->where('phone', $phone))
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

        if ($data['message_id'] ?? null) {
            $update['gateway_message_id'] = $data['message_id'];
        }

        $log->update($update);

        return response()->json(['status' => 'ok', 'log_id' => $log->id]);
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

    private function runAutoReplies(WhatsAppDevice $device, string $incoming, string $sender, bool $isMenuSelection): void
    {
        $incomingNormalized = mb_strtolower(trim($incoming));

        if (! $isMenuSelection) {
            if ($this->shouldSendGreeting($device)) {
                $this->sendGreetingAndMenu($device, $sender);
                return;
            }
        }

        if ($incomingNormalized === '') {
            return;
        }

        if (! $isMenuSelection) {
            return;
        }

        if ($this->handleMenuFlow($device, $incomingNormalized, $sender)) {
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

        $this->sendAutoReply(
            $device,
            $sender,
            $rule->reply_text,
            MessageLog::TYPE_TEXT,
            [],
            ['auto_reply_rule_id' => $rule->id]
        );
    }

    private function shouldSendGreeting(WhatsAppDevice $device): bool
    {
        return filled($device->auto_reply_greeting) || ! empty($device->auto_reply_menu);
    }

    private function sendGreetingAndMenu(WhatsAppDevice $device, string $sender): void
    {
        $greeting = $device->auto_reply_greeting;

        if (filled($greeting)) {
            $this->sendAutoReply(
                $device,
                $sender,
                $greeting,
                MessageLog::TYPE_TEXT,
                [],
                ['auto_reply_greeting' => true]
            );
        }

        $menu = $device->auto_reply_menu ?? $this->defaultMenu();
        if (! empty($menu) && isset($menu['root'])) {
            $this->sendDeviceMenu($device, $sender, 'root', $menu, 'info');
        }
    }

    private function handleMenuFlow(WhatsAppDevice $device, string $incomingNormalized, string $sender): bool
    {
        $keyword = $incomingNormalized;
        $menu = $device->auto_reply_menu ?? [];

        if (empty($menu)) {
            return false;
        }

        if ($keyword === 'info') {
            return $this->sendDeviceMenu($device, $sender, 'root', $menu, 'info');
        }

        if (array_key_exists($keyword, $menu)) {
            return $this->sendDeviceMenu($device, $sender, $keyword, $menu, $keyword);
        }

        return false;
    }

    private function sendDeviceMenu(
        WhatsAppDevice $device,
        string $sender,
        string $key,
        array $menu,
        string $menuKey
    ): bool {
        $entry = $menu[$key] ?? null;

        if (! is_array($entry) || blank($entry['text'] ?? null)) {
            return false;
        }

        $buttons = $entry['buttons'] ?? [];
        $text = (string) $entry['text'];

        if (is_array($buttons) && count($buttons) > 0) {
            $this->sendAutoReply(
                $device,
                $sender,
                $text,
                MessageLog::TYPE_LIST,
                ['type' => 'list', 'buttons' => $buttons],
                ['auto_reply_menu' => $menuKey, 'buttons' => $buttons]
            );

            return true;
        }

        $this->sendAutoReply(
            $device,
            $sender,
            $text,
            MessageLog::TYPE_TEXT,
            [],
            ['auto_reply_menu' => $menuKey]
        );

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultMenu(): array
    {
        return [
            'root' => [
                'text' => 'Pilih layanan yang kamu butuhkan:',
                'buttons' => [
                    ['id' => 'harga', 'text' => 'Harga'],
                    ['id' => 'joki', 'text' => 'Joki'],
                    ['id' => 'topup', 'text' => 'Topup'],
                ],
            ],
            'joki' => [
                'text' => 'Pilih tier joki yang kamu inginkan:',
                'buttons' => [
                    ['id' => 'mythic', 'text' => 'Mythic'],
                    ['id' => 'legend', 'text' => 'Legend'],
                    ['id' => 'epic', 'text' => 'Epic'],
                ],
            ],
            'harga' => [
                'text' => 'Dummy harga: Paket mulai Rp 50.000. Ketik INFO untuk kembali ke menu.',
            ],
            'topup' => [
                'text' => 'Dummy topup: Diamond mulai Rp 10.000. Ketik INFO untuk kembali ke menu.',
            ],
            'mythic' => [
                'text' => 'Dummy joki tier Mythic: silakan hubungi admin untuk detail. Ketik INFO untuk menu.',
            ],
            'legend' => [
                'text' => 'Dummy joki tier Legend: silakan hubungi admin untuk detail. Ketik INFO untuk menu.',
            ],
            'epic' => [
                'text' => 'Dummy joki tier Epic: silakan hubungi admin untuk detail. Ketik INFO untuk menu.',
            ],
        ];
    }

    private function sendAutoReply(
        WhatsAppDevice $device,
        string $sender,
        string $message,
        string $type,
        array $options = [],
        array $rawPayload = []
    ): void {
        $log = MessageLog::create([
            'user_id' => $device->user_id,
            'whatsapp_device_id' => $device->id,
            'direction' => MessageLog::DIRECTION_OUTGOING,
            'type' => $type,
            'phone' => $sender,
            'message' => $message,
            'status' => MessageLog::STATUS_PENDING,
            'raw_payload' => array_merge(['auto_reply' => true], $rawPayload),
        ]);

        SendMessageJob::dispatch($log->id, $options);
    }
}
