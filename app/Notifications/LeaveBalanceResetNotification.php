<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Filament\Notifications\Notification as FilamentNotification;
use App\Models\Worker;
use App\Models\LeaveBalance;

class LeaveBalanceResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Worker $worker,
        public LeaveBalance $balance,
        public bool $isAdmin = false
    ) {}

    /** Channel pengiriman */
    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast']; // realtime + email + dashboard
    }

    /** Email */
    public function toMail($notifiable)
    {
        $subject = $this->isAdmin
            ? "Reset Saldo Cuti Tahunan: {$this->worker->worker_name}"
            : "Saldo Cuti Tahunan Anda Telah Direset";

        return (new MailMessage)
            ->subject($subject)
            ->greeting($this->isAdmin ? 'Halo HR/Admin,' : "Halo {$this->worker->worker_name},")
            ->line($this->isAdmin
                ? "Saldo cuti tahunan untuk {$this->worker->worker_name} telah direset."
                : "Saldo cuti tahunan Anda telah diperbarui.")
            ->line("Tahun: {$this->balance->year}")
            ->line("Hak cuti baru: {$this->balance->hak_cuti} hari")
            ->lineIf($this->isAdmin, "Reset dilakukan pada: {$this->balance->reset_at->format('d M Y H:i')}")
            ->action('Lihat di Sistem', url('/'))
            ->salutation('Terima kasih, Sistem Informasi Cuti PT FSS');
    }

    /** Database (Filament Notification Center) */
    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->isAdmin
                ? "Reset saldo cuti {$this->worker->worker_name}"
                : "Saldo cuti Anda telah direset",
            'message' => $this->isAdmin
                ? "Hak cuti baru: {$this->balance->hak_cuti} hari."
                : "Tahun {$this->balance->year}, hak cuti Anda kini {$this->balance->hak_cuti} hari.",
        ];
    }

    /** Broadcast realtime (WebSocket / Pusher) */
    public function toBroadcast($notifiable)
    {
        return [
            'title' => $this->isAdmin
                ? "Reset saldo cuti {$this->worker->worker_name}"
                : "Saldo cuti Anda telah direset",
            'message' => $this->isAdmin
                ? "Hak cuti baru: {$this->balance->hak_cuti} hari."
                : "Tahun {$this->balance->year}, hak cuti Anda kini {$this->balance->hak_cuti} hari.",
        ];
    }

    /** Filament notification tampilan */
    public function toFilament($notifiable)
    {
        return FilamentNotification::make()
            ->title($this->isAdmin
                ? "Reset saldo cuti {$this->worker->worker_name}"
                : "Saldo cuti Anda telah direset")
            ->success()
            ->body($this->isAdmin
                ? "Hak cuti baru {$this->balance->hak_cuti} hari berhasil direset."
                : "Tahun {$this->balance->year}, hak cuti Anda kini {$this->balance->hak_cuti} hari.")
            ->sendToDatabase($notifiable);
    }
}
