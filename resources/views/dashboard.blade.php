<x-layouts.app :title="__('Dashboard')">
    <div class="space-y-6">
        <livewire:dashboard-stats />

        <div class="grid gap-6 lg:grid-cols-2">
            <livewire:send-message-form />
            <livewire:broadcast-page />
        </div>

        <livewire:log-table :per-page="8" />
    </div>
</x-layouts.app>
