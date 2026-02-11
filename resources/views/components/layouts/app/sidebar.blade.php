@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    @php
        $user = auth()->user();
        $operationLinks = [
            ['route' => 'devices.index', 'label' => __('Devices')],
            ['route' => 'messaging.index', 'label' => __('Messaging')],
            ['route' => 'automation.index', 'label' => __('Automation')],
            ['route' => 'logs.index', 'label' => __('Logs')],
        ];
    @endphp
    <body class="antialiased" x-data="{ sidebarCollapsed: false }">
        <div class="dashboard-shell">
            <aside
                class="app-sidebar hidden lg:flex"
                :class="{
                    'app-sidebar--collapsed': sidebarCollapsed
                }"
            >
                <div class="mb-6 flex items-center justify-between lg:justify-start">
                    <a href="{{ route('dashboard') }}" class="app-sidebar__logo" wire:navigate>
                        <div class="flex size-10 items-center justify-center rounded-2xl bg-white/10">
                            <x-app-logo-icon class="size-5 text-white" />
                        </div>
                        <div class="sidebar-collapse-hidden">
                            <p class="text-base font-semibold">BisnisPanel</p>
                            <p class="text-xs text-white/70">{{ __('Enterprise Suite') }}</p>
                        </div>
                    </a>
                </div>

                <div class="sidebar-nav-section">
                    <p class="sidebar-nav-label sidebar-collapse-hidden">{{ __('Overview') }}</p>
                    <nav class="space-y-2">
                        <a href="{{ route('dashboard') }}"
                            @class([
                                'sidebar-nav-link',
                                'sidebar-nav-link--active' => request()->routeIs('dashboard'),
                            ])
                            wire:navigate
                            title="{{ __('Dashboard') }}"
                        >
                            <span class="inline-flex size-2 rounded-full bg-white/70"></span>
                            <span class="sidebar-text">{{ __('Dashboard') }}</span>
                        </a>

                        @if ($user->isAdmin())
                            <a href="{{ route('admin.dashboard') }}"
                                @class([
                                    'sidebar-nav-link',
                                    'sidebar-nav-link--active' => request()->routeIs('admin.dashboard'),
                                ])
                                wire:navigate
                                title="{{ __('Admin Overview') }}"
                            >
                                <span class="inline-flex size-2 rounded-full bg-white/70"></span>
                                <span class="sidebar-text">{{ __('Admin Overview') }}</span>
                            </a>
                        @endif
                    </nav>
                </div>

                <div class="sidebar-nav-section">
                    <p class="sidebar-nav-label sidebar-collapse-hidden">{{ __('Operations') }}</p>
                    <nav class="space-y-2">
                        @foreach ($operationLinks as $item)
                            <a href="{{ route($item['route']) }}"
                                @class([
                                    'sidebar-nav-link',
                                    'sidebar-nav-link--active' => request()->routeIs($item['route']),
                                ])
                                wire:navigate
                                title="{{ $item['label'] }}"
                            >
                                <span class="inline-flex size-2 rounded-full bg-white/70"></span>
                                <span class="sidebar-text">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                </div>

                <div class="mt-auto space-y-4 pt-8 sidebar-collapse-hidden">
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-xs text-white/70">
                        <p class="text-sm font-semibold text-white">{{ __('Need Help?') }}</p>
                        <p>{{ __('Reach the success team at ops@bisnispanel.test') }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full rounded-2xl border border-white/20 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10" data-test="logout-button">
                            {{ __('Log Out') }}
                        </button>
                    </form>
                </div>

                <div class="mt-auto flex w-full items-center justify-center lg:hidden">
                    <button type="button" class="sidebar-toggle text-white/60" @click="sidebarCollapsed = ! sidebarCollapsed">
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </aside>

            <div class="app-content">
                <header class="app-header">
                    <div class="app-header__inner">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex items-center gap-3">
                                <button type="button" class="sidebar-toggle hidden lg:inline-flex" @click="sidebarCollapsed = ! sidebarCollapsed">
                                    <svg x-show="! sidebarCollapsed" x-cloak class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 6l6 6-6 6" />
                                    </svg>
                                    <svg x-show="sidebarCollapsed" x-cloak class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m18 6-6 6 6 6" />
                                    </svg>
                                </button>

                                <div class="header-title">
                                    <p class="header-title__eyebrow">{{ __('Operations Control') }}</p>
                                    <h1 class="header-title__main">{{ $title ?? config('app.name', 'BisnisPanel') }}</h1>
                                </div>
                            </div>

                            <div class="header-actions">
                                <div class="header-action-item">
                                    <livewire:notification-dropdown />
                                </div>

                                <div class="header-action-item">
                                    <flux:dropdown align="end">
                                        <button type="button" class="profile-chip w-full sm:w-auto">
                                            <div class="profile-chip__avatar">{{ $user->initials() }}</div>
                                            <div class="leading-tight text-left hidden sm:block">
                                                <p class="text-sm font-semibold text-[var(--text-primary)] truncate">{{ $user->name }}</p>
                                                <p class="text-xs text-[var(--text-muted)]">{{ $user->email }}</p>
                                            </div>
                                            <svg class="ms-2 size-4 text-[var(--text-muted)]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m6 9 6 6 6-6" />
                                            </svg>
                                        </button>

                                        <flux:menu class="w-80 rounded-2xl border border-slate-200 !bg-white shadow-xl" style="background-color: white !important;">
                                            <div class="flex items-center gap-3 px-3 py-3 !bg-white">
                                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-sm font-semibold text-neutral-600">
                                                    {{ $user->initials() }}
                                                </div>
                                                <div class="grid flex-1 text-start leading-tight">
                                                    <span class="truncate text-sm font-semibold text-neutral-900">{{ $user->name }}</span>
                                                    <span class="truncate text-xs text-neutral-500">{{ $user->email }}</span>
                                                </div>
                                            </div>

                                            <flux:menu.separator class="bg-slate-200" />

                                            <div class="p-1 !bg-white">
                                                <a href="{{ route('profile.edit') }}" wire:navigate class="flex items-center gap-2 w-full rounded-lg px-3 py-2 text-sm font-medium text-neutral-600 hover:bg-slate-50 transition-colors">
                                                    <flux:icon.cog class="size-4" />
                                                    {{ __('Settings') }}
                                                </a>
                                            </div>

                                            <flux:menu.separator class="bg-slate-200" />

                                            <div class="p-1 !bg-white">
                                                <form method="POST" action="{{ route('logout') }}" class="w-full">
                                                    @csrf
                                                    <button type="submit" class="flex items-center gap-2 w-full rounded-lg px-3 py-2 text-sm font-medium text-neutral-600 hover:bg-rose-50 hover:text-rose-600 transition-colors" data-test="logout-button">
                                                        <flux:icon.arrow-right-start-on-rectangle class="size-4" />
                                                        {{ __('Log Out') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-[var(--surface-border)] bg-[var(--surface-base)] px-6 py-3 lg:hidden">
                        <div class="flex items-center gap-3">
                            <div class="flex flex-1 gap-2 overflow-x-auto text-sm text-[var(--text-muted)]">
                                @foreach ($operationLinks as $item)
                                    <a href="{{ route($item['route']) }}"
                                        @class([
                                            'rounded-full px-3 py-1.5 border',
                                            'border-[var(--primary)] text-[var(--primary)]' => request()->routeIs($item['route']),
                                            'border-transparent bg-[var(--surface-muted)]' => ! request()->routeIs($item['route']),
                                        ])
                                        wire:navigate
                                    >
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </div>
                            <livewire:notification-dropdown />
                        </div>
                    </div>
                </header>

                <main class="mx-auto flex w-full max-w-[1440px] flex-1 flex-col px-4 py-8 sm:px-6">
                    {{ $slot }}
                </main>
                <div
                    x-data="{ open: false, type: 'success', message: '' }"
                    x-on:notify.window="
                        type = $event.detail.type ?? 'success';
                        message = $event.detail.message ?? '';
                        open = true;
                        setTimeout(() => open = false, 5000);
                    "
                    x-cloak
                    class="fixed bottom-6 right-6 z-50"
                >
                    <template x-if="open">
                        <div
                            class="rounded-2xl border px-4 py-3 text-sm shadow-lg transition"
                            :class="type === 'error'
                                ? 'border-rose-200 bg-white text-rose-700'
                                : 'border-emerald-200 bg-white text-emerald-700'"
                        >
                            <p x-text="message"></p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        @fluxScripts
        @livewireScripts
        @stack('scripts')
    </body>
</html>
