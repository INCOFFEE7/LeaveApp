<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        \App\Events\LeavePermissionCreated::class => [
            \App\Listeners\SendLeaveNotificationListener::class,
        ],
        \App\Events\LeavePermissionInProgress::class => [
            \App\Listeners\SendLeaveNotificationListener::class,
        ],
        \App\Events\LeaveApprovalProgress::class => [
            \App\Listeners\SendLeaveNotificationListener::class,
        ],
        \App\Events\LeaveApprovalFinal::class => [
            \App\Listeners\SendLeaveNotificationListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    // public function boot(): void
    // {
    //     parent::boot();

    //     // log untuk debug
    //     \Log::info('✅ EventServiceProvider booted');
    // }

    /**
     * Apakah mau auto-discovery listener?
     * Kita pakai manual daftar → return false.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
