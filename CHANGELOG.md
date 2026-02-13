# Changelog

All notable changes to BisnisPanel are documented in this file.

---

## [2026-02-13] — Auto-Reply Menu System Overhaul

### ⚡ Major: Session-Aware Auto-Reply Engine

Complete rewrite of the auto-reply system from stateless to session-aware.

#### Added

- **`auto_reply_sessions` table** — Tracks per-sender conversation state (current menu level, greeting status, last interaction timestamp) per device.
- **`AutoReplySession` model** — With `isExpired(minutes)` helper and `touch(menuKey)` for state management.
- **`auto_reply_session_timeout` column** on `whatsapp_devices` — Configurable timeout per device (default 30 minutes). After timeout, greeting + root menu resend.
- **Fallback message** — Unknown input now replies with "Maaf, saya tidak mengerti…" and re-shows the current menu with buttons, instead of silence.
- **Auto "↩ Kembali ke Menu" button** — Leaf node responses (no sub-buttons) automatically get a back-to-menu quick-reply button. No more "Ketik INFO" needed.
- **WhatsApp-style preview panel** — Menu editor now shows a live preview of how root menu, sub-menus, and leaf responses will look in WhatsApp (green bubbles, white buttons).
- **Session timeout input** — Menu editor includes a "Session Timeout (menit)" field.
- **Dashboard analytics** — Two new KPI cards:
    - _Auto-Reply Sessions_ — Unique senders interacting with auto-reply today (with trend).
    - _Top Menu Option_ — Most tapped menu button today.

#### Changed

- **`BaileysWebhookController::runAutoReplies()`** — Completely rewritten with session-first logic:
    1. New/expired session → greeting + root menu (once per session)
    2. "info" or "menu" → reset to root
    3. Menu key match → navigate sub-menu or leaf + back button
    4. Keyword rule match → send rule reply
    5. No match → fallback message with current menu
- **`BaileysWebhookController::sendAutoReply()`** — Now uses `dispatchSync` instead of `dispatch` for instant response (bypasses database queue polling delay).
- **`BaileysWebhookController::sendMenuEntry()`** (renamed from `sendDeviceMenu`) — Simplified signature, auto-appends back button on leaves.
- **Default menu texts** — Removed "Ketik INFO untuk kembali ke menu" from leaf nodes (back button handles this now).
- **`DeviceList.php`** — Added `$editingSessionTimeout` property, loaded/saved with menu.

#### Removed

- **`shouldSendGreeting()`** — Replaced by session expiry check.
- **`handleMenuFlow()`** — Logic merged into session-aware `runAutoReplies()`.
- **`$isMenuSelection` parameter** — No longer needed; session tracks state.

### Files Changed

```
NEW  database/migrations/2026_02_13_000100_create_auto_reply_sessions_table.php
NEW  database/migrations/2026_02_13_000200_add_session_timeout_to_whatsapp_devices_table.php
NEW  app/Models/AutoReplySession.php
MOD  app/Models/WhatsAppDevice.php
MOD  app/Http/Controllers/Api/BaileysWebhookController.php
MOD  app/Livewire/DeviceList.php
MOD  app/Livewire/DashboardStats.php
MOD  resources/views/livewire/device-list.blade.php
```

### Migration Required

```bash
php artisan migrate
```

### Commit Message

```
feat(auto-reply): session-aware menu flow with fallback, back button, preview & analytics

- Add auto_reply_sessions table for per-sender state tracking
- Rewrite runAutoReplies() with session-first flow (greeting once per session)
- Auto-append "Kembali ke Menu" button on leaf responses
- Send fallback message + re-show menu on unknown input
- Use dispatchSync for instant auto-reply delivery
- Add WhatsApp-style preview panel in menu editor
- Add configurable session timeout per device
- Add Auto-Reply Sessions + Top Menu Option dashboard stats
```

---

## Next Steps

### Immediate (post-deploy)

- [ ] Run `php artisan migrate` on production
- [ ] Restart queue worker (`php artisan queue:restart`)
- [ ] Restart WA Gateway (`node index.js`)
- [ ] Test full flow: first message → greeting → menu → sub-menu → leaf → back → unknown input → fallback

### Short-term Improvements

- [ ] Add `php artisan auto-reply:cleanup` command to prune expired sessions (older than 24h)
- [ ] Add session count badge on device cards (active conversations)
- [ ] Handle image/document incoming with a "Maaf, silakan pilih menu" fallback
- [ ] Add "human handoff" option — forward unresolved conversations to admin WhatsApp

### Medium-term

- [ ] N-level menu depth (currently supports root → sub → leaf, 3 levels)
- [ ] Template variables in menu responses (`{name}`, `{phone}`)
- [ ] A/B testing for menu texts
- [ ] Export conversation analytics to CSV
- [ ] Redis queue for sub-100ms auto-reply latency
