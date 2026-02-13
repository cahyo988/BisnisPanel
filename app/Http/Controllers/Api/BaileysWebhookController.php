<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendMessageJob;
use App\Models\AutoReplySession;
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
        $this->runAutoReplies($device, $incoming, $data['from']);

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

    // ─── Auto-Reply Engine (Session-Aware) ──────────────────────────────

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

    /**
     * Session-aware auto-reply engine.
     *
     * Flow:
     *  1. Get or create session for this sender+device
     *  2. If session is new or expired → send greeting + root menu
     *  3. If input matches a menu key → navigate (send sub-menu or leaf + back button)
     *  4. If input matches "info"/"menu" → reset to root menu
     *  5. If input matches an AutoReplyRule → send rule reply
     *  6. Otherwise → send fallback (re-show current menu)
     */
    private function runAutoReplies(WhatsAppDevice $device, string $incoming, string $sender): void
    {
        $menu = $device->auto_reply_menu ?? [];
        $hasMenu = ! empty($menu) && isset($menu['root']);
        $hasGreeting = filled($device->auto_reply_greeting);

        // If device has no menu and no greeting, only check keyword rules
        if (! $hasMenu && ! $hasGreeting) {
            $this->tryKeywordRules($device, $incoming, $sender);
            return;
        }

        $incomingNormalized = mb_strtolower(trim($incoming));
        $timeoutMinutes = $device->auto_reply_session_timeout ?? 30;

        // Get or create session
        $session = AutoReplySession::query()
            ->where('whatsapp_device_id', $device->id)
            ->where('sender_phone', $sender)
            ->first();

        // ── New or expired session: send greeting + root menu ──
        if (! $session || $session->isExpired($timeoutMinutes)) {
            if ($session) {
                $session->update([
                    'greeted' => true,
                    'current_menu_key' => 'root',
                    'last_interaction_at' => Carbon::now(),
                ]);
            } else {
                $session = AutoReplySession::create([
                    'whatsapp_device_id' => $device->id,
                    'sender_phone' => $sender,
                    'current_menu_key' => 'root',
                    'greeted' => true,
                    'last_interaction_at' => Carbon::now(),
                ]);
            }

            $this->sendGreetingAndMenu($device, $sender, $menu);
            return;
        }

        // ── Active session: process input ──
        $session->update(['last_interaction_at' => Carbon::now()]);

        if ($incomingNormalized === '') {
            return;
        }

        // "info" or "menu" → reset to root
        if (in_array($incomingNormalized, ['info', 'menu'], true)) {
            $session->update(['current_menu_key' => 'root']);
            if ($hasMenu) {
                $this->sendMenuEntry($device, $sender, 'root', $menu);
            }
            return;
        }

        // Match input against menu keys
        if ($hasMenu && array_key_exists($incomingNormalized, $menu)) {
            $entry = $menu[$incomingNormalized];
            $hasButtons = ! empty($entry['buttons']);

            $session->update([
                'current_menu_key' => $hasButtons ? $incomingNormalized : $session->current_menu_key,
            ]);

            $this->sendMenuEntry($device, $sender, $incomingNormalized, $menu);
            return;
        }

        // Try keyword-based AutoReplyRules
        if ($this->tryKeywordRules($device, $incoming, $sender)) {
            return;
        }

        // ── Fallback: re-show current menu ──
        if ($hasMenu) {
            $this->sendFallback($device, $sender, $session->current_menu_key ?? 'root', $menu);
        }
    }

    /**
     * Try matching against keyword-based AutoReplyRules.
     */
    private function tryKeywordRules(WhatsAppDevice $device, string $incoming, string $sender): bool
    {
        $incomingNormalized = mb_strtolower(trim($incoming));

        if ($incomingNormalized === '') {
            return false;
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
            return false;
        }

        $this->sendAutoReply(
            $device,
            $sender,
            $rule->reply_text,
            MessageLog::TYPE_TEXT,
            [],
            ['auto_reply_rule_id' => $rule->id]
        );

        return true;
    }

    /**
     * Send greeting text + root menu (only on first contact / session reset).
     */
    private function sendGreetingAndMenu(WhatsAppDevice $device, string $sender, array $menu): void
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

        if (! empty($menu) && isset($menu['root'])) {
            $this->sendMenuEntry($device, $sender, 'root', $menu);
        }
    }

    /**
     * Send a menu entry (either a sub-menu with buttons, or a leaf response with back button).
     */
    private function sendMenuEntry(
        WhatsAppDevice $device,
        string $sender,
        string $key,
        array $menu
    ): void {
        $entry = $menu[$key] ?? null;

        if (! is_array($entry) || blank($entry['text'] ?? null)) {
            return;
        }

        $buttons = $entry['buttons'] ?? [];
        $text = (string) $entry['text'];

        if (is_array($buttons) && count($buttons) > 0) {
            // ── Menu node with buttons → send interactive buttons ──
            $buttonCount = count($buttons);
            $messageType = $buttonCount <= 3 ? MessageLog::TYPE_BUTTON : MessageLog::TYPE_LIST;
            $payloadType = $buttonCount <= 3 ? 'button' : 'list';

            $this->sendAutoReply(
                $device,
                $sender,
                $text,
                $messageType,
                ['type' => $payloadType, 'buttons' => $buttons],
                ['auto_reply_menu' => $key, 'buttons' => $buttons]
            );

            return;
        }

        // ── Leaf node → send text + "↩ Kembali ke Menu" button ──
        $this->sendAutoReply(
            $device,
            $sender,
            $text,
            MessageLog::TYPE_BUTTON,
            [
                'type' => 'button',
                'buttons' => [
                    ['id' => 'info', 'text' => '↩ Kembali ke Menu'],
                ],
            ],
            ['auto_reply_menu' => $key]
        );
    }

    /**
     * Send a fallback message when user input is not recognized.
     * Re-shows the current menu level.
     */
    private function sendFallback(
        WhatsAppDevice $device,
        string $sender,
        string $currentMenuKey,
        array $menu
    ): void {
        $entry = $menu[$currentMenuKey] ?? $menu['root'] ?? null;
        $fallbackText = "Maaf, saya tidak mengerti pilihan tersebut.\n\n";

        if (! $entry || blank($entry['text'] ?? null)) {
            $this->sendAutoReply(
                $device,
                $sender,
                $fallbackText.'Ketik *INFO* untuk melihat menu utama.',
                MessageLog::TYPE_BUTTON,
                [
                    'type' => 'button',
                    'buttons' => [['id' => 'info', 'text' => '↩ Menu Utama']],
                ],
                ['auto_reply_fallback' => true]
            );
            return;
        }

        $buttons = $entry['buttons'] ?? [];

        if (is_array($buttons) && count($buttons) > 0) {
            $buttonCount = count($buttons);
            $messageType = $buttonCount <= 3 ? MessageLog::TYPE_BUTTON : MessageLog::TYPE_LIST;
            $payloadType = $buttonCount <= 3 ? 'button' : 'list';

            $this->sendAutoReply(
                $device,
                $sender,
                $fallbackText.$entry['text'],
                $messageType,
                ['type' => $payloadType, 'buttons' => $buttons],
                ['auto_reply_fallback' => true, 'auto_reply_menu' => $currentMenuKey]
            );
        } else {
            $this->sendAutoReply(
                $device,
                $sender,
                $fallbackText.'Ketik *INFO* untuk melihat menu utama.',
                MessageLog::TYPE_BUTTON,
                [
                    'type' => 'button',
                    'buttons' => [['id' => 'info', 'text' => '↩ Menu Utama']],
                ],
                ['auto_reply_fallback' => true]
            );
        }
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
                'text' => 'Dummy harga: Paket mulai Rp 50.000.',
            ],
            'topup' => [
                'text' => 'Dummy topup: Diamond mulai Rp 10.000.',
            ],
            'mythic' => [
                'text' => 'Dummy joki tier Mythic: silakan hubungi admin untuk detail.',
            ],
            'legend' => [
                'text' => 'Dummy joki tier Legend: silakan hubungi admin untuk detail.',
            ],
            'epic' => [
                'text' => 'Dummy joki tier Epic: silakan hubungi admin untuk detail.',
            ],
        ];
    }

    /**
     * Create a message log and dispatch it synchronously for instant delivery.
     */
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

        // Use dispatchSync for auto-replies to bypass queue and respond instantly
        SendMessageJob::dispatchSync($log->id, $options);
    }
}
