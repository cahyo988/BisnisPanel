<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50 text-neutral-900 antialiased">
        <div class="relative min-h-screen overflow-hidden hero-surface">
            <div class="pointer-events-none absolute inset-0 opacity-40 grid-dots"></div>

            <div class="relative z-10 mx-auto flex min-h-screen w-full max-w-6xl flex-col gap-12 px-6 py-10 lg:flex-row lg:items-center lg:px-10">
                <div class="flex-1 space-y-8">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.2em] text-slate-500" wire:navigate>
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/90 shadow-sm">
                            <x-app-logo-icon class="size-7 fill-current text-slate-900" />
                        </span>
                        {{ config('app.name', 'BPanel') }}
                    </a>

                    <div class="space-y-4">
                        <h1 class="text-4xl font-semibold leading-tight text-slate-900 md:text-5xl">
                            Kendalikan operasi WhatsApp bisnis dengan kontrol panel yang rapi dan cepat.
                        </h1>
                        <p class="max-w-xl text-base leading-relaxed text-slate-600 md:text-lg">
                            Kelola device, broadcast, auto reply, dan notifikasi dari satu workspace. Monitor performa
                            tiap tenant tanpa keluar dari dashboard.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-4 text-sm text-slate-600">
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/80 px-4 py-2 shadow-sm">
                            <span class="size-2 rounded-full bg-amber-500"></span>
                            Real-time delivery log
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/80 px-4 py-2 shadow-sm">
                            <span class="size-2 rounded-full bg-slate-900"></span>
                            Multi-tenant device manager
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/80 px-4 py-2 shadow-sm">
                            <span class="size-2 rounded-full bg-emerald-500"></span>
                            Automation studio
                        </span>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-3">
                        <div class="rounded-2xl bg-white/80 p-4 shadow-sm">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Uptime</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">99.9%</p>
                            <p class="text-xs text-slate-500">Queue and webhook resilient.</p>
                        </div>
                        <div class="rounded-2xl bg-white/80 p-4 shadow-sm">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Broadcast</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">500+</p>
                            <p class="text-xs text-slate-500">Recipients per batch.</p>
                        </div>
                        <div class="rounded-2xl bg-white/80 p-4 shadow-sm">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Automation</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">Rule-based</p>
                            <p class="text-xs text-slate-500">Exact or contains triggers.</p>
                        </div>
                    </div>
                </div>

                <div class="w-full max-w-md">
                    <div class="glass-panel hero-glow rounded-3xl p-8 shadow-xl">
                        {{ $slot }}
                    </div>
                    <p class="mt-6 text-center text-xs text-slate-500">
                        Dengan masuk Anda menyetujui kebijakan privasi dan keamanan BPanel.
                    </p>
                </div>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
