<x-layouts.app :title="__('Devices')">
    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-sky-50 p-4 md:p-5">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('Device Center') }}</h2>
                    <p class="text-sm text-slate-600">{{ __('Kelola device WhatsApp dan gateway Telegram dari satu tempat.') }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <flux:modal.trigger name="add-whatsapp-device">
                        <flux:button
                            variant="outline"
                            icon="device-phone-mobile"
                            class="!px-3"
                            title="{{ __('Add WhatsApp Device') }}"
                        />
                    </flux:modal.trigger>

                    <flux:modal.trigger name="add-telegram-gateway">
                        <flux:button
                            variant="outline"
                            icon="paper-airplane"
                            class="!px-3"
                            title="{{ __('Add Telegram Gateway') }}"
                        />
                    </flux:modal.trigger>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
                <span class="rounded-full bg-white px-3 py-1 ring-1 ring-slate-200">{{ __('Tip: klik ikon HP untuk tambah device WhatsApp') }}</span>
                <span class="rounded-full bg-white px-3 py-1 ring-1 ring-slate-200">{{ __('Tip: klik ikon pesawat untuk tambah gateway Telegram') }}</span>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
            <livewire:device-list />
            </div>

            <div class="space-y-6">
                <livewire:device-qr-connect />
            </div>
        </div>
    </div>

    <flux:modal name="add-whatsapp-device" class="max-w-2xl w-full">
        <livewire:device-create />
    </flux:modal>

    <flux:modal name="add-telegram-gateway" class="max-w-2xl w-full">
        <livewire:telegram-gateway-manager />
    </flux:modal>
</x-layouts.app>
