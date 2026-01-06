<?php

namespace App\Policies;

use App\Models\MessageLog;
use App\Models\User;

class MessageLogPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MessageLog $log): bool
    {
        return $user->isAdmin() || $log->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MessageLog $log): bool
    {
        return $this->view($user, $log);
    }

    public function delete(User $user, MessageLog $log): bool
    {
        return $this->view($user, $log);
    }
}
