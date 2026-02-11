<x-layouts.app :title="__('Messaging')">
    <div class="space-y-6">
        <div class="grid gap-6 lg:grid-cols-2">
            <livewire:send-message-form />
            <livewire:broadcast-page />
        </div>

        <livewire:message-template-manager />
    </div>
</x-layouts.app>
