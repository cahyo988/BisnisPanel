<x-layouts.app :title="__('Automation')">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-neutral-900">{{ __('Automation') }}</h2>
            <p class="text-sm text-neutral-500">{{ __('Kelola auto reply dan unduh panduan penggunaan.') }}</p>
        </div>
        <a
            href="{{ route('automation.docs') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-neutral-700 hover:bg-slate-50"
        >
            <span>{{ __('Download Panduan') }}</span>
        </a>
    </div>

    <div class="mt-6">
        <livewire:auto-reply-manager />
    </div>
</x-layouts.app>
