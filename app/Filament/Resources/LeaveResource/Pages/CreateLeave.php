<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use App\Models\LeavePermission;
use App\Models\DepartmentApprover;
use App\Models\LeaveApproval;
use App\Helpers\DateHelper;
use Filament\Support\Exceptions\Halt;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CreateLeave extends CreateRecord
{
    protected static string $resource = LeaveResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user   = Auth::user();
        $worker = $user->worker;

        // =====================================================
        // 0️⃣ NORMALISASI FLAG
        // =====================================================
        $data['is_manual'] = (bool) ($data['is_manual'] ?? false);

        // =====================================================
        // 1️⃣ VALIDASI TUMPANG TINDIH CUTI
        // =====================================================
        $exists = LeavePermission::where('worker_id', $data['worker_id'])
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($data) {
                $q->whereBetween('leave_date_from', [$data['leave_date_from'], $data['leave_date_to']])
                  ->orWhereBetween('leave_date_to', [$data['leave_date_from'], $data['leave_date_to']]);
            })
            ->exists();

        if ($exists) {
            Notification::make()
                ->title('Pengajuan Gagal')
                ->body('Terdapat pengajuan cuti lain pada rentang tanggal tersebut.')
                ->danger()
                ->send();

            $this->addError('leave_date_from', 'Tanggal cuti bertabrakan.');
            throw new Halt();
        }

        // =====================================================
        // 2️⃣ HITUNG CUTI (SERVER SIDE)
        // =====================================================
        $data['total_leave'] = DateHelper::workdaysBetween(
            $data['leave_date_from'],
            $data['leave_date_to']
        );

        $data['return_to_work_date'] = DateHelper::nextWorkday(
            $data['leave_date_to']
        );

        // =====================================================
        // 3️⃣ FIELD BREAK (ONSITE)
        // =====================================================
        if (
            isset($data['leave_type_id']) &&
            LeaveResource::isFieldBreak($data['leave_type_id'])
        ) {
            $onsiteDays = DateHelper::workdaysBetween(
                $data['onsite_date_from'],
                $data['onsite_date_to']
            );

            $data['onsite_total_days'] = max(1, $onsiteDays);
            $data['earned_leave']      = intdiv($data['onsite_total_days'], 7);
        } else {
            // pastikan bersih
            $data['onsite_total_days'] = null;
            $data['earned_leave']      = 0;
        }

        // =====================================================
        // 4️⃣ TENTUKAN LEVEL APPROVAL
        // =====================================================
        $departmentId = $worker->department_id;
        $userLevel    = $user->approverLevel($departmentId);

        $data['current_level'] = $userLevel ? $userLevel + 1 : 1;

        $approvers = DepartmentApprover::where('department_id', $departmentId)->get();

        if ($approvers->isEmpty()) {
            Notification::make()
                ->title('Pengajuan Gagal')
                ->body('Departemen belum memiliki approver.')
                ->danger()
                ->send();

            throw new Halt();
        }

        if (! $approvers->contains('level', $data['current_level'])) {
            $next = $approvers->where('level', '>', $data['current_level'])->sortBy('level')->first();

            if (! $next) {
                Notification::make()
                    ->title('Pengajuan Gagal')
                    ->body('Tidak ditemukan approver lanjutan.')
                    ->danger()
                    ->send();

                throw new Halt();
            }

            $data['current_level'] = $next->level;
        }

        // =====================================================
        // 5️⃣ STATUS & AUDIT
        // =====================================================
        if ($data['is_manual']) {
            $data['status']        = 'approved';
            $data['current_level'] = null;
            $data['approved_at']   = now();
            $data['approved_by']   = $user->id;
        } else {
            $data['status'] = 'pending';
        }

        $data['created_by'] = $user->id;
        $data['updated_by'] = $user->id;

        return $data;
    }

    protected function afterCreate(): void
    {
        Log::info('✅ afterCreate dijalankan', [
            'leave_id' => $this->record->id,
        ]);

        // hanya untuk manual
        if (! $this->record->is_manual) {
            return;
        }

        LeaveApproval::create([
            'leave_id'    => $this->record->id,
            'level'       => 0,
            'approved_by' => auth()->id(),
            'status'      => 'approved',
            'note'        => 'Manual approval by admin',
        ]);
    }
}
