<?php

namespace App\Notifications;

use App\Models\LeavePermission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

class LeaveApprovalNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public LeavePermission $leave,
        public string $context, // 'approval' | 'final'
        public string $status,   // 'in_progress' | 'approved' | 'rejected'
        public ?string $note = null
    ) {}

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $subject = match ($this->status) {
            'approved' => 'Pengajuan Cuti Disetujui',
            'rejected' => 'Pengajuan Cuti Ditolak',
            default     => 'Pengajuan Cuti Diproses',
        };

        $mail = (new MailMessage)
            ->subject($subject)
            ->line("Status pengajuan cuti Anda: {$this->status}.")
            ->line('Tanggal: ' . $this->leave->leave_date_from . ' - ' . $this->leave->leave_date_to);

        if ($this->status === 'rejected' && $this->note) {
            $mail->line('Alasan penolakan: ' . $this->note);
        }

        return $mail->action('Lihat Detail', route('filament.main.resources.pengajuan-cuti.index'))
            ->line('Terima kasih.');
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->context === 'final'
                ? "Status Cuti " . ($this->status === 'approved' ? 'Disetujui' : 'Ditolak')
                : "Cuti Perlu Persetujuan",

            'body' => $this->context === 'final'
                ? ($this->status === 'rejected'
                    ? "Pengajuan cuti Anda ditolak. Alasan: {$this->note}"
                    : "Pengajuan cuti Anda telah disetujui.")
                : "Ada pengajuan cuti menunggu persetujuan Anda.",

            'url' => $this->context === 'final'
                ? route('filament.main.resources.pengajuan-cuti.index')
                : route('filament.main.resources.approval-cuti.index'),

            'leave_id' => $this->leave->id,
            'status' => $this->status,
            'context' => $this->context,
        ];
    }

    public function toArray($notifiable)
    {
        return [
            'leave_id' => $this->leave->id,
            'status' => $this->status,
            'context' => $this->context,
        ];
    }
}
