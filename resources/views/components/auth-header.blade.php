@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-1 text-left">
    <flux:heading size="xl">{{ $title }}</flux:heading>
    <flux:subheading class="text-slate-500">{{ $description }}</flux:subheading>
</div>
