<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\LeavePermission;

class ReminderApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public LeavePermission $leave;

    public function __construct(LeavePermission $leave)
    {
        $this->leave = $leave;
    }

    public function via($notifiable): array
    {
        // channel berdasarkan preferensi user
        return match ($notifiable->reminder_channel) {
            'in_app' => ['database'],
            'email'  => ['mail'],
            'both'   => ['mail', 'database'],
            default  => ['database'],
        };
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Reminder] Persetujuan Pengajuan Cuti #' . $this->leave->id)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Pengajuan cuti oleh ' . $this->leave->worker->user->name . ' masih menunggu persetujuan Anda.')
            ->line('Tanggal: ' . $this->leave->start_date->format('d M Y') . ' - ' . $this->leave->end_date->format('d M Y'))
            ->line('Jenis: ' . $this->leave->leaveType->name)
            ->action('Proses Sekarang', url('/leave-approvals/' . $this->leave->id))
            ->line('Terima kasih.');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'   => 'Reminder Persetujuan Cuti',
            'message' => 'Pengajuan cuti #' . $this->leave->id . ' menunggu persetujuan Anda.',
            'url'     => url('/leave-approvals/' . $this->leave->id),
        ];
    }
}
