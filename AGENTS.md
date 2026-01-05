# Repository Guidelines

## Project Structure & Module Organization
BisnisPanel follows the default Laravel + Livewire layout. `app/` hosts service classes, form requests, and Livewire/Volt components; keep domain-specific modules in subfolders (e.g., `App/Billing`). Routes live in `routes/` (`web.php` for browser flows, `api.php` for JSON endpoints), while Blade templates and Livewire views stay in `resources/views` with supporting assets under `resources/js` and `resources/css`. Database migrations, factories, and seeders sit in `database/` and populate sqlite or MySQL targets; shared fixtures go inside `database/seeders`. Static entry points and the Vite manifest are emitted to `public/`, so only commit source files, not compiled assets.

## Build, Test, and Development Commands
- `composer run setup` installs PHP/NPM dependencies, copies `.env`, generates the app key, migrates, and builds assets; run once per fresh clone.
- `composer run dev` starts the PHP dev server, queue listener, and `npm run dev` through `npx concurrently`, mirroring production behavior locally.
- `npm run dev` or `npm run build` alone run the Vite/Tailwind pipeline when you only need front-end assets.
- `composer run test` clears config cache and executes the Artisan test suite; most CI jobs call this script verbatim.

## Coding Style & Naming Conventions
Use 4-space indentation and PSR-12 for PHP; format via `./vendor/bin/pint`. Stick to PascalCase for PHP classes, camelCase for methods/properties, snake_case for database columns, and kebab-case Blade filenames (`resources/views/livewire/user-profile.blade.php`). Group Livewire components under `app/Livewire` and pair each class with a Blade view of the same base name. Front-end modules follow ES modules with two-space indentation; keep Vite entrypoints under `resources/js` and name shared utilities `useX.ts`/`useX.js` for clarity.

## Testing Guidelines
Pest is the default framework (see `tests/Feature` and `tests/Unit`). Name tests using fluent descriptions such as `it_validates_customer_totals` to keep reports skimmable. Use factories from `database/factories` for setup, and favor `RefreshDatabase` to isolate state. Run focused suites with `./vendor/bin/pest --filter=Checkout` before pushing, and ensure every new behavior ships with at least one Feature test plus lower-level coverage when logic branches proliferate.

## Commit & Pull Request Guidelines
This archive ships without Git history, so follow Conventional Commits (`feat:`, `fix:`, `chore:`) to keep future changelog tooling trivial; keep subject lines under 72 characters and include concise bodies when context is non-obvious. Each pull request must describe the change, list setup/test steps, link the tracked issue, and attach UI screenshots or API samples whenever the surface shifts. Block merges on passing `composer run test` and `npm run build`, and request at least one review for migrations or security-sensitive updates.

## Security & Configuration Notes
Never commit `.env`, database dumps, or files within `storage/`. Generate secrets with `php artisan key:generate` and rotate queue/broadcast keys per environment. For queues and websockets, ensure `composer run dev` (or the production supervisor) keeps the queue listener active; background jobs will otherwise stall. When touching config, document required env vars in `.env.example` and mention them in the PR body so deployers can update Vault entries promptly.
