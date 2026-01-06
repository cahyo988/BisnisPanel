<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Welcome back')" :description="__('Use your work email to access your BPanel workspace.')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 text-xs text-slate-600">
            <p class="font-semibold text-slate-700">{{ __('Secure login') }}</p>
            <p class="mt-1">{{ __('Two-factor and device audit are available in settings.') }}</p>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div>
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

                @if (Route::has('password.request'))
                    <flux:link class="text-sm text-slate-600" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

        @if (Route::has('register'))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-slate-600">
                <span>{{ __('Don\'t have an account?') }}</span>
                <flux:link :href="route('register')" wire:navigate>{{ __('Create one') }}</flux:link>
            </div>
        @endif
    </div>
</x-layouts.auth>
