<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Worker;
use App\Observers\WorkerObserver;
use App\Models\LeaveApproval;
use App\Models\LeavePermission;
use App\Observers\LeavePermissionObserver;
use App\Observers\LeaveApprovalObserver;
use Filament\AvatarProviders\Contracts\AvatarProvider;
use App\Providers\Filament\CustomAvatarProvider;
use App\Http\Responses\LogoutResponse;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;
use Filament\Facades\Filament;


class AppServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
        $this->app->singleton(AvatarProvider::class, CustomAvatarProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Worker::observe(WorkerObserver::class);
        LeaveApproval::observe(LeaveApprovalObserver::class);
        LeavePermission::observe(LeavePermissionObserver::class);
    }
}
