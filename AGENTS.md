# Repository Guidelines

This file is for agentic coding tools operating in this repo. Follow these rules
before making changes. Keep edits consistent with the existing Laravel + Livewire
codebase and avoid surprises in generated output.

## Project Structure & Module Organization
- Framework: Laravel 12 + Livewire/Volt + Vite/Tailwind.
- `app/` contains domain logic (services, jobs, policies, middleware, Livewire).
- Livewire classes live in `app/Livewire`; views in `resources/views/livewire`.
- Volt routes and views are in `resources/views/livewire/settings/*`.
- Routes: browser flows in `routes/web.php`, JSON endpoints in `routes/api.php`.
- Blade layout components in `resources/views/components/layouts`.
- Asset entrypoints in `resources/js/app.js` and `resources/css/app.css`.
- Database: migrations, factories, seeders in `database/` (sqlite supported).
- Public assets in `public/` are build outputs; do not commit them.
- Gateway-related configs live under `config/services.php` (`services.whatsapp`).
- Background jobs are in `app/Jobs`; queue listener runs via `composer run dev`.

## Build, Lint, and Test Commands
Use Composer scripts and standard tooling. Prefer project scripts to raw commands.

Setup and local dev
- `composer run setup`: installs PHP/NPM deps, copies `.env`, generates key,
  migrates, and builds assets.
- `composer run dev`: starts PHP server, queue listener, and Vite dev server.

Build and assets
- `npm run dev`: Vite dev server only.
- `npm run build`: Vite build for production assets.

Tests
- `composer run test`: clears config cache then runs `php artisan test`.
- `./vendor/bin/pest`: run all Pest tests directly.
- `./vendor/bin/pest --filter=Checkout`: run a single test by name.
- `php artisan test --filter=Checkout`: alternative single-test runner.
- `./vendor/bin/pest tests/Feature/SomeTest.php`: run one file.

Lint/format
- `./vendor/bin/pint`: format PHP to PSR-12 (preferred over manual changes).
- No ESLint/Prettier config detected; keep JS/Blade formatting consistent with
  existing files.

## Coding Style and Conventions
Follow existing patterns in `app/` and `resources/`.

### PHP (PSR-12 + Laravel conventions)
- 4-space indentation, `<?php` opening tag, blank line after namespace.
- One class per file; order: namespace, imports, class, methods.
- Use typed properties and return types where possible.
- Use short closures: `fn (Builder $builder) => ...` with a space after `fn`.
- Prefer `private`/`protected` visibility and `readonly` where appropriate.
- Use `config()` for settings and avoid hardcoding env values.
- Throw exceptions on unexpected failures; log before rethrow when needed.
- Favor early returns for guard clauses.
- Use `__()` for user-facing strings in Livewire/Controllers.

### Imports and Namespacing
- Group `use` statements by vendor; keep them alphabetized when practical.
- Prefer explicit imports over fully-qualified references inside methods.
- Avoid unused imports; run Pint if you add/remove classes.
- Keep `use` blocks tight; remove unused aliases on refactors.

### Models and Eloquent
- Use `$fillable` and explicit casts via `casts()` when fields are arrays/dates.
- Use constants for statuses/types (see `app/Models/MessageLog.php`).
- For relationships, return typed relation classes (`HasMany`, `BelongsTo`).
- Use query scopes or helper methods for repeated filters (see `HasUserScope`).
- Prefer `latest()` or explicit `orderBy()` over default ordering.
- Use `with()` to avoid N+1 queries in Livewire lists.

### Livewire/Volt Components
- Livewire class names in PascalCase; Blade views in kebab-case.
- Use `render(): View` and return `view('livewire.name', [...])`.
- Use `dispatchBrowserEvent` for browser notifications, not `session()->flash`.
- Keep validation rules in `rules()`; use `Rule::in` for enum-like values.
- Keep component state typed (`public ?int $id = null` etc.).
- Keep form reset logic in a dedicated method (for example `resetForm()`).
- Use `authorize()` for resource-level access checks.

### Controllers and Middleware
- Validate input with `$request->validate()` and use `Rule::in` for enum fields.
- Return JSON responses from API controllers; use `response()->json()`.
- Use middleware for auth/authorization and security checks.
- Middleware should short-circuit with `abort()` on invalid access.

### Jobs and Services
- Keep services thin and focused; use dependency injection for collaborators.
- Log failures with context (log id, message), then rethrow.
- Avoid side effects in constructors; perform actions in methods.
- Jobs should accept primitive IDs (not model instances) when queued.
- Prefer `dispatch()` from controllers/services for background work.

### Blade, CSS, and JS
- Blade templates use kebab-case filenames; keep Livewire directives consistent.
- Use Tailwind utility classes; avoid introducing custom CSS unless needed.
- JS is minimal; follow existing ES module style in `resources/js`.
- Respect `.editorconfig`: LF line endings, trim trailing whitespace.
- Keep Blade control structures aligned (`@if`, `@foreach`) with consistent indent.
- Use `@class` for conditional Tailwind classes when helpful.
- Avoid inline scripts unless Livewire requires it.

## Types, Data, and Error Handling
- Validate all external input (webhooks, forms, jobs).
- Prefer nullable types where fields can be missing, and handle blank strings.
- For webhook auth, compare tokens using `hash_equals`.
- When a remote service is unavailable, return a mock-like payload or rethrow
  after logging (see `app/Services/WhatsAppGateway.php`).
- Do not swallow exceptions unless the caller expects it; bubble up with context.
- Use `blank()` or `filled()` helpers when checking optional strings.
- Store raw payloads as arrays and cast them (`raw_payload` uses `array`).
- Use translation helpers for UI-facing messages.

## Testing Guidelines
- Pest is the default test runner; keep tests under `tests/Feature` or
  `tests/Unit`.
- Use `RefreshDatabase` for isolation and factories for data setup.
- Name tests with fluent descriptions: `it_validates_customer_totals`.
- Prefer feature tests for user flows and API endpoints.
- Keep API tests asserting JSON shapes and status codes.
- Favor `actingAs()` and policy tests for authorization coverage.

## Commit & Pull Request Notes
- Follow Conventional Commits (`feat:`, `fix:`, `chore:`).
- Keep subject lines under 72 chars; add body when context is non-obvious.
- For UI changes, include screenshots in the PR description.
- CI expects `composer run test` and `npm run build` to pass.

## Security & Configuration
- Never commit `.env`, database dumps, or `storage/` artifacts.
- Document new env vars in `.env.example` if you add config keys.
- Queue listener must be running for background jobs to execute.
- Use `hash_equals()` for token comparisons in webhook middleware.
- Keep webhook endpoints in `routes/api.php` and guard with middleware.
- Prefer `config('services.whatsapp.*')` for gateway settings.

## Agent-Specific Notes
- No Cursor rules detected in `.cursor/rules/` or `.cursorrules`.
- No GitHub Copilot instructions detected in `.github/copilot-instructions.md`.
