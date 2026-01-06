<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HasUserScope
{
    /**
     * Restrict the query to a specific user unless they are an admin.
     */
    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        $user ??= auth()->user();

        if (! $user || $user->isAdmin()) {
            return $query;
        }

        return $query->where($this->getTable().'.user_id', $user->getKey());
    }
}

