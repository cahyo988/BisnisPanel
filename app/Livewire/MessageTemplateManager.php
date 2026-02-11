<?php

namespace App\Livewire;

use App\Models\MessageTemplate;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class MessageTemplateManager extends Component
{
    use AuthorizesRequests;

    public ?int $templateId = null;
    public ?int $selectedUserId = null;
    public string $name = '';
    public string $body = '';
    public string $search = '';

    public function mount(): void
    {
        if (! auth()->user()->isAdmin()) {
            $this->selectedUserId = auth()->id();
        }
    }

    public function render(): View
    {
        $viewer = auth()->user();

        return view('livewire.message-template-manager', [
            'templates' => $this->templates(),
            'userOptions' => $viewer->isAdmin()
                ? User::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function save(): void
    {
        try {
            $validated = $this->validate($this->rules());
            $viewer = auth()->user();

            $targetUserId = $viewer->isAdmin()
                ? ($validated['selectedUserId'] ?? $this->selectedUserId)
                : $viewer->id;

            if ($viewer->isAdmin() && ! $targetUserId) {
                $this->addError('selectedUserId', __('Select a user for this template.'));
                return;
            }

            $payload = [
                'user_id' => $targetUserId,
                'name' => $validated['name'],
                'body' => $validated['body'],
            ];

            if ($this->templateId) {
                $template = MessageTemplate::query()
                    ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
                    ->findOrFail($this->templateId);

                $this->authorize('update', $template);
                $template->update($payload);
                Log::info('Message template updated', ['template_id' => $template->getKey()]);
            } else {
                $this->authorize('create', MessageTemplate::class);
                $template = MessageTemplate::create($payload);
                Log::info('Message template created', ['template_id' => $template->getKey()]);
            }

            $this->resetForm();
            $this->notify('success', __('Template saved successfully.'));
        } catch (Throwable $exception) {
            Log::error('Failed to save message template', [
                'template_id' => $this->templateId,
                'message' => $exception->getMessage(),
            ]);

            $this->notify('error', __('Failed to save template.'));
        }
    }

    public function edit(int $templateId): void
    {
        $template = MessageTemplate::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($templateId);

        $this->authorize('view', $template);

        $this->templateId = $template->id;
        $this->selectedUserId = $template->user_id;
        $this->name = $template->name;
        $this->body = $template->body;
    }

    public function delete(int $templateId): void
    {
        try {
            $template = MessageTemplate::query()
                ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
                ->findOrFail($templateId);

            $this->authorize('delete', $template);
            $template->delete();

            $this->resetForm();
            $this->notify('success', __('Template deleted.'));
        } catch (Throwable $exception) {
            Log::error('Failed to delete message template', [
                'template_id' => $templateId,
                'message' => $exception->getMessage(),
            ]);

            $this->notify('error', __('Failed to delete template.'));
        }
    }

    public function resetForm(): void
    {
        $this->templateId = null;
        $this->name = '';
        $this->body = '';
    }

    #[On('message-template-apply')]
    public function applyTemplate(int $templateId, string $target = 'single'): void
    {
        $template = MessageTemplate::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->findOrFail($templateId);

        $this->authorize('view', $template);

        $this->dispatch(
            'message-template-applied',
            body: $template->body,
            name: $template->name,
            target: $target,
        );
    }

    protected function rules(): array
    {
        return [
            'selectedUserId' => auth()->user()->isAdmin()
                ? ['required', 'integer', 'exists:users,id']
                : ['nullable'],
            'name' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    private function templates()
    {
        return MessageTemplate::query()
            ->tap(fn (Builder $builder) => $this->applyUserScope($builder))
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $builder) {
                    $builder->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('body', 'like', '%'.$this->search.'%');
                });
            })
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
}
