<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\WhatsAppDevice;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class DeviceCreate extends Component
{
    use AuthorizesRequests;

    public string $name = '';
    public string $phone_number = '';
    public ?string $session = null;
    public ?int $selectedUserId = null;

    public function mount(): void
    {
        $this->selectedUserId = auth()->id();
    }

    public function render(): View
    {
        return view('livewire.device-create', [
            'users' => auth()->user()->isAdmin()
                ? User::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules());

        $user = auth()->user();

        $targetUserId = $user->isAdmin()
            ? ($validated['selectedUserId'] ?? $this->selectedUserId)
            : $user->id;

        $sessionPayload = $this->parseSession($validated['session'] ?? null);

        $device = WhatsAppDevice::create([
            'user_id' => $targetUserId,
            'name' => $validated['name'],
            'phone_number' => $this->normalizePhone($validated['phone_number']),
            'status' => 'disconnected',
            'session' => $sessionPayload,
        ]);

        $this->dispatch('device-created', deviceId: $device->id);

        $this->reset(['name', 'phone_number', 'session']);

        if ($user->isAdmin()) {
            $this->selectedUserId = null;
        }

        session()->flash('device_created', 'Device created successfully.');
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone_number' => [
                'required',
                'string',
                'max:32',
                'regex:/^\+?[0-9]{6,15}$/',
                Rule::unique('whatsapp_devices', 'phone_number'),
            ],
            'session' => ['nullable', 'string'],
            'selectedUserId' => auth()->user()->isAdmin()
                ? ['required', 'integer', 'exists:users,id']
                : ['nullable'],
        ];
    }

    private function parseSession(?string $payload): ?array
    {
        if (blank($payload)) {
            return null;
        }

        $decoded = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                'session' => 'Session must be a valid JSON payload.',
            ]);
        }

        return $decoded;
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9\+]/', '', $phone);
    }
}
