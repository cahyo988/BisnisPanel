<?php

namespace App\Livewire\Admin;

use App\Models\MessageLog;
use App\Models\User;
use App\Models\WhatsAppDevice;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AdminDashboardStats extends Component
{
    public function render(): View
    {
        return view('livewire.admin.admin-dashboard-stats', [
            'totalUsers' => User::query()->count(),
            'adminUsers' => User::query()->where('role', User::ROLE_ADMIN)->count(),
            'regularUsers' => User::query()->where('role', User::ROLE_USER)->count(),
            'deviceCount' => WhatsAppDevice::query()->count(),
            'messagesToday' => MessageLog::query()->whereDate('created_at', today())->count(),
        ]);
    }
}
