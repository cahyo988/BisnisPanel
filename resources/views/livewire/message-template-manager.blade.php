<div class="panel-card" id="message-templates">
    <h3 class="panel-section-title">{{ __('Message Templates') }}</h3>
    <p class="panel-section-subtitle">{{ __('Save and reuse message bodies across single sends and broadcasts.') }}</p>

    <form class="mt-5 space-y-4" wire:submit.prevent="save">
        <div class="grid gap-4 md:grid-cols-2">
            @if ($userOptions->isNotEmpty())
                <div>
                    <label class="text-sm font-medium text-neutral-700">{{ __('Owner') }}</label>
                    <select wire:model="selectedUserId" class="panel-select mt-1">
                        <option value="">{{ __('Select user…') }}</option>
                        @foreach ($userOptions as $option)
                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                        @endforeach
                    </select>
                    @error('selectedUserId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            @endif
            <div>
                <label class="text-sm font-medium text-neutral-700">{{ __('Template Name') }}</label>
                <input type="text" wire:model.defer="name" class="panel-input mt-1" placeholder="{{ __('Promo follow-up') }}" />
                @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="text-sm font-medium text-neutral-700">{{ __('Message Body') }}</label>
            <textarea wire:model.defer="body" rows="4" class="panel-input mt-1" placeholder="{{ __('Hi {name}, thanks for reaching out…') }}"></textarea>
            @error('body') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-end gap-3">
            @if ($templateId)
                <flux:button type="button" variant="ghost" wire:click="resetForm">{{ __('Cancel') }}</flux:button>
            @endif
            <flux:button type="submit" variant="primary">
                {{ $templateId ? __('Update Template') : __('Create Template') }}
            </flux:button>
        </div>
    </form>

    <div class="mt-8 space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h4 class="text-sm font-semibold text-neutral-700">{{ __('Existing Templates') }}</h4>
            <input type="text" wire:model.live="search" class="panel-input sm:w-64" placeholder="{{ __('Search templates...') }}" />
        </div>

        @if ($templates->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-neutral-500">
                {{ __('No templates saved yet.') }}
            </div>
        @else
            <div class="mt-4 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white">
                @foreach ($templates as $template)
                    <div class="flex flex-col gap-3 px-4 py-4 md:flex-row md:items-center md:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-neutral-900 truncate">{{ $template->name }}</p>
                                <span class="text-xs text-neutral-400">{{ $template->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-2 text-sm text-neutral-700">{{ \Illuminate\Support\Str::limit($template->body, 140) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="table-icon-btn" wire:click="$dispatch('message-template-apply', { templateId: {{ $template->id }}, target: 'single' })" title="{{ __('Use for single') }}" @disabled(blank($template->body))>
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 8.25V6a2.25 2.25 0 0 0-2.25-2.25h-6A2.25 2.25 0 0 0 6 6v12a2.25 2.25 0 0 0 2.25 2.25h6A2.25 2.25 0 0 0 16.5 18v-2.25M15.75 12h6m0 0-3-3m3 3-3 3" />
                                </svg>
                            </button>
                            <button type="button" class="table-icon-btn" wire:click="$dispatch('message-template-apply', { templateId: {{ $template->id }}, target: 'broadcast' })" title="{{ __('Use for broadcast') }}" @disabled(blank($template->body))>
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0 1 12.97-5.303M19.5 12a7.5 7.5 0 0 1-12.97 5.303M8.25 15.75h7.5M9 9h6" />
                                </svg>
                            </button>
                            <button type="button" class="table-icon-btn" wire:click="edit({{ $template->id }})" title="{{ __('Edit template') }}">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931ZM18 14.25V18A2.25 2.25 0 0 1 15.75 20.25H6A2.25 2.25 0 0 1 3.75 18V8.25A2.25 2.25 0 0 1 6 6h3.75" />
                                </svg>
                            </button>
                            <button type="button" class="table-icon-btn" wire:click="delete({{ $template->id }})" title="{{ __('Delete template') }}">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
