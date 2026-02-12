<?php

namespace App\Livewire;

use App\Jobs\ProcessBroadcastJob;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Models\WhatsAppDevice;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use ZipArchive;

class BroadcastPage extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public ?int $deviceId = null;
    public string $message = '';
    public int $delayMs = 1500;
    public $upload;
    public ?string $currentBatchId = null;
    public ?string $scheduledAt = null;
    public ?int $templateId = null;
    public bool $useContactNames = true;

    protected $listeners = [
        'device-created' => '$refresh',
        'device-removed' => '$refresh',
    ];

    public function render(): View
    {
        return view('livewire.broadcast-page', [
            'devices' => $this->deviceOptions(),
            'progress' => $this->progress,
            'recentLogs' => $this->currentBatchId ? $this->batchLogs() : collect(),
            'templates' => $this->templateOptions(),
            'nameMap' => $this->useContactNames ? $this->recentIncomingNames() : [],
        ]);
    }

    public function start(): void
    {
        $validated = $this->validate($this->rules());

        if ($this->templateId) {
            $this->applyTemplate();
        }

        if (blank($this->message)) {
            $this->addError('message', __('Message body is required.'));
            return;
        }

        $numbers = collect($this->loadRecipients())
            ->map(fn ($phone) => $this->normalizePhone($phone))
            ->filter()
            ->unique()
            ->values();

        if ($numbers->isEmpty()) {
            $this->addError('upload', 'No phone numbers were found in the file.');

            return;
        }

        $device = WhatsAppDevice::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($validated['deviceId']);

        $this->authorize('view', $device);

        $batchId = (string) Str::uuid();
        $scheduledAt = $this->parseSchedule($validated['scheduledAt'] ?? null);

        $nameMap = $this->useContactNames ? $this->recentIncomingNames() : [];
        $logIds = $numbers->take(500)->map(function (string $phone) use ($device, $batchId, $scheduledAt, $nameMap) {
            $payload = array_filter([
                'broadcast' => true,
                'template_id' => $this->templateId,
                'contact_name' => $nameMap[$phone] ?? null,
            ]);

            return MessageLog::create([
                'user_id' => $device->user_id,
                'whatsapp_device_id' => $device->id,
                'batch_id' => $batchId,
                'direction' => MessageLog::DIRECTION_OUTGOING,
                'type' => MessageLog::TYPE_TEXT,
                'phone' => $phone,
                'message' => $this->renderMessageWithName($this->message, $nameMap[$phone] ?? null, $phone),
                'status' => $scheduledAt ? MessageLog::STATUS_SCHEDULED : MessageLog::STATUS_PENDING,
                'raw_payload' => $payload,
                'scheduled_at' => $scheduledAt,
            ])->id;
        })->all();

        if ($scheduledAt && $scheduledAt->isFuture()) {
            ProcessBroadcastJob::dispatch($logIds, $this->delayMs)->delay($scheduledAt);
        } else {
            ProcessBroadcastJob::dispatch($logIds, $this->delayMs);
        }

        $this->currentBatchId = $batchId;
        $this->reset(['message', 'upload', 'templateId', 'scheduledAt']);

        $this->dispatch('message-sent');

        session()->flash('broadcast_started', 'Broadcast queued for '.min($numbers->count(), 500).' numbers.');
    }

    protected function rules(): array
    {
        return [
            'deviceId' => ['required', 'integer', 'exists:whatsapp_devices,id'],
            'message' => [
                Rule::requiredIf(fn () => blank($this->templateId)),
                'nullable',
                'string',
                'max:2000',
            ],
            'delayMs' => ['required', 'integer', 'min:0', 'max:10000'],
            'upload' => ['required', 'file', 'mimes:csv,txt,xlsx'],
            'scheduledAt' => ['nullable', 'date'],
            'templateId' => ['nullable', 'integer', 'exists:message_templates,id'],
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

    private function templateOptions()
    {
        return MessageTemplate::query()
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

    private function loadRecipients(): array
    {
        $path = $this->upload->getRealPath();

        if (! $path) {
            return [];
        }

        $extension = strtolower($this->upload->getClientOriginalExtension());

        return match ($extension) {
            'xlsx' => $this->extractFromExcel($path),
            default => $this->extractFromCsv($path),
        };
    }

    private function extractFromCsv(string $path): array
    {
        $numbers = [];

        if (($handle = fopen($path, 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                $value = $row[0] ?? null;

                if ($value) {
                    $numbers[] = $value;
                }
            }

            fclose($handle);
        }

        return $numbers;
    }

    private function extractFromExcel(string $path): array
    {
        $zip = new ZipArchive();
        $numbers = [];

        if ($zip->open($path) !== true) {
            return $numbers;
        }

        $sharedStrings = $this->parseSharedStrings($zip->getFromName('xl/sharedStrings.xml'));
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');

        if ($sheetXml) {
            $xml = simplexml_load_string($sheetXml);
            if ($xml === false) {
                $zip->close();

                return $numbers;
            }
            foreach ($xml->sheetData->row as $row) {
                $cell = $row->c[0] ?? null;
                if (! $cell || ! isset($cell->v)) {
                    continue;
                }

                $value = (string) $cell->v;
                if ((string) $cell['t'] === 's') {
                    $index = (int) $value;
                    $value = $sharedStrings[$index] ?? $value;
                }

                $numbers[] = $value;
            }
        }

        $zip->close();

        return $numbers;
    }

    /**
     * @return array<int, string>
     */
    private function parseSharedStrings(?string $xml): array
    {
        if (! $xml) {
            return [];
        }

        $document = simplexml_load_string($xml);

        if ($document === false) {
            return [];
        }

        $sharedStrings = [];

        foreach ($document->si as $index => $si) {
            $sharedStrings[$index] = (string) $si->t;
        }

        return $sharedStrings;
    }

    private function normalizePhone(string $phone): string
    {
        $stripped = preg_replace('/[^0-9\+]/', '', $phone);

        if (blank($stripped)) {
            return '';
        }

        return Str::startsWith($stripped, '+') ? $stripped : '+'.$stripped;
    }

    public function getProgressProperty(): ?array
    {
        if (! $this->currentBatchId) {
            return null;
        }

        $baseQuery = MessageLog::query()
            ->where('batch_id', $this->currentBatchId)
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder));

        $total = (clone $baseQuery)->count();

        if ($total === 0) {
            return null;
        }

        $sent = (clone $baseQuery)->where('status', MessageLog::STATUS_SENT)->count();
        $failed = (clone $baseQuery)->where('status', MessageLog::STATUS_FAILED)->count();

        return compact('total', 'sent', 'failed');
    }

    public function applyTemplate(): void
    {
        if (! $this->templateId) {
            return;
        }

        $template = MessageTemplate::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
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
        if ($target === 'single') {
            return;
        }

        $this->message = $body;
    }

    private function batchLogs(): Collection
    {
        return MessageLog::query()
            ->where('batch_id', $this->currentBatchId)
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->latest()
            ->limit(10)
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

    /**
     * @return array<string, string>
     */
    private function recentIncomingNames(): array
    {
        $deviceId = $this->deviceId;

        if (! $deviceId) {
            return [];
        }

        $rows = MessageLog::query()
            ->select(['phone', 'raw_payload'])
            ->where('direction', MessageLog::DIRECTION_INCOMING)
            ->where('whatsapp_device_id', $deviceId)
            ->whereNotNull('raw_payload')
            ->latest()
            ->limit(300)
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $phone = $this->normalizePhone($row->phone);
            if (! $phone || isset($map[$phone])) {
                continue;
            }

            $payload = $row->raw_payload;
            if (! is_array($payload)) {
                continue;
            }

            $name = $payload['push_name'] ?? null;

            if (filled($name)) {
                $map[$phone] = (string) $name;
            }
        }

        return $map;
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
