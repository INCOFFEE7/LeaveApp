<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Notifications\NotificationsPlugin;
use Filament\Notifications\Livewire\Notifications;

class MainPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('main')
            ->path('leave')
            ->authGuard('web')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->defaultAvatarProvider(CustomAvatarProvider::class)
            ->databaseNotifications()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                \App\Filament\Pages\EditProfile::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->userMenuItems([
                'profile' => \Filament\Navigation\UserMenuItem::make()
                    ->label('Edit Profil')
                    ->icon('heroicon-o-user')
                    ->url(fn (): string => \App\Filament\Pages\EditProfile::getUrl()),
            ])
            // ->topNavigationItems([
            //     \Filament\Navigation\NavigationItem::make('notifications')
            //         ->label('Notifikasi')
            //         ->icon('heroicon-o-bell')
            //         ->url(fn () => \App\Filament\Pages\NotificationsPage::getUrl())
            //         ->badge(fn () => \App\Models\Notification::where('user_id', auth()->id())
            //             ->where('is_read', false)
            //             ->count()),
            // ])
            ->middleware([
                'web',
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
