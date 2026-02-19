<?php

namespace App\Livewire;

use App\Jobs\SendMessageJob;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Models\WhatsAppDevice;
use App\Services\ChannelAccountRegistry;
use App\Services\ConversationRegistry;
use App\Support\ContactKeyNormalizer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
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

    public ?int $templateId = null;

    public ?string $scheduledAt = null;

    public bool $useContactNames = true;

    public function render(): View
    {
        return view('livewire.send-message-form', [
            'devices' => $this->deviceOptions(),
            'templates' => $this->templateOptions(),
            'contactName' => $this->useContactNames
                ? $this->resolveContactName($this->deviceId, $this->phone)
                : null,
        ]);
    }

    public function send(ChannelAccountRegistry $channelAccounts, ConversationRegistry $conversations): void
    {
        $validated = $this->validate($this->rules());

        if ($this->templateId) {
            $this->applyTemplate();
        }

        $scheduledAt = $this->parseSchedule($validated['scheduledAt'] ?? null);

        if ($validated['type'] === MessageLog::TYPE_TEXT && blank($this->message)) {
            $this->addError('message', __('Message body is required.'));

            return;
        }

        if ($validated['type'] === MessageLog::TYPE_IMAGE && blank($validated['mediaUrl']) && ! $this->mediaFile) {
            $this->addError('mediaUrl', 'Provide a media URL or upload a file.');

            return;
        }

        $device = WhatsAppDevice::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($validated['deviceId']);

        $this->authorize('view', $device);

        $contactName = $this->useContactNames
            ? $this->resolveContactName($device->id, $validated['phone'])
            : null;
        $normalizedPhone = $this->normalizePhone($validated['phone']);
        $this->message = $this->renderMessageWithName($this->message, $contactName, $normalizedPhone);
        $channelAccount = $channelAccounts->forWhatsAppDevice($device);

        $rawPayload = [
            'type' => $validated['type'],
            'media_url' => $validated['mediaUrl'],
            'contact_name' => $contactName,
        ];

        if ($this->mediaFile) {
            $rawPayload['media_path'] = $this->mediaFile->store('whatsapp-media', 'public');
        }

        if ($this->templateId) {
            $rawPayload['template_id'] = $this->templateId;
        }

        $log = MessageLog::create([
            'user_id' => $device->user_id,
            'channel' => $channelAccount->channel,
            'channel_account_id' => $channelAccount->id,
            'whatsapp_device_id' => $device->id,
            'direction' => MessageLog::DIRECTION_OUTGOING,
            'type' => $validated['type'],
            'phone' => $normalizedPhone,
            'message' => $this->message ?: $validated['mediaUrl'] ?? 'Media message',
            'status' => $scheduledAt ? MessageLog::STATUS_SCHEDULED : MessageLog::STATUS_PENDING,
            'raw_payload' => $rawPayload,
            'scheduled_at' => $scheduledAt,
        ]);

        $conversations->assign($log, $channelAccount, $normalizedPhone, $contactName);

        if ($scheduledAt && $scheduledAt->isFuture()) {
            SendMessageJob::dispatch($log->id)->delay($scheduledAt);
        } else {
            SendMessageJob::dispatch($log->id);
        }

        $this->reset(['phone', 'message', 'mediaUrl', 'mediaFile', 'templateId', 'scheduledAt']);

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
                Rule::requiredIf(fn () => $this->type === MessageLog::TYPE_TEXT && blank($this->templateId)),
                'nullable',
                'string',
                'max:2000',
            ],
            'mediaUrl' => ['nullable', 'url'],
            'mediaFile' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'templateId' => ['nullable', 'integer', 'exists:message_templates,id'],
            'scheduledAt' => ['nullable', 'date'],
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

    public function applyTemplate(): void
    {
        if (! $this->templateId) {
            return;
        }

        $template = MessageTemplate::query()
            ->tap(fn (Builder $builder) => $this->applyTemplateScope($builder))
            ->findOrFail($this->templateId);

        $this->authorize('view', $template);

        $this->message = $template->body;
    }

    public function updatedTemplateId(?int $templateId): void
    {
        if (! $templateId) {
            return;
        }

        $this->applyTemplate();
    }

    #[On('message-template-applied')]
    public function onTemplateApplied(string $body, ?string $name = null, ?string $target = null): void
    {
        if ($target === 'broadcast') {
            return;
        }

        $this->message = $body;
    }

    private function applyUserScope(Builder $builder): Builder
    {
        $viewer = auth()->user();

        if (! $viewer->isAdmin()) {
            $builder->where('user_id', $viewer->id);
        }

        return $builder;
    }

    private function applyTemplateScope(Builder $builder): Builder
    {
        $viewer = auth()->user();

        if (! $viewer->isAdmin()) {
            $builder->where('user_id', $viewer->id);
        }

        return $builder;
    }

    private function templateOptions()
    {
        return MessageTemplate::query()
            ->select(['id', 'name', 'user_id'])
            ->tap(fn (Builder $builder) => $this->applyTemplateScope($builder))
            ->orderBy('name')
            ->get();
    }

    private function parseSchedule(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->timezone(config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizePhone(string $phone): string
    {
        $normalized = preg_replace('/[^0-9\+]/', '', $phone);

        return Str::startsWith($normalized, '+') ? $normalized : '+'.$normalized;
    }

    private function resolveContactName(?int $deviceId, ?string $phone): ?string
    {
        if (! $deviceId || blank($phone)) {
            return null;
        }

        $normalized = $this->normalizePhone($phone);
        $canonical = ContactKeyNormalizer::normalizeWhatsApp($normalized);

        $log = MessageLog::query()
            ->where('direction', MessageLog::DIRECTION_INCOMING)
            ->where('whatsapp_device_id', $deviceId)
            ->where(function (Builder $builder) use ($normalized, $canonical): void {
                $builder->where('phone', $normalized);

                if (filled($canonical)) {
                    $builder->orWhere('phone', $canonical);
                }
            })
            ->latest()
            ->first();

        $payload = $log?->raw_payload;

        return is_array($payload) && filled($payload['push_name'] ?? null)
            ? (string) $payload['push_name']
            : null;
    }

    private function renderMessageWithName(string $message, ?string $name, string $fallbackPhone): string
    {
        if (blank($message)) {
            return $message;
        }

        $replacement = $name ?: $fallbackPhone;

        return str_ireplace(['{name}', '{{name}}'], $replacement, $message);
    }
}
