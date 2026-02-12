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
use Throwable;

class AutoReplyManager extends Component
{
    use AuthorizesRequests;

    public ?int $ruleId = null;
    public ?int $deviceId = null;
    public ?int $filterDeviceId = null;
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
            'groupedRules' => $this->rulesGrouped(),
            'templates' => $this->templateOptions,
        ]);
    }

    public function save(): void
    {
        try {
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
                Log::info('Auto reply rule updated', [
                    'rule_id' => $rule->getKey(),
                    'device_id' => $device->id,
                    'user_id' => $device->user_id,
                ]);
            } else {
                $this->authorize('create', AutoReplyRule::class);
                $rule = AutoReplyRule::create($payload);
                Log::info('Auto reply rule created', [
                    'rule_id' => $rule->getKey(),
                    'device_id' => $device->id,
                    'user_id' => $device->user_id,
                ]);
            }

            $this->resetForm();
            $this->notify('success', __('Auto reply rule saved successfully.'));
        } catch (Throwable $exception) {
            Log::error('Failed to save auto reply rule', [
                'rule_id' => $this->ruleId,
                'device_id' => $this->deviceId,
                'message' => $exception->getMessage(),
            ]);

            $this->notify('error', __('Failed to save auto reply rule.'));
        }
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
        try {
            $rule = AutoReplyRule::query()
                ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
                ->findOrFail($ruleId);

            $this->authorize('delete', $rule);

            $rule->delete();

            $this->notify('success', __('Rule deleted.'));
            $this->resetForm();
        } catch (Throwable $exception) {
            Log::error('Failed to delete auto reply rule', [
                'rule_id' => $ruleId,
                'message' => $exception->getMessage(),
            ]);

            $this->notify('error', __('Failed to delete auto reply rule.'));
        }
    }

    public function toggle(int $ruleId): void
    {
        try {
            $rule = AutoReplyRule::query()
                ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
                ->findOrFail($ruleId);

            $this->authorize('update', $rule);

            $rule->update(['is_active' => ! $rule->is_active]);
            $this->notify('success', $rule->is_active ? __('Rule activated.') : __('Rule paused.'));
        } catch (Throwable $exception) {
            Log::error('Failed to toggle auto reply rule', [
                'rule_id' => $ruleId,
                'message' => $exception->getMessage(),
            ]);

            $this->notify('error', __('Failed to update rule status.'));
        }
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

    private function notify(string $type, string $message): void
    {
        if (method_exists($this, 'dispatchBrowserEvent')) {
            $this->dispatchBrowserEvent('swal', [
                'type' => $type,
                'message' => $message,
            ]);
            $this->dispatchBrowserEvent('notify', [
                'type' => $type,
                'message' => $message,
            ]);
        }
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
            ->when($this->filterDeviceId, fn (Builder $builder) => $builder->where('whatsapp_device_id', $this->filterDeviceId))
            ->orderBy('whatsapp_device_id')
            ->latest()
            ->get();
    }

    private function rulesGrouped()
    {
        $rules = $this->rulesList();

        return $rules->groupBy(function (AutoReplyRule $rule): string {
            return $rule->device?->name ?? __('Unknown device');
        });
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
            'faq_price' => [
                'label' => 'Auto-reply info harga',
                'keyword' => 'harga',
                'match_mode' => 'contains',
                'body' => "Halo! Untuk info harga terbaru dari {$appName}, mohon sebutkan produk/layanan yang kamu maksud. Tim kami akan kirimkan detailnya.",
            ],
            'faq_location' => [
                'label' => 'Auto-reply lokasi',
                'keyword' => 'alamat',
                'match_mode' => 'contains',
                'body' => "Berikut alamat {$appName}: [ISI ALAMAT LENGKAP]. Jika butuh patokan, balas MAP untuk kirim tautan maps.",
            ],
            'promo_info' => [
                'label' => 'Balasan info promo',
                'keyword' => 'promo',
                'match_mode' => 'contains',
                'body' => "Terima kasih atas ketertarikannya! Promo terbaru kami: diskon 10% + gratis ongkir untuk transaksi di atas Rp500.000. Ketik PESAN untuk dibantu admin.",
            ],
            'catalog_request' => [
                'label' => 'Auto-reply katalog',
                'keyword' => 'katalog',
                'match_mode' => 'contains',
                'body' => "Baik! Kami kirimkan katalog {$appName}. Mohon tunggu sebentar, atau balas PRODUK jika ingin rekomendasi cepat.",
            ],
            'order_status' => [
                'label' => 'Auto-reply status pesanan',
                'keyword' => 'status',
                'match_mode' => 'contains',
                'body' => "Untuk cek status pesanan, silakan kirimkan nomor order. Tim {$appName} akan bantu cekkan.",
            ],
            'payment_info' => [
                'label' => 'Auto-reply info pembayaran',
                'keyword' => 'bayar',
                'match_mode' => 'contains',
                'body' => "Pembayaran dapat dilakukan via transfer/QRIS. Balas METODE untuk mendapatkan detail pembayaran terbaru dari {$appName}.",
            ],
            'cs_handoff' => [
                'label' => 'Auto-reply minta admin',
                'keyword' => 'admin',
                'match_mode' => 'contains',
                'body' => "Baik, pesan kamu akan diteruskan ke admin {$appName}. Mohon tunggu sebentar ya.",
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
