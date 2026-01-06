<x-layouts.app :title="__('Admin Dashboard')">
    <div class="space-y-6">
        <div class="panel-card space-y-4">
            <div>
                <p class="panel-section-title">{{ __('Admin Command Center') }}</p>
                <p class="panel-section-subtitle">{{ __('Manage users and monitor system-wide activity') }}</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">{{ __('Access') }}</p>
                    <p class="mt-2 text-sm text-slate-600">
                        {{ __('Admins can view every tenant, device, and broadcast activity.') }}
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">{{ __('Governance') }}</p>
                    <p class="mt-2 text-sm text-slate-600">
                        {{ __('Review roles regularly and keep only essential admins active.') }}
                    </p>
                </div>
            </div>
        </div>

        <livewire:admin.admin-dashboard-stats />
        <livewire:admin.user-management />
    </div>
</x-layouts.app>
