# BisnisPanel

BisnisPanel is a Laravel + Livewire control center for managing multi-tenant WhatsApp automation. Each authenticated user (or admin) can provision Baileys devices, send one-off or bulk messages, define keyword-based auto replies, review delivery logs, and monitor notifications without leaving the browser.

## Getting started

```bash
composer run setup    # install PHP dependencies, migrate, build assets
composer run dev      # serve the app, queue worker, and Vite
```

Seed data ships with:

- `admin@bisnis.test` / `password` (admin)
- `owner@bisnis.test` / `password` (regular business account)

## WhatsApp gateway configuration

The panel speaks to your NodeJS + Baileys worker via HTTP and webhooks. Configure the gateway credentials and webhook secret in `.env`:

```
WHATSAPP_GATEWAY_URL=https://wa-gateway.internal
WHATSAPP_GATEWAY_TOKEN=your-http-api-token
WHATSAPP_WEBHOOK_TOKEN=shared-webhook-secret
```

Expose these webhook endpoints to Baileys:

| Purpose                 | Method | Endpoint                                |
| ----------------------- | ------ | --------------------------------------- |
| Incoming messages       | `POST` | `/api/webhooks/baileys/messages`        |
| Device status updates   | `POST` | `/api/webhooks/baileys/devices/status`  |
| Delivery status updates | `POST` | `/api/webhooks/baileys/messages/status` |

Each request must include the `X-Webhook-Token` header that matches `WHATSAPP_WEBHOOK_TOKEN`.

## Core features

- **Dashboard:** Live statistics (devices, sent/failed/incoming counts, auto-reply sessions, top menu options) plus quick-send and broadcast cards.
- **Device management:** Create/delete WhatsApp devices, inject Baileys sessions, and display QR codes for pairing. Admins may scope devices by tenant.
- **Messaging:** Single message form with media upload/URL support, automated logging, schedule sends, and job-dispatch to the Node gateway.
- **Broadcasts:** Upload CSV/XLSX recipient lists (up to 500 numbers per batch), schedule sends, tune the delay, monitor progress, and review recent attempts.
- **Message templates:** Create reusable templates, apply them to single sends or broadcasts, and manage per-tenant ownership.
- **Auto replies:**
    - **Session-aware menu flow** — Greeting + root menu sent only once per session. Configurable timeout per device (default 30 min).
    - **Multi-level menus** — Root menu → sub-menus with interactive WhatsApp buttons/lists → leaf responses with automatic "↩ Kembali ke Menu" back button.
    - **Fallback message** — Unknown input replies "Maaf, saya tidak mengerti…" and re-shows the current menu.
    - **Keyword rules** — Per-device, keyword-based rules with exact/contains matching and template/text responses.
    - **Instant delivery** — Auto-replies use `dispatchSync` to bypass queue delay.
    - **WhatsApp preview** — Menu editor includes a live preview panel showing how menus render in WhatsApp.
- **Logs & notifications:** Filterable message ledger with delivery timestamps plus a notification dropdown that captures system/device/message/broadcast events.
- **Webhooks & API:** Incoming messages, delivery receipts, and device events persist to `message_logs`, `panel_notifications`, and trigger auto replies as needed, ready for a Baileys worker to consume.

All business entities include a `user_id` and obey role-based access. Regular users only see their own data, while admins can switch between tenants directly from the UI filters.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for recent updates, migration steps, and next steps.
