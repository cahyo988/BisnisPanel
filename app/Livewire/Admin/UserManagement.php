<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    public string $search = '';
    public string $role = '';
    public int $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'role' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRole(): void
    {
        $this->resetPage();
    }

    public function updateRole(int $userId, string $role): void
    {
        if (! in_array($role, [User::ROLE_ADMIN, User::ROLE_USER], true)) {
            return;
        }

        if ($userId === auth()->id() && $role !== User::ROLE_ADMIN) {
            session()->flash('status', __('You cannot remove your own admin access.'));

            return;
        }

        $user = User::query()->findOrFail($userId);
        $user->role = $role;
        $user->save();

        session()->flash('status', __('Role updated for :name.', ['name' => $user->name]));
    }

    public function render(): View
    {
        $users = User::query()
            ->when($this->role !== '', fn (Builder $query) => $query->where('role', $this->role))
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';
                $query->where(function (Builder $subQuery) use ($term): void {
                    $subQuery->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.admin.user-management', [
            'users' => $users,
        ]);
    }
}
