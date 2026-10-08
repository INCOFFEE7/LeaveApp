<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Auth;

class EditLeave extends EditRecord
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * 🚫 Cegah edit jika status sudah diproses
     */
    protected function beforeFill(): void
    {
        if (in_array($this->record->status, ['in_progress', 'approved', 'rejected'])) {
            Notification::make()
                ->title('Tidak dapat diedit')
                ->body('Pengajuan cuti yang sudah diproses (disetujui atau ditolak) tidak bisa diedit lagi.')
                ->danger()
                ->send();

            $this->redirect(static::getResource()::getUrl('index'));
        }
    }

    /**
     * 🧩 Validasi sebelum simpan
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->record;

        if (in_array($record->status, ['in_progress', 'approved', 'rejected'])) {
            $this->addError('status', 'Cuti yang sudah diproses tidak dapat diubah.');
            throw new Halt('Cuti yang sudah diproses tidak bisa diubah.');
        }

        // ✅ Cek apakah ada cuti lain yang overlap
        $exists = \App\Models\LeavePermission::where('worker_id', $data['worker_id'])
            ->where('id', '!=', $record->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($data) {
                $q->whereBetween('leave_date_from', [$data['leave_date_from'], $data['leave_date_to']])
                  ->orWhereBetween('leave_date_to', [$data['leave_date_from'], $data['leave_date_to']]);
            })
            ->exists();

        if ($exists) {
            Notification::make()
                ->title('Perubahan gagal')
                ->body('Terdapat pengajuan cuti lain pada rentang tanggal ini.')
                ->danger()
                ->send();

            $this->addError('leave_date_from', 'Terdapat pengajuan cuti lain pada rentang tanggal ini.');
            throw new Halt('Perubahan gagal karena tumpang tindih dengan cuti lain.');
        }

        $data['updated_by'] = Auth::id();

        return $data;
    }

    /**
     * ✅ Notifikasi sukses setelah simpan
     */
    protected function afterSave(): void
    {
        $record = $this->record;

        $returnDate = $record->leave_date_to
            ? \App\Helpers\DateHelper::nextWorkday($record->leave_date_to)
            : null;

        $record->updateQuietly([
            'total_leave'        => $record->total_leave ?? 0,
            'return_to_work_date'=> $returnDate,
            'onsite_total_days'  => $record->onsite_total_days,
            'earned_leave'       => $record->earned_leave,
            'updated_by'         => auth()->id(),
        ]);
        Notification::make()
            ->title('Perubahan berhasil disimpan')
            ->success()
            ->send();
    }
}
