<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WhatsAppDevice;

class WhatsAppDevicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WhatsAppDevice $device): bool
    {
        return $user->isAdmin() || $device->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, WhatsAppDevice $device): bool
    {
        return $user->isAdmin() || $device->user_id === $user->id;
    }

    public function delete(User $user, WhatsAppDevice $device): bool
    {
        return $user->isAdmin() || $device->user_id === $user->id;
    }
}

