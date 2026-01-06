<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <div class="relative overflow-hidden hero-surface">
            <div class="pointer-events-none absolute inset-0 opacity-40 grid-dots"></div>

            <header class="relative z-10 mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6 lg:px-10">
                <a href="{{ route('home') }}" class="flex items-center gap-3" wire:navigate>
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white shadow-sm">
                        <x-app-logo-icon class="size-6 fill-current text-slate-900" />
                    </span>
                    <span class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-600">BPanel</span>
                </a>

                <nav class="hidden items-center gap-6 text-sm font-medium text-slate-600 lg:flex">
                    <a class="transition hover:text-slate-900" href="#features">Fitur</a>
                    <a class="transition hover:text-slate-900" href="#workflow">Workflow</a>
                    <a class="transition hover:text-slate-900" href="#insight">Insight</a>
                    <a class="transition hover:text-slate-900" href="#support">Support</a>
                </nav>

                @if (Route::has('login'))
                    <div class="flex items-center gap-3 text-sm">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-full bg-slate-900 px-4 py-2 font-semibold text-white" wire:navigate>
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-full border border-slate-200 bg-white px-4 py-2 font-semibold text-slate-700" wire:navigate>
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-full bg-amber-500 px-4 py-2 font-semibold text-white shadow-sm" wire:navigate>
                                    Mulai sekarang
                                </a>
                            @endif
                        @endauth
                    </div>
                @endif
            </header>

            <main class="relative z-10 mx-auto grid w-full max-w-6xl gap-12 px-6 pb-20 pt-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center lg:px-10">
                <div class="space-y-8">
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/80 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 shadow-sm">
                        Control center for WhatsApp operations
                    </div>
                    <h1 class="text-4xl font-semibold leading-tight text-slate-900 md:text-5xl">
                        Jalankan otomatisasi WhatsApp tanpa kehilangan kendali.
                    </h1>
                    <p class="max-w-xl text-base leading-relaxed text-slate-600 md:text-lg">
                        BPanel menghadirkan dashboard multi-tenant yang stabil untuk device, messaging, broadcast, dan
                        auto reply. Dirancang untuk tim yang membutuhkan kecepatan, visibilitas, dan kontrol penuh.
                    </p>

                    <div class="flex flex-wrap gap-4">
                        <div class="rounded-2xl bg-white/80 p-4 shadow-sm">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Queues</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">Resilient</p>
                            <p class="text-xs text-slate-500">Retries and delivery logs.</p>
                        </div>
                        <div class="rounded-2xl bg-white/80 p-4 shadow-sm">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Devices</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">Live QR</p>
                            <p class="text-xs text-slate-500">Pairing and status sync.</p>
                        </div>
                        <div class="rounded-2xl bg-white/80 p-4 shadow-sm">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Broadcast</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">500+</p>
                            <p class="text-xs text-slate-500">Recipients per batch.</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 sm:flex-row">
                        <a href="{{ route('register') }}" class="rounded-full bg-slate-900 px-6 py-3 text-center text-sm font-semibold text-white shadow-sm" wire:navigate>
                            Buat workspace
                        </a>
                        <a href="#workflow" class="rounded-full border border-slate-200 bg-white px-6 py-3 text-center text-sm font-semibold text-slate-700">
                            Lihat alur kerja
                        </a>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="hero-glow rounded-3xl border border-white/60 bg-white/80 p-6 shadow-xl">
                        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                            <span>Realtime overview</span>
                            <span class="rounded-full bg-emerald-500/15 px-2 py-1 text-emerald-600">Live</span>
                        </div>
                        <div class="mt-6 grid gap-4">
                            <div class="flex items-center justify-between rounded-2xl bg-slate-900 px-4 py-3 text-white">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.2em] text-white/60">Active devices</p>
                                    <p class="mt-2 text-2xl font-semibold">12</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-white/60">Delivered</p>
                                    <p class="mt-1 text-lg font-semibold">98.2%</p>
                                </div>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Queue</p>
                                    <p class="mt-2 text-xl font-semibold text-slate-900">Ready</p>
                                    <p class="text-xs text-slate-500">0 failed jobs.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Broadcast</p>
                                    <p class="mt-2 text-xl font-semibold text-slate-900">Running</p>
                                    <p class="text-xs text-slate-500">4 campaigns today.</p>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Automation</p>
                                <p class="mt-2 text-xl font-semibold text-slate-900">Rules armed</p>
                                <p class="text-xs text-slate-500">Exact + contains matcher.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl bg-white/80 p-4 shadow-sm">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Multi-tenant</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900">Scoped access</p>
                            <p class="text-xs text-slate-500">Admin can switch tenants in UI.</p>
                        </div>
                        <div class="rounded-2xl bg-white/80 p-4 shadow-sm">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Security</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900">2FA ready</p>
                            <p class="text-xs text-slate-500">Fortify + email verification.</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <section id="features" class="mx-auto w-full max-w-6xl px-6 py-16 lg:px-10">
            <div class="grid gap-8 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                <div class="space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">Fitur inti</p>
                    <h2 class="text-3xl font-semibold text-slate-900">Semua modul operasional dalam satu panel.</h2>
                    <p class="text-base text-slate-600">
                        Tidak perlu berpindah aplikasi untuk device provisioning, campaign, dan monitoring. Semua terhubung
                        dengan webhook dan queue bawaan.
                    </p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">Device manager</p>
                        <p class="mt-2 text-sm text-slate-600">Pair QR, cek status, dan atur tenant dari satu tempat.</p>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">Messaging control</p>
                        <p class="mt-2 text-sm text-slate-600">Kirim pesan satuan, media, dan template dengan audit trail.</p>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">Broadcast studio</p>
                        <p class="mt-2 text-sm text-slate-600">CSV/XLSX, delay, dan monitoring progress real time.</p>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">Automation rules</p>
                        <p class="mt-2 text-sm text-slate-600">Auto reply berbasis keyword dengan mode exact/contains.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="workflow" class="bg-slate-900 py-16 text-white">
            <div class="mx-auto w-full max-w-6xl px-6 lg:px-10">
                <div class="grid gap-10 lg:grid-cols-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-white/60">Workflow</p>
                        <h2 class="mt-4 text-3xl font-semibold">Rangkaian kerja yang terstruktur.</h2>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                        <p class="text-sm font-semibold">1. Connect device</p>
                        <p class="mt-2 text-sm text-white/70">Scan QR dan cek status device secara real time.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                        <p class="text-sm font-semibold">2. Configure rules</p>
                        <p class="mt-2 text-sm text-white/70">Atur auto reply, template, dan pengiriman terjadwal.</p>
                    </div>
                </div>
                <div class="mt-6 grid gap-6 lg:grid-cols-3">
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                        <p class="text-sm font-semibold">3. Launch campaign</p>
                        <p class="mt-2 text-sm text-white/70">Broadcast ke ratusan nomor dengan throttling aman.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                        <p class="text-sm font-semibold">4. Monitor logs</p>
                        <p class="mt-2 text-sm text-white/70">Filter log per device, status, dan tenant.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                        <p class="text-sm font-semibold">5. Iterate</p>
                        <p class="mt-2 text-sm text-white/70">Optimasi template dan aturan berdasarkan insight.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="insight" class="mx-auto w-full max-w-6xl px-6 py-16 lg:px-10">
            <div class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">Insight</p>
                    <h3 class="mt-3 text-2xl font-semibold text-slate-900">Dashboard yang berbicara jelas.</h3>
                    <p class="mt-3 text-sm text-slate-600">
                        Statistik device, message log, dan notifikasi ditampilkan secara real time agar keputusan lebih cepat.
                    </p>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl bg-slate-900 p-4 text-white">
                            <p class="text-xs uppercase tracking-[0.2em] text-white/60">Delivery</p>
                            <p class="mt-2 text-xl font-semibold">+18%</p>
                            <p class="text-xs text-white/60">Dibanding minggu lalu.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Automation</p>
                            <p class="mt-2 text-xl font-semibold text-slate-900">32 rules</p>
                            <p class="text-xs text-slate-500">Aktif di 6 device.</p>
                        </div>
                    </div>
                </div>
                <div class="space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">Kenapa BPanel</p>
                    <h3 class="text-3xl font-semibold text-slate-900">Dibangun untuk tim operasional yang serius.</h3>
                    <p class="text-base text-slate-600">
                        Struktur data sudah multi-tenant, pipeline queue siap produksi, dan modul keamanan Fortify siap dipakai.
                    </p>
                    <ul class="space-y-3 text-sm text-slate-600">
                        <li class="flex items-start gap-2">
                            <span class="mt-1 size-2 rounded-full bg-amber-500"></span>
                            Role-based access untuk admin dan owner.
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1 size-2 rounded-full bg-slate-900"></span>
                            Log pesan tersimpan untuk audit dan troubleshooting.
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1 size-2 rounded-full bg-emerald-500"></span>
                            Webhook listener siap terhubung dengan gateway Baileys.
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="support" class="bg-slate-900 py-16 text-white">
            <div class="mx-auto w-full max-w-6xl px-6 lg:px-10">
                <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
                    <div>
                        <h3 class="text-3xl font-semibold">Siap di-launch untuk tim Anda.</h3>
                        <p class="mt-3 text-sm text-white/70">
                            Jalankan `composer run setup` untuk instalasi pertama, lalu `composer run dev` untuk server lokal.
                        </p>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <a href="{{ route('register') }}" class="rounded-full bg-amber-500 px-6 py-3 text-center text-sm font-semibold text-white">
                            Mulai uji coba
                        </a>
                        <a href="{{ route('login') }}" class="rounded-full border border-white/20 px-6 py-3 text-center text-sm font-semibold text-white">
                            Masuk ke panel
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </body>
</html>
