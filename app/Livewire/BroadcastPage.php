<?php

namespace App\Livewire;

use App\Jobs\ProcessBroadcastJob;
use App\Models\MessageLog;
use App\Models\WhatsAppDevice;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
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
        ]);
    }

    public function start(): void
    {
        $validated = $this->validate($this->rules());

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

        $logIds = $numbers->take(500)->map(function (string $phone) use ($device, $batchId) {
            return MessageLog::create([
                'user_id' => $device->user_id,
                'whatsapp_device_id' => $device->id,
                'batch_id' => $batchId,
                'direction' => MessageLog::DIRECTION_OUTGOING,
                'type' => MessageLog::TYPE_TEXT,
                'phone' => $phone,
                'message' => $this->message,
                'status' => MessageLog::STATUS_PENDING,
                'raw_payload' => ['broadcast' => true],
            ])->id;
        })->all();

        ProcessBroadcastJob::dispatch($logIds, $this->delayMs);

        $this->currentBatchId = $batchId;
        $this->reset(['message', 'upload']);

        $this->dispatch('message-sent');

        session()->flash('broadcast_started', 'Broadcast queued for '.min($numbers->count(), 500).' numbers.');
    }

    protected function rules(): array
    {
        return [
            'deviceId' => ['required', 'integer', 'exists:whatsapp_devices,id'],
            'message' => ['required', 'string', 'max:1000'],
            'delayMs' => ['required', 'integer', 'min:0', 'max:10000'],
            'upload' => ['required', 'file', 'mimes:csv,txt,xlsx'],
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

    private function batchLogs(): Collection
    {
        return MessageLog::query()
            ->where('batch_id', $this->currentBatchId)
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->latest()
            ->limit(10)
            ->get();
    }
}
