<?php

namespace App\Notifications;

use App\Models\LeavePermission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

class LeaveSubmittedNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public LeavePermission $leave,
        public string $type // 'submitted' | 'for_approval'
    ) {}

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $subject = $this->type === 'for_approval'
            ? 'Pengajuan Cuti Baru Menunggu Persetujuan'
            : 'Pengajuan Cuti Berhasil Diajukan';

        return (new MailMessage)
            ->subject($subject)
            ->line('Nama: ' . $this->leave->worker->worker_name)
            ->line('Tanggal: ' . $this->leave->leave_date_from . ' - ' . $this->leave->leave_date_to)
            ->line('Alasan: ' . $this->leave->leave_reason)
            ->action('Lihat Detail', route('filament.main.resources.approval-cuti.index'))
            ->line('Terima kasih.');
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->type === 'for_approval'
                ? 'Cuti Menunggu Persetujuan'
                : 'Pengajuan Cuti Berhasil',

            'body' => $this->type === 'for_approval'
                ? 'Ada pengajuan cuti baru menunggu persetujuan Anda.'
                : 'Pengajuan cuti Anda berhasil dikirim.',

            'url' => $this->type === 'for_approval'
                ? route('filament.main.resources.approval-cuti.index')
                : route('filament.main.resources.pengajuan-cuti.index'),

            'leave_id' => $this->leave->id,
            'type' => $this->type,
        ];
    }

    public function toArray($notifiable)
    {
        return [
            'leave_id' => $this->leave->id,
            'type' => $this->type,
        ];
    }
}
