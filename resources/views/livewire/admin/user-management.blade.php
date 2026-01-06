<div class="panel-card space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="panel-section-title">{{ __('User management') }}</p>
            <p class="panel-section-subtitle">{{ __('Promote admins and manage access across tenants') }}</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <input
                type="text"
                class="panel-input sm:w-64"
                placeholder="{{ __('Search name or email') }}"
                wire:model.live.debounce.300ms="search"
            />
            <select class="panel-select sm:w-48" wire:model.live="role">
                <option value="">{{ __('All roles') }}</option>
                <option value="{{ \App\Models\User::ROLE_ADMIN }}">{{ __('Admin') }}</option>
                <option value="{{ \App\Models\User::ROLE_USER }}">{{ __('User') }}</option>
            </select>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Email') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Role') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Joined') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($users as $user)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-900">{{ $user->name }}</div>
                            @if ($user->id === auth()->id())
                                <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                                    {{ __('You') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <select
                                class="panel-select w-36"
                                @disabled($user->id === auth()->id())
                                wire:change="updateRole({{ $user->id }}, $event.target.value)"
                            >
                                <option value="{{ \App\Models\User::ROLE_ADMIN }}" @selected($user->role === \App\Models\User::ROLE_ADMIN)>
                                    {{ __('Admin') }}
                                </option>
                                <option value="{{ \App\Models\User::ROLE_USER }}" @selected($user->role === \App\Models\User::ROLE_USER)>
                                    {{ __('User') }}
                                </option>
                            </select>
                        </td>
                        <td class="px-4 py-3 text-slate-500">
                            {{ $user->created_at?->format('d M Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-center text-slate-500" colspan="4">
                            {{ __('No users found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $users->links() }}
    </div>
</div>
