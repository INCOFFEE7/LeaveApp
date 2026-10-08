<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

// Widgets
use App\Filament\Widgets\Admin\{
    LeaveStatsOverview,
    LeaveUsageChart,
    LeaveTableWidget
};
use App\Filament\Widgets\Approver\{
    StatsOverview as ApproverStatsOverview,
    LeaveApprovalTableWidget,
    LeaveHistoryTableWidget
};
use App\Filament\Widgets\Employee\{
    EmployeeStatsOverview,
    EmployeeLeaveHistoryTableWidget,
    EmployeeLeaveCalendarWidget
};

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $title = 'Dashboard';
    protected static string $view = 'filament.pages.dashboard';
    protected static ?int $navigationSort = 1;

    public static function getWidgets(): array
    {
        // Pastikan user sudah login
        $user = Auth::user();
        if (!$user) {
            return [EmployeeLeaveCalendarWidget::class];
        }

        // Admin Dashboard
        if ($user->can('dashboard.view.admin')) {
            return [
                LeaveStatsOverview::class,
                LeaveUsageChart::class,
                EmployeeLeaveCalendarWidget::class,
                LeaveTableWidget::class,
            ];
        }

        // Approver Dashboard
        if ($user->can('dashboard.view.approver')) {
            return [
                ApproverStatsOverview::class,
                EmployeeLeaveCalendarWidget::class,
                LeaveApprovalTableWidget::class,
                LeaveHistoryTableWidget::class,
            ];
        }

        // Employee Dashboard
        if ($user->can('dashboard.view.employee')) {
            return [
                EmployeeStatsOverview::class,
                EmployeeLeaveCalendarWidget::class,
                EmployeeLeaveHistoryTableWidget::class,
            ];
        }

        // Default fallback (kalau tidak punya permission)
        return [EmployeeLeaveCalendarWidget::class];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }
}
