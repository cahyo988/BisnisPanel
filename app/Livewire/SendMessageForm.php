<?php

namespace App\Livewire;

use App\Jobs\SendMessageJob;
use App\Models\MessageLog;
use App\Models\WhatsAppDevice;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class SendMessageForm extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public ?int $deviceId = null;
    public string $type = MessageLog::TYPE_TEXT;
    public string $phone = '';
    public string $message = '';
    public ?string $mediaUrl = null;
    public $mediaFile;

    public function render(): View
    {
        return view('livewire.send-message-form', [
            'devices' => $this->deviceOptions(),
        ]);
    }

    public function send(): void
    {
        $validated = $this->validate($this->rules());

        if ($validated['type'] === MessageLog::TYPE_IMAGE && blank($validated['mediaUrl']) && ! $this->mediaFile) {
            $this->addError('mediaUrl', 'Provide a media URL or upload a file.');

            return;
        }

        $device = WhatsAppDevice::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($validated['deviceId']);

        $this->authorize('view', $device);

        $rawPayload = [
            'type' => $validated['type'],
            'media_url' => $validated['mediaUrl'],
        ];

        if ($this->mediaFile) {
            $rawPayload['media_path'] = $this->mediaFile->store('whatsapp-media', 'public');
        }

        $log = MessageLog::create([
            'user_id' => $device->user_id,
            'whatsapp_device_id' => $device->id,
            'direction' => MessageLog::DIRECTION_OUTGOING,
            'type' => $validated['type'],
            'phone' => $this->normalizePhone($validated['phone']),
            'message' => $validated['message'] ?? $validated['mediaUrl'] ?? 'Media message',
            'status' => MessageLog::STATUS_PENDING,
            'raw_payload' => $rawPayload,
        ]);

        SendMessageJob::dispatch($log->id);

        $this->reset(['phone', 'message', 'mediaUrl', 'mediaFile']);

        $this->dispatch('message-sent', logId: $log->id);

        session()->flash('message_sent', 'Message queued successfully.');
    }

    protected function rules(): array
    {
        return [
            'deviceId' => ['required', 'integer', 'exists:whatsapp_devices,id'],
            'type' => ['required', Rule::in([
                MessageLog::TYPE_TEXT,
                MessageLog::TYPE_IMAGE,
                MessageLog::TYPE_DOCUMENT,
                MessageLog::TYPE_BUTTON,
            ])],
            'phone' => ['required', 'string', 'max:20'],
            'message' => [
                Rule::requiredIf(fn () => $this->type === MessageLog::TYPE_TEXT),
                'nullable',
                'string',
                'max:1000',
            ],
            'mediaUrl' => ['nullable', 'url'],
            'mediaFile' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    private function deviceOptions()
    {
        return WhatsAppDevice::query()
            ->select(['id', 'name', 'user_id'])
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->orderBy('name')
            ->get();
    }

    private function applyUserScope(Builder $builder): Builder
    {
        $viewer = auth()->user();

        if (! $viewer->isAdmin()) {
            $builder->where('user_id', $viewer->id);
        }

        return $builder;
    }

    private function normalizePhone(string $phone): string
    {
        $normalized = preg_replace('/[^0-9\+]/', '', $phone);

        return Str::startsWith($normalized, '+') ? $normalized : '+'.$normalized;
    }
}
