<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50 text-neutral-900">
        <flux:header container class="border-b border-slate-200 bg-white">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <a href="{{ route('dashboard') }}" class="ms-2 me-5 flex items-center space-x-2 rtl:space-x-reverse lg:ms-0" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
                <flux:navbar.item icon="device-phone-mobile" :href="route('devices.index')" :current="request()->routeIs('devices.index')" wire:navigate>
                    {{ __('Devices') }}
                </flux:navbar.item>
                <flux:navbar.item icon="chat-bubble-left-right" :href="route('messaging.index')" :current="request()->routeIs('messaging.index')" wire:navigate>
                    {{ __('Messaging') }}
                </flux:navbar.item>
                <flux:navbar.item icon="sparkles" :href="route('automation.index')" :current="request()->routeIs('automation.index')" wire:navigate>
                    {{ __('Automation') }}
                </flux:navbar.item>
                <flux:navbar.item icon="table-cells" :href="route('logs.index')" :current="request()->routeIs('logs.index')" wire:navigate>
                    {{ __('Logs') }}
                </flux:navbar.item>
            </flux:navbar>

            <div class="ms-3 hidden lg:flex">
                <livewire:notification-dropdown />
            </div>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:tooltip :content="__('Search')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="magnifying-glass" href="#" :label="__('Search')" />
                </flux:tooltip>
                <flux:tooltip :content="__('Product guide')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="folder-git-2"
                        :href="route('home').'#features'"
                        :label="__('Guide')"
                    />
                </flux:tooltip>
                <flux:tooltip :content="__('Support')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="book-open-text"
                        :href="route('home').'#support'"
                        label="Support"
                    />
                </flux:tooltip>
            </flux:navbar>

            <!-- Desktop User Menu -->
            <flux:dropdown position="top" align="end">
                <flux:profile
                    class="cursor-pointer"
                    :initials="auth()->user()->initials()"
                />

                <flux:menu class="w-96 rounded-2xl border border-slate-200 !bg-white shadow-xl" style="background-color: white !important;">
                    <div class="flex items-center gap-3 px-3 py-3 !bg-white">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-sm font-semibold text-neutral-600">
                            {{ auth()->user()->initials() }}
                        </div>
                        <div class="grid flex-1 text-start leading-tight">
                            <span class="truncate text-sm font-semibold text-neutral-900">{{ auth()->user()->name }}</span>
                            <span class="truncate text-xs text-neutral-500">{{ auth()->user()->email }}</span>
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
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar stashable sticky class="lg:hidden border-e border-slate-200 bg-white">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="ms-1 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Platform')">
                    <flux:navlist.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="device-phone-mobile" :href="route('devices.index')" :current="request()->routeIs('devices.index')" wire:navigate>
                    {{ __('Devices') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="chat-bubble-left-right" :href="route('messaging.index')" :current="request()->routeIs('messaging.index')" wire:navigate>
                    {{ __('Messaging') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="sparkles" :href="route('automation.index')" :current="request()->routeIs('automation.index')" wire:navigate>
                    {{ __('Automation') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="table-cells" :href="route('logs.index')" :current="request()->routeIs('logs.index')" wire:navigate>
                    {{ __('Logs') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="folder-git-2" :href="route('home').'#features'">
                {{ __('Guide') }}
                </flux:navlist.item>

                <flux:navlist.item icon="book-open-text" :href="route('home').'#support'">
                {{ __('Support') }}
                </flux:navlist.item>
            </flux:navlist>
        </flux:sidebar>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
