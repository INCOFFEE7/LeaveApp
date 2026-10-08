<?php

namespace App\Notifications;

use App\Models\LeavePermission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class LeavePermissionUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LeavePermission $leave,
        public array $changes = []
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $changedFields = $this->changes['changes'] ?? '-';
        $note = $this->changes['note'] ?? '-';

        return [
            'title' => 'Perubahan Pengajuan Cuti',
            'body'  => "Data pengajuan cuti Anda telah diperbarui oleh atasan. {$changedFields}. Alasan perubahan: {$note}.",
            'url'   => route('filament.resources.leave-permissions.edit', $this->leave->id),
        ];
    }

    public function toFilament($notifiable)
    {
        return FilamentNotification::make()
            ->title('Perubahan Pengajuan Cuti')
            ->body('Data pengajuan cuti Anda telah diperbarui oleh atasan.')
            ->success();
    }
}
