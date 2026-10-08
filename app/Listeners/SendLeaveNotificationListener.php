<?php

namespace App\Listeners;

use App\Events\{
    LeavePermissionCreated,
    LeavePermissionInProgress,
    LeaveApprovalProgress,
    LeaveApprovalFinal,
    LeaveApprovalRejected,
    LeavePermissionUpdated
};
use App\Models\DepartmentApprover;
use App\Models\User;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class SendLeaveNotificationListener
{
    public function handle($event): void
    {
        if ($event instanceof LeavePermissionUpdated) {
            $this->onUpdated($event->leave, $event->changes ?? []);
            return;
        }

        match (true) {
            $event instanceof LeavePermissionCreated => $this->onCreated($event->leave),
            $event instanceof LeavePermissionInProgress => $this->onInProgress($event->leave),
            $event instanceof LeaveApprovalProgress => $this->onApprovalProgress($event->leave, $event->approver ?? null),
            $event instanceof LeaveApprovalFinal => $this->onFinalApproval($event->leave, $event->approver ?? null),
            $event instanceof LeaveApprovalRejected => $this->onRejected($event->leave, $event->approver ?? null),
            default => null,
        };
    }

    protected function onCreated($leave)
    {
        $workerUser = $leave->worker->user;
        $from = Carbon::parse($leave->leave_date_from)->translatedFormat('Y-m-d');
        $to   = Carbon::parse($leave->leave_date_to)->translatedFormat('Y-m-d');

        // Notif ke pengaju
        if ($workerUser) {
            Notification::make()
                ->title('Pengajuan Cuti Dibuat')
                ->body("Pengajuan cuti Anda untuk {$from} s/d {$to} telah dibuat.")
                ->success()
                ->sendToDatabase($workerUser);
        }

        // Notif ke approver atau manager sesuai level
        $this->notifyApprover($leave, $leave->current_level);
    }

    protected function onInProgress($leave)
    {
        $this->notifyApprover($leave, $leave->current_level);
    }

    protected function onApprovalProgress($leave, $approver)
    {
        // Notif ke approver yang menyetujui
        if ($approver instanceof User) {
            Notification::make()
                ->title('Persetujuan Diterima')
                ->body("Anda telah menyetujui pengajuan cuti {$leave->worker->user->name}.")
                ->success()
                ->sendToDatabase($approver);
        }

        // Notif ke worker
        $workerUser = $leave->worker->user;
        if ($workerUser) {
            Notification::make()
                ->title('Pengajuan Diproses')
                ->body("Pengajuan cuti Anda telah disetujui oleh {$approver?->name}.")
                ->info()
                ->sendToDatabase($workerUser);
        }
    }

    protected function onFinalApproval($leave, $approver)
    {
        $workerUser = $leave->worker->user;
        if (!$workerUser) return;

        // Cek apakah masih ada approver selanjutnya (misal manager)
        $nextApprover = \App\Helpers\LeaveHelper::getNextApprover($leave);
        if ($nextApprover) {
            // Masih ada approver berikutnya, jangan kirim notifikasi final
            return;
        }

        // Cek notifikasi final sudah pernah dikirim
        $exists = $workerUser->notifications()
            ->where('type', 'App\\Notifications\\LeavePermissionFinalApprovedNotification')
            ->where('data->leave_id', $leave->id)
            ->exists();

        if ($exists) return;

        // Kirim notifikasi ke worker
        Notification::make()
            ->title('Pengajuan Disetujui')
            ->body("Pengajuan cuti Anda telah disetujui sepenuhnya.")
            ->success()
            ->sendToDatabase($workerUser);

        // Notif ke approver terakhir
        if ($approver instanceof User) {
            Notification::make()
                ->title('Persetujuan Final')
                ->body("Anda telah memberikan persetujuan akhir untuk pengajuan {$workerUser->name}.")
                ->success()
                ->sendToDatabase($approver);
        }
    }

    protected function onRejected($leave, $approver)
    {
        // Notif ke approver
        if ($approver instanceof User) {
            Notification::make()
                ->title('Anda Menolak Pengajuan')
                ->body("Anda menolak pengajuan cuti {$leave->worker->user->name}.")
                ->danger()
                ->sendToDatabase($approver);
        }

        // Notif ke worker
        $workerUser = $leave->worker->user;
        if ($workerUser) {
            Notification::make()
                ->title('Pengajuan Ditolak')
                ->body("Pengajuan cuti Anda ditolak oleh {$approver?->name}.")
                ->danger()
                ->sendToDatabase($workerUser);
        }
    }

    protected function onUpdated($leave, array $changes = [])
    {
        $workerUser = $leave->worker->user;

        $approverId = \App\Helpers\LeaveHelper::getNextApprover($leave);
        $approverUser = $approverId ? User::find($approverId) : null;

        $summary = $this->formatChanges($changes);

        if ($workerUser) {
            Notification::make()
                ->title('Perubahan pada Pengajuan Anda')
                ->body($summary ?: 'Ada perubahan pada pengajuan cuti Anda.')
                ->info()
                ->sendToDatabase($workerUser);
        }

        if ($approverUser) {
            Notification::make()
                ->title('Perubahan Pengajuan Cuti')
                ->body($summary ?: 'Pengajuan cuti telah diperbarui dan membutuhkan konfirmasi Anda.')
                ->warning()
                ->sendToDatabase($approverUser);
        }
    }

    protected function formatChanges(array $changes): ?string
    {
        if (empty($changes)) return null;

        $parts = [];
        foreach ($changes as $key => $val) {
            if (is_array($val) && array_key_exists('old', $val) && array_key_exists('new', $val)) {
                $parts[] = ucfirst(str_replace('_', ' ', $key)) . ": {$val['old']} → {$val['new']}";
            } else {
                $parts[] = ucfirst(str_replace('_', ' ', $key)) . ": {$val}";
            }
        }

        return implode(' — ', $parts);
    }

    protected function notifyApprover($leave, $level = null)
    {
        $level = $level ?? $leave->current_level;

        // Ambil semua approver di level tersebut dalam departemen
        $approvers = DepartmentApprover::where('department_id', $leave->worker->department_id)
            ->where('level', $level)
            ->get();

        if ($approvers->isEmpty()) {
            return;
        }

        // Jika hanya satu approver di level ini → kirim langsung
        if ($approvers->count() === 1) {
            $approver = $approvers->first()->user;
            if ($approver) {
                Notification::make()
                    ->title('Ada Pengajuan Cuti Baru')
                    ->body("Pengajuan cuti dari {$leave->worker->user->name} membutuhkan persetujuan Anda.")
                    ->warning()
                    ->sendToDatabase($approver);
            }
            return;
        }

        // Jika lebih dari satu approver di level yang sama → cek relasi leader_assignment
        $workerUser = $leave->worker->user;

        // Cari apakah worker punya assignment aktif ke salah satu approver
        $assignedLeaderIds = $workerUser->leaderAssignments()
            ->whereNull('ended_at')
            ->pluck('leader_id')
            ->toArray();

        // Filter approver berdasarkan assignment
        $filteredApprovers = $approvers->filter(function ($approver) use ($assignedLeaderIds) {
            return in_array($approver->approver_id, $assignedLeaderIds);
        });

        // Jika worker punya relasi ke salah satu leader → kirim hanya ke mereka
        if ($filteredApprovers->isNotEmpty()) {
            foreach ($filteredApprovers as $approver) {
                if ($approver->user) {
                    Notification::make()
                        ->title('Ada Pengajuan Cuti Baru')
                        ->body("Pengajuan cuti dari {$leave->worker->user->name} membutuhkan persetujuan Anda.")
                        ->warning()
                        ->sendToDatabase($approver->user);
                }
            }
            return;
        }

        // Jika tidak ada relasi leader_assignment → kirim ke semua approver level tersebut
        foreach ($approvers as $approver) {
            if ($approver->user) {
                Notification::make()
                    ->title('Ada Pengajuan Cuti Baru')
                    ->body("Pengajuan cuti dari {$leave->worker->user->name} membutuhkan persetujuan Anda.")
                    ->warning()
                    ->sendToDatabase($approver->user);
            }
        }
    }
}
