<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\WhatsAppDevice;
use App\Services\WhatsAppGateway;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Throwable;

class DeviceList extends Component
{
    use AuthorizesRequests;

    public string $status = 'all';
    public ?int $selectedUserId = null;
    public string $search = '';
    public ?int $editingGreetingDeviceId = null;
    public string $editingGreeting = '';
    public ?int $editingMenuDeviceId = null;
    public array $editingMenuForm = [
        'root_text' => '',
        'root_buttons' => [],
    ];
    public int $editingSessionTimeout = 30;

    protected $listeners = [
        'device-created' => '$refresh',
    ];

    public function mount(): void
    {
        if (! auth()->user()->isAdmin()) {
            $this->selectedUserId = auth()->id();
        }
    }

    public function render(): View
    {
        return view('livewire.device-list', [
            'devices' => $this->devices,
            'userOptions' => auth()->user()->isAdmin()
                ? User::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function editGreeting(int $deviceId): void
    {
        $device = WhatsAppDevice::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($deviceId);

        $this->authorize('update', $device);

        $this->editingGreetingDeviceId = $device->id;
        $this->editingGreeting = $device->auto_reply_greeting ?? '';
    }

    public function saveGreeting(): void
    {
        if (! $this->editingGreetingDeviceId) {
            return;
        }

        $device = WhatsAppDevice::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($this->editingGreetingDeviceId);

        $this->authorize('update', $device);

        $device->update([
            'auto_reply_greeting' => $this->editingGreeting ?: null,
        ]);

        $this->editingGreetingDeviceId = null;
        $this->editingGreeting = '';

        session()->flash('device_removed', __('Auto reply greeting saved.'));
    }

    public function cancelGreeting(): void
    {
        $this->editingGreetingDeviceId = null;
        $this->editingGreeting = '';
    }

    public function editMenu(int $deviceId): void
    {
        $device = WhatsAppDevice::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($deviceId);

        $this->authorize('update', $device);

        $menuPayload = $device->auto_reply_menu ?? $this->defaultMenu();

        $this->editingMenuDeviceId = $device->id;
        $this->editingMenuForm = $this->menuToForm($menuPayload);
        $this->editingSessionTimeout = $device->auto_reply_session_timeout ?? 30;
    }

    public function saveMenu(): void
    {
        if (! $this->editingMenuDeviceId) {
            return;
        }

        $device = WhatsAppDevice::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($this->editingMenuDeviceId);

        $this->authorize('update', $device);

        $menuPayload = $this->formToMenu($this->validateMenuForm($this->editingMenuForm));

        $device->update([
            'auto_reply_menu' => $menuPayload,
            'auto_reply_session_timeout' => max(1, min(1440, $this->editingSessionTimeout)),
        ]);

        $this->editingMenuDeviceId = null;
        $this->editingMenuForm = $this->blankMenuForm();
        $this->editingSessionTimeout = 30;

        session()->flash('device_removed', __('Auto reply menu saved.'));
    }

    public function cancelMenu(): void
    {
        $this->editingMenuDeviceId = null;
        $this->editingMenuForm = $this->blankMenuForm();
        $this->editingSessionTimeout = 30;
    }

    public function loadDefaultMenu(): void
    {
        $this->editingMenuForm = $this->menuToForm($this->defaultMenu());
    }

    public function clearMenu(): void
    {
        $this->editingMenuForm = $this->blankMenuForm();
    }

    public function addRootButton(): void
    {
        $this->editingMenuForm['root_buttons'][] = [
            'label' => '',
            'has_submenu' => false,
            'reply_text' => '',
            'submenu_text' => '',
            'sub_buttons' => [],
        ];
    }

    public function removeRootButton(int $index): void
    {
        unset($this->editingMenuForm['root_buttons'][$index]);
        $this->editingMenuForm['root_buttons'] = array_values($this->editingMenuForm['root_buttons']);
    }

    public function addSubButton(int $rootIndex): void
    {
        $this->editingMenuForm['root_buttons'][$rootIndex]['sub_buttons'][] = [
            'label' => '',
            'reply_text' => '',
        ];
    }

    public function removeSubButton(int $rootIndex, int $subIndex): void
    {
        unset($this->editingMenuForm['root_buttons'][$rootIndex]['sub_buttons'][$subIndex]);
        $this->editingMenuForm['root_buttons'][$rootIndex]['sub_buttons'] = array_values(
            $this->editingMenuForm['root_buttons'][$rootIndex]['sub_buttons']
        );
    }

    public function getDevicesProperty()
    {
        return WhatsAppDevice::query()
            ->withCount('messageLogs')
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->when($this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $builder) {
                    $builder
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('phone_number', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('id')
            ->get();
    }

    public function remove(int $deviceId): void
    {
        $device = WhatsAppDevice::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($deviceId);

        $this->authorize('delete', $device);

        $device->delete();

        session()->flash('device_removed', 'Device removed.');
        $this->dispatch('device-removed');
    }

    public function showQr(WhatsAppGateway $gateway, int $deviceId): void
    {
        // Check if user session is still valid
        if (! auth()->check()) {
            session()->flash('error', __('Your session has expired. Please login again.'));
            $this->redirectRoute('login');
            return;
        }

        $device = WhatsAppDevice::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($deviceId);

        $this->authorize('view', $device);

        $gateway->connectDevice($device->id, $device->phone_number, $device->name, force: false);

        // Dispatch event to show modal
        $this->dispatch('show-qr-modal', deviceId: $device->id);
    }

    public function disconnect(WhatsAppGateway $gateway, int $deviceId): void
    {
        $device = WhatsAppDevice::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($deviceId);

        $this->authorize('update', $device);

        $device->update(['status' => 'disconnected', 'session' => null]);

        try {
            $gateway->disconnectDevice($device->id);
            session()->flash('device_removed', __('Device disconnected. Refreshing status...'));
        } catch (Throwable $exception) {
            report($exception);
            session()->flash('device_removed', __('Gateway unreachable. Device status will update once the connection recovers.'));
        }
    }

    private function applyUserScope(Builder $builder): Builder
    {
        $viewer = auth()->user();

        if ($viewer->isAdmin()) {
            if ($this->selectedUserId) {
                $builder->where('user_id', $this->selectedUserId);
            }

            return $builder;
        }

        return $builder->where('user_id', $viewer->id);
    }

    private function blankMenuForm(): array
    {
        return [
            'root_text' => '',
            'root_buttons' => [],
        ];
    }

    private function validateMenuForm(array $form): array
    {
        $errors = [];

        if (blank($form['root_text'] ?? null)) {
            $errors['editingMenuForm.root_text'] = __('Root message is required.');
        }

        $rootButtons = $form['root_buttons'] ?? [];

        if (empty($rootButtons)) {
            $errors['editingMenuForm.root_buttons'] = __('Add at least one button.');
        }

        foreach ($rootButtons as $index => $button) {
            if (blank($button['label'] ?? null)) {
                $errors["editingMenuForm.root_buttons.{$index}.label"] = __('Button label is required.');
            }

            $hasSubmenu = (bool) ($button['has_submenu'] ?? false);

            if ($hasSubmenu) {
                if (blank($button['submenu_text'] ?? null)) {
                    $errors["editingMenuForm.root_buttons.{$index}.submenu_text"] = __('Submenu message is required.');
                }

                $subButtons = $button['sub_buttons'] ?? [];

                if (empty($subButtons)) {
                    $errors["editingMenuForm.root_buttons.{$index}.sub_buttons"] = __('Add at least one submenu button.');
                }

                foreach ($subButtons as $subIndex => $subButton) {
                    if (blank($subButton['label'] ?? null)) {
                        $errors["editingMenuForm.root_buttons.{$index}.sub_buttons.{$subIndex}.label"] = __('Submenu button label is required.');
                    }

                    if (blank($subButton['reply_text'] ?? null)) {
                        $errors["editingMenuForm.root_buttons.{$index}.sub_buttons.{$subIndex}.reply_text"] = __('Reply text is required.');
                    }
                }
            } else {
                if (blank($button['reply_text'] ?? null)) {
                    $errors["editingMenuForm.root_buttons.{$index}.reply_text"] = __('Reply text is required.');
                }
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $form;
    }

    private function menuToForm(array $menu): array
    {
        $root = $menu['root'] ?? [];
        $rootButtons = [];

        foreach (($root['buttons'] ?? []) as $button) {
            $id = (string) ($button['id'] ?? '');
            $label = (string) ($button['text'] ?? '');
            $entry = $menu[$id] ?? [];
            $submenuButtons = $entry['buttons'] ?? [];

            if (! empty($submenuButtons)) {
                $subButtons = [];

                foreach ($submenuButtons as $subButton) {
                    $subId = (string) ($subButton['id'] ?? '');
                    $subLabel = (string) ($subButton['text'] ?? '');
                    $subEntry = $menu[$subId] ?? [];

                    $subButtons[] = [
                        'label' => $subLabel,
                        'reply_text' => (string) ($subEntry['text'] ?? ''),
                    ];
                }

                $rootButtons[] = [
                    'label' => $label,
                    'has_submenu' => true,
                    'submenu_text' => (string) ($entry['text'] ?? ''),
                    'sub_buttons' => $subButtons,
                    'reply_text' => '',
                ];
            } else {
                $rootButtons[] = [
                    'label' => $label,
                    'has_submenu' => false,
                    'reply_text' => (string) ($entry['text'] ?? ''),
                    'submenu_text' => '',
                    'sub_buttons' => [],
                ];
            }
        }

        return [
            'root_text' => (string) ($root['text'] ?? ''),
            'root_buttons' => $rootButtons,
        ];
    }

    private function formToMenu(array $form): array
    {
        $menu = [];
        $usedIds = [];

        $menu['root'] = [
            'text' => trim((string) ($form['root_text'] ?? '')),
            'buttons' => [],
        ];

        foreach ($form['root_buttons'] as $index => $button) {
            $label = trim((string) ($button['label'] ?? ''));
            $buttonId = $this->makeMenuId($label, 'menu_'.($index + 1), $usedIds);

            $menu['root']['buttons'][] = [
                'id' => $buttonId,
                'text' => $label,
            ];

            if (! empty($button['has_submenu'])) {
                $submenuButtons = [];
                $submenuText = trim((string) ($button['submenu_text'] ?? ''));

                foreach (($button['sub_buttons'] ?? []) as $subIndex => $subButton) {
                    $subLabel = trim((string) ($subButton['label'] ?? ''));
                    $subId = $this->makeMenuId($subLabel, $buttonId.'_'.($subIndex + 1), $usedIds);

                    $submenuButtons[] = [
                        'id' => $subId,
                        'text' => $subLabel,
                    ];

                    $menu[$subId] = [
                        'text' => trim((string) ($subButton['reply_text'] ?? '')),
                    ];
                }

                $menu[$buttonId] = [
                    'text' => $submenuText,
                    'buttons' => $submenuButtons,
                ];
            } else {
                $menu[$buttonId] = [
                    'text' => trim((string) ($button['reply_text'] ?? '')),
                ];
            }
        }

        return $menu;
    }

    private function makeMenuId(string $label, string $fallback, array &$usedIds): string
    {
        $base = Str::slug($label, '_');
        $id = $base !== '' ? $base : $fallback;
        $candidate = $id;
        $suffix = 2;

        while (in_array($candidate, $usedIds, true)) {
            $candidate = $id.'_'.$suffix;
            $suffix++;
        }

        $usedIds[] = $candidate;

        return $candidate;
    }

    private function defaultMenu(): array
    {
        return [
            'root' => [
                'text' => 'Pilih layanan yang kamu butuhkan:',
                'buttons' => [
                    ['id' => 'harga', 'text' => 'Harga'],
                    ['id' => 'joki', 'text' => 'Joki'],
                    ['id' => 'topup', 'text' => 'Topup'],
                ],
            ],
            'joki' => [
                'text' => 'Pilih tier joki yang kamu inginkan:',
                'buttons' => [
                    ['id' => 'mythic', 'text' => 'Mythic'],
                    ['id' => 'legend', 'text' => 'Legend'],
                    ['id' => 'epic', 'text' => 'Epic'],
                ],
            ],
            'harga' => [
                'text' => 'Dummy harga: Paket mulai Rp 50.000.',
            ],
            'topup' => [
                'text' => 'Dummy topup: Diamond mulai Rp 10.000.',
            ],
            'mythic' => [
                'text' => 'Dummy joki tier Mythic: silakan hubungi admin untuk detail.',
            ],
            'legend' => [
                'text' => 'Dummy joki tier Legend: silakan hubungi admin untuk detail.',
            ],
            'epic' => [
                'text' => 'Dummy joki tier Epic: silakan hubungi admin untuk detail.',
            ],
        ];
    }
}
