<?php

namespace App\Livewire;

use App\Models\AutoReplyRule;
use App\Models\WhatsAppDevice;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AutoReplyManager extends Component
{
    use AuthorizesRequests;

    public ?int $ruleId = null;
    public ?int $deviceId = null;
    public string $keyword = '';
    public string $matchMode = 'exact';
    public string $replyType = 'text';
    public string $replyText = '';
    public ?string $replyTemplate = null;
    public bool $isActive = true;
    public array $templateOptions = [];

    public function mount(): void
    {
        $this->templateOptions = $this->templatePresets();
    }

    public function render(): View
    {
        return view('livewire.auto-reply-manager', [
            'devices' => $this->deviceOptions(),
            'rules' => $this->rulesList(),
            'templates' => $this->templateOptions,
        ]);
    }

    public function save(): void
    {
        $validated = $this->validate();

        $device = WhatsAppDevice::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($validated['deviceId']);

        $this->authorize('view', $device);

        $payload = [
            'user_id' => $device->user_id,
            'whatsapp_device_id' => $device->id,
            'keyword' => $validated['keyword'],
            'match_mode' => $validated['matchMode'],
            'reply_type' => $validated['replyType'],
            'reply_text' => $validated['replyText'],
            'is_active' => $validated['isActive'],
        ];

        if ($this->ruleId) {
            $rule = AutoReplyRule::query()
                ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
                ->findOrFail($this->ruleId);

            $this->authorize('update', $rule);

            $rule->update($payload);
        } else {
            $rule = AutoReplyRule::create($payload);
        }

        $this->resetForm();
        $this->dispatchBrowserEvent('notify', [
            'type' => 'success',
            'message' => __('Auto reply rule saved successfully.'),
        ]);
    }

    public function edit(int $ruleId): void
    {
        $rule = AutoReplyRule::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($ruleId);

        $this->authorize('view', $rule);

        $this->ruleId = $rule->id;
        $this->deviceId = $rule->whatsapp_device_id;
        $this->keyword = $rule->keyword;
        $this->matchMode = $rule->match_mode;
        $this->replyType = $rule->reply_type;
        $this->replyText = $rule->reply_text;
        $this->replyTemplate = $this->detectTemplateKey($rule->reply_text) ?: null;
        $this->isActive = $rule->is_active;
    }

    public function delete(int $ruleId): void
    {
        $rule = AutoReplyRule::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($ruleId);

        $this->authorize('delete', $rule);

        $rule->delete();

        $this->dispatchBrowserEvent('notify', [
            'type' => 'success',
            'message' => __('Rule deleted.'),
        ]);

        $this->resetForm();
    }

    public function toggle(int $ruleId): void
    {
        $rule = AutoReplyRule::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($ruleId);

        $this->authorize('update', $rule);

        $rule->update(['is_active' => ! $rule->is_active]);
        $this->dispatchBrowserEvent('notify', [
            'type' => 'success',
            'message' => $rule->is_active ? __('Rule activated.') : __('Rule paused.'),
        ]);
    }

    public function resetForm(): void
    {
        $this->ruleId = null;
        $this->deviceId = null;
        $this->keyword = '';
        $this->matchMode = 'exact';
        $this->replyType = 'text';
        $this->replyText = '';
        $this->replyTemplate = '';
        $this->isActive = true;
    }

    public function applyTemplate(string $templateKey): void
    {
        if (! isset($this->templateOptions[$templateKey])) {
            return;
        }

        $template = $this->templateOptions[$templateKey];

        $this->replyTemplate = $templateKey;
        $this->replyType = 'template';
        $this->matchMode = $template['match_mode'] ?? $this->matchMode;
        $this->keyword = $template['keyword'] ?? $this->keyword;
        $this->replyText = $template['body'];

        Log::info('Auto reply template applied', [
            'template_key' => $templateKey,
            'keyword' => $this->keyword,
            'match_mode' => $this->matchMode,
            'reply_type' => $this->replyType,
        ]);

        $this->dispatch(
            'auto-template-filled',
            keyword: $this->keyword,
            matchMode: $this->matchMode,
            replyType: $this->replyType,
            replyText: $this->replyText
        );
    }

    public function updatedReplyTemplate(?string $templateKey): void
    {
        if (blank($templateKey)) {
            $this->replyTemplate = null;

            return;
        }

        $this->applyTemplate($templateKey);
    }

    public function updatedReplyType(string $type): void
    {
        if ($type === 'text') {
            $this->replyTemplate = '';
        }
    }

    public function clearTemplate(): void
    {
        if ($this->replyType === 'template') {
            $this->replyType = 'text';
        }

        $this->replyTemplate = null;
    }

    public function updatedReplyText(?string $value): void
    {
        if ($this->replyTemplate && isset($this->templateOptions[$this->replyTemplate])) {
            if ($value !== $this->templateOptions[$this->replyTemplate]['body']) {
                $this->replyTemplate = null;
            }
        }
    }

    protected function rules(): array
    {
        return [
            'deviceId' => ['required', 'integer', 'exists:whatsapp_devices,id'],
            'keyword' => ['required', 'string', 'max:120'],
            'matchMode' => ['required', Rule::in(['exact', 'contains'])],
            'replyType' => ['required', Rule::in(['text', 'template'])],
            'replyText' => ['required', 'string', 'max:1000'],
            'replyTemplate' => ['nullable', 'string', Rule::in(array_keys($this->templateOptions))],
            'isActive' => ['boolean'],
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

    private function rulesList()
    {
        return AutoReplyRule::query()
            ->with('device')
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->latest()
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

    private function detectTemplateKey(string $text): string
    {
        foreach ($this->templateOptions as $key => $template) {
            if ($template['body'] === $text) {
                return $key;
            }
        }

        return '';
    }

    protected function templatePresets(): array
    {
        $appName = config('app.name', 'BisnisPanel');

        return [
            'default_greeting' => [
                'label' => 'Sapaan cepat',
                'keyword' => 'halo',
                'match_mode' => 'contains',
                'body' => "Halo! Terima kasih telah menghubungi {$appName}. Ada yang bisa kami bantu hari ini?",
            ],
            'faq_hours' => [
                'label' => 'Auto-reply jam operasional',
                'keyword' => 'jam',
                'match_mode' => 'contains',
                'body' => "Hai! Jam operasional {$appName} adalah Senin - Jumat pukul 09.00-18.00. Balas JANJI jika ingin dijadwalkan panggilan.",
            ],
            'promo_info' => [
                'label' => 'Balasan info promo',
                'keyword' => 'promo',
                'match_mode' => 'contains',
                'body' => "Terima kasih atas ketertarikannya! Promo terbaru kami: diskon 10% + gratis ongkir untuk transaksi di atas Rp500.000. Ketik PESAN untuk dibantu admin.",
            ],
            'support_ticket' => [
                'label' => 'Konfirmasi dukungan',
                'keyword' => 'bantu',
                'match_mode' => 'contains',
                'body' => "Keluhan kamu sudah kami terima dan sedang diproses oleh tim {$appName}. Mohon menunggu maksimal 15 menit, kami akan update lewat chat ini.",
            ],
        ];
    }
}
