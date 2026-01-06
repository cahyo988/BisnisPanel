<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50 text-neutral-900">
        <flux:sidebar sticky stashable class="border-e border-slate-200 bg-white px-6 py-4">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="me-3 flex items-center space-x-2 rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-3 shadow-sm" wire:navigate>
                <div class="flex size-10 items-center justify-center rounded-xl bg-slate-900 text-white">
                    <x-app-logo-icon class="size-5" />
                </div>
                <div class="grid text-sm leading-tight">
                    <span class="font-semibold text-slate-900">BPanel</span>
                    <span class="text-xs text-slate-500">{{ __('WhatsApp Ops') }}</span>
                </div>
            </a>

            <nav class="mt-6 space-y-4 text-sm font-medium text-neutral-600">
                <div class="space-y-1">
                    <div class="flex items-center justify-between px-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <span>{{ __('Overview') }}</span>
                        @if (auth()->user()->isAdmin())
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">{{ __('Admin') }}</span>
                        @endif
                    </div>
                    <a href="{{ route('dashboard') }}" @class([
                        'flex items-center gap-3 rounded-2xl px-3 py-2.5 transition-all',
                        'bg-slate-900 text-white shadow-sm' => request()->routeIs('dashboard'),
                        'text-neutral-600 hover:bg-slate-50' => ! request()->routeIs('dashboard'),
                    ]) wire:navigate>
                        <span class="inline-block size-2 rounded-full @if(request()->routeIs('dashboard')) bg-amber-400 @else bg-slate-300 @endif"></span>
                        {{ __('Dashboard') }}
                    </a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" @class([
                            'flex items-center gap-3 rounded-2xl px-3 py-2.5 transition-all',
                            'bg-slate-900 text-white shadow-sm' => request()->routeIs('admin.dashboard'),
                            'text-neutral-600 hover:bg-slate-50' => ! request()->routeIs('admin.dashboard'),
                        ]) wire:navigate>
                            <span class="inline-block size-2 rounded-full @if(request()->routeIs('admin.dashboard')) bg-amber-400 @else bg-slate-300 @endif"></span>
                            {{ __('Admin Dashboard') }}
                        </a>
                    @endif
                </div>

                <div class="space-y-1">
                    <p class="px-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Operations') }}</p>
                    @foreach ([
                        ['route' => 'devices.index', 'label' => __('Devices')],
                        ['route' => 'messaging.index', 'label' => __('Messaging')],
                        ['route' => 'automation.index', 'label' => __('Automation')],
                        ['route' => 'logs.index', 'label' => __('Logs')],
                    ] as $item)
                        <a href="{{ route($item['route']) }}" @class([
                            'flex items-center gap-3 rounded-2xl px-3 py-2.5 transition-all',
                            'bg-slate-100 text-slate-900' => request()->routeIs($item['route']),
                            'text-neutral-600 hover:bg-slate-50' => ! request()->routeIs($item['route']),
                        ]) wire:navigate>
                            <span class="inline-block size-2 rounded-full @if(request()->routeIs($item['route'])) bg-slate-700 @else bg-slate-300 @endif"></span>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </nav>

            <div class="mt-8 space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('Notifications') }}</p>
                <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                    <livewire:notification-dropdown />
                </div>
            </div>

            <flux:spacer />

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm font-semibold text-neutral-900">{{ __('Need help?') }}</p>
                <p class="mt-1 text-xs text-neutral-500">{{ __('Review the guide and support checklist on the homepage.') }}</p>
                <a href="{{ route('home') }}#support" class="mt-3 inline-flex items-center text-sm font-semibold text-slate-700 hover:underline">
                    {{ __('Open support guide') }}
                </a>
            </div>

            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block mt-6" position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                    data-test="sidebar-menu-button"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden bg-white border-b border-slate-200">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <div class="ms-3">
                <livewire:notification-dropdown />
            </div>

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
