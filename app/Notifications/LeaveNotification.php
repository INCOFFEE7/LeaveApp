<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class LeaveNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    public string $type;
    public string $modelType;
    public int $modelId;
    public ?int $level;

    public function __construct(string $type, int $modelId, string $modelType, ?int $level = null)
    {
        $this->type = $type;
        $this->modelId = $modelId;
        $this->modelType = $modelType;
        $this->level = $level;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {

        $model = $this->modelType::find($this->modelId);

        $title = 'Notifikasi';
        $message = 'Ada update pada pengajuan cuti.';
        $url = url('/');

        if (!$model) {
            $message = 'Data tidak ditemukan.';
            return compact('title', 'message', 'url');
        }

        if ($model instanceof \App\Models\LeavePermission) {
            $workerName = $model->worker?->worker_name ?? 'User';
            switch ($this->type) {
                case 'leave_approval_requested':
                    $title = "Persetujuan Cuti Dibutuhkan";
                    $message = "$workerName mengajukan cuti dan butuh persetujuan level {$this->level}";
                    $url = url("/leave-approvals/{$model->id}");
                    break;

                case 'leave_created_worker':
                    $title = "Pengajuan Cuti Tercatat";
                    $message = "Anda mengajukan cuti dari {$model->leave_date_from} hingga {$model->leave_date_to}";
                    $url = url("/leave-permissions/{$model->id}");
                    break;

                case 'leave_created_approver':
                    $title = "Pengajuan Cuti Baru";
                    $message = "$workerName mengajukan cuti dari {$model->leave_date_from} hingga {$model->leave_date_to}";
                    $url = url("/leave-approvals/{$model->id}");
                    break;

                case 'leave_status_changed':
                    $title = "Status Cuti Berubah";
                    $message = "Status cuti Anda telah diperbarui.";
                    $url = url("/leave-permissions/{$model->id}");
                    break;

                case 'leave_final_approved':
                    $title = "Cuti Disetujui";
                    $message = "Pengajuan cuti Anda telah disetujui.";
                    $url = url("/leave-permissions/{$model->id}");
                    break;
            }
        } elseif ($model instanceof \App\Models\LeaveBalance) {
            $workerName = $model->worker?->worker_name ?? 'User';
            if ($this->type === 'leave_balance_reset') {
                $title = "Saldo Cuti Direset";
                $message = "Saldo cuti $workerName untuk tahun {$model->year} telah diperbarui. Hak cuti: {$model->hak_cuti}, Sisa cuti: {$model->sisa_cuti}, Utang cuti: {$model->utang_cuti}";
                $url = url("/leave-balances/{$model->id}");
            }
        }

        return compact('title', 'message', 'url');
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
