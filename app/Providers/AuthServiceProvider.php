<?php

namespace App\Providers;

use App\Models\AutoReplyRule;
use App\Models\MessageLog;
use App\Models\PanelNotification;
use App\Models\WhatsAppDevice;
use App\Policies\AutoReplyRulePolicy;
use App\Policies\MessageLogPolicy;
use App\Policies\PanelNotificationPolicy;
use App\Policies\WhatsAppDevicePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        WhatsAppDevice::class => WhatsAppDevicePolicy::class,
        MessageLog::class => MessageLogPolicy::class,
        PanelNotification::class => PanelNotificationPolicy::class,
        AutoReplyRule::class => AutoReplyRulePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}

