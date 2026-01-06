<?php

namespace App\Policies;

use App\Models\AutoReplyRule;
use App\Models\User;

class AutoReplyRulePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AutoReplyRule $rule): bool
    {
        return $user->isAdmin() || $rule->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AutoReplyRule $rule): bool
    {
        return $this->view($user, $rule);
    }

    public function delete(User $user, AutoReplyRule $rule): bool
    {
        return $this->view($user, $rule);
    }
}

