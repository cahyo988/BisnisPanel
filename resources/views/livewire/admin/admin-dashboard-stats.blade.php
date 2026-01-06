<div class="panel-card space-y-6">
    <div>
        <p class="panel-section-title">{{ __('Admin overview') }}</p>
        <p class="panel-section-subtitle">{{ __('System-wide visibility for users and messaging') }}</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-sm font-medium text-neutral-500">{{ __('Total Users') }}</p>
            <p class="mt-3 text-3xl font-semibold text-neutral-900">{{ number_format($totalUsers) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-sm font-medium text-neutral-500">{{ __('Admins') }}</p>
            <p class="mt-3 text-3xl font-semibold text-neutral-900">{{ number_format($adminUsers) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-sm font-medium text-neutral-500">{{ __('Business Users') }}</p>
            <p class="mt-3 text-3xl font-semibold text-neutral-900">{{ number_format($regularUsers) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-sm font-medium text-neutral-500">{{ __('Devices') }}</p>
            <p class="mt-3 text-3xl font-semibold text-neutral-900">{{ number_format($deviceCount) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-sm font-medium text-neutral-500">{{ __('Messages Today') }}</p>
            <p class="mt-3 text-3xl font-semibold text-neutral-900">{{ number_format($messagesToday) }}</p>
        </div>
    </div>
</div>
