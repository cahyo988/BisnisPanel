<?php

namespace App\Policies;

use App\Models\PanelNotification;
use App\Models\User;

class PanelNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PanelNotification $notification): bool
    {
        return $user->isAdmin() || $notification->user_id === $user->id;
    }

    public function update(User $user, PanelNotification $notification): bool
    {
        return $this->view($user, $notification);
    }

    public function delete(User $user, PanelNotification $notification): bool
    {
        return $this->view($user, $notification);
    }
}

