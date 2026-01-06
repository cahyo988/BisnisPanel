<x-layouts.app :title="__('Devices')">
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <livewire:device-list />
        </div>
        <div class="space-y-6">
            <livewire:device-create />
            <livewire:device-qr-connect />
        </div>
    </div>
</x-layouts.app>
