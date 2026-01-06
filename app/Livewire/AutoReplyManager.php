<?php

namespace App\Livewire;

use App\Models\AutoReplyRule;
use App\Models\WhatsAppDevice;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
    public bool $isActive = true;

    public function render(): View
    {
        return view('livewire.auto-reply-manager', [
            'devices' => $this->deviceOptions(),
            'rules' => $this->rulesList(),
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
        session()->flash('auto_reply_saved', 'Auto reply rule saved.');
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
        $this->isActive = $rule->is_active;
    }

    public function delete(int $ruleId): void
    {
        $rule = AutoReplyRule::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($ruleId);

        $this->authorize('delete', $rule);

        $rule->delete();

        $this->resetForm();
    }

    public function toggle(int $ruleId): void
    {
        $rule = AutoReplyRule::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($ruleId);

        $this->authorize('update', $rule);

        $rule->update(['is_active' => ! $rule->is_active]);
    }

    public function resetForm(): void
    {
        $this->ruleId = null;
        $this->deviceId = null;
        $this->keyword = '';
        $this->matchMode = 'exact';
        $this->replyType = 'text';
        $this->replyText = '';
        $this->isActive = true;
    }

    protected function rules(): array
    {
        return [
            'deviceId' => ['required', 'integer', 'exists:whatsapp_devices,id'],
            'keyword' => ['required', 'string', 'max:120'],
            'matchMode' => ['required', Rule::in(['exact', 'contains'])],
            'replyType' => ['required', Rule::in(['text', 'template'])],
            'replyText' => ['required', 'string', 'max:1000'],
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
}

