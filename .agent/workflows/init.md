---
description: Initialize and explore the BisnisPanel project, understanding the full structure and conventions
---

# BisnisPanel – Init Workflow

This workflow helps onboard you (the agent) to the BisnisPanel project so you can work effectively.

## 1. Read the project guidelines

- View `AGENTS.md` at the repo root for coding conventions, folder layout, and commands.

## 2. Understand the tech stack

- **Backend**: Laravel 12, PHP 8.2+, Livewire/Volt, Laravel Fortify (auth + 2FA).
- **Frontend**: Blade, Tailwind CSS v4, Vite 7, Flux UI (Livewire component library), SweetAlert2.
- **Database**: SQLite (default), queue/cache/session all via `database` driver.
- **WhatsApp Gateway**: Node.js Baileys gateway in `wa-gateway/`, talks to Laravel via webhooks & HTTP service.
- **Testing**: Pest 4 + pest-plugin-laravel. Tests in `tests/Feature` and `tests/Unit`.
- **Linting**: Laravel Pint (PSR-12).

## 3. Key directories

| Directory                             | Purpose                                                                                                          |
| ------------------------------------- | ---------------------------------------------------------------------------------------------------------------- |
| `app/Models/`                         | Eloquent models: `User`, `WhatsAppDevice`, `MessageLog`, `MessageTemplate`, `AutoReplyRule`, `PanelNotification` |
| `app/Livewire/`                       | Livewire components: Dashboard, DeviceList, BroadcastPage, SendMessageForm, AutoReplyManager, etc.               |
| `app/Http/Controllers/Api/`           | `BaileysWebhookController` – receives webhook events from the WA gateway                                         |
| `app/Services/`                       | `WhatsAppGateway` (HTTP client to gateway), `MessageDispatcher`                                                  |
| `app/Jobs/`                           | Background jobs for async message processing                                                                     |
| `app/Policies/`                       | Authorization policies                                                                                           |
| `resources/views/livewire/`           | Blade views for Livewire components                                                                              |
| `resources/views/components/layouts/` | App layout components (header, sidebar, etc.)                                                                    |
| `routes/web.php`                      | Browser routes (dashboard, devices, messaging, automation, logs, settings, admin)                                |
| `routes/api.php`                      | Webhook API endpoints (messages, device status, delivery status)                                                 |
| `wa-gateway/`                         | Standalone Node.js WhatsApp gateway (Baileys)                                                                    |
| `database/migrations/`                | 13 migration files                                                                                               |
| `config/services.php`                 | WhatsApp gateway connection config                                                                               |

## 4. Common commands

// turbo-all

### Setup (first time)

```bash
composer run setup
```

### Run dev server

```bash
composer run dev
```

This starts: PHP dev server + queue listener + Vite dev server concurrently.

### Run tests

```bash
composer run test
```

Or directly:

```bash
./vendor/bin/pest
```

### Lint/format PHP

```bash
./vendor/bin/pint
```

### Build assets for production

```bash
npm run build
```

## 5. Environment

- Copy `.env.example` to `.env` if not present.
- Key env vars for WhatsApp: `WHATSAPP_GATEWAY_URL`, `WHATSAPP_GATEWAY_TOKEN`, `WHATSAPP_WEBHOOK_TOKEN`.
- App name: `BPanel`.

## 6. Testing conventions

- Pest is the test runner. Use `RefreshDatabase` for DB isolation.
- Feature tests in `tests/Feature/`, unit tests in `tests/Unit/`.
- Name tests fluently: `it_validates_customer_totals`.
- Use `actingAs()` for auth'd requests.

## 7. Git conventions

- Conventional Commits: `feat:`, `fix:`, `chore:`.
- Subject lines under 72 chars.
