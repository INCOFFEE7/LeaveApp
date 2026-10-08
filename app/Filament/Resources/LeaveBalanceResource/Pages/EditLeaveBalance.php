<?php

namespace App\Filament\Resources\LeaveBalanceResource\Pages;

use App\Filament\Resources\LeaveBalanceResource;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceLog;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;

class EditLeaveBalance extends EditRecord
{
    protected static string $resource = LeaveBalanceResource::class;

    // ✅ Tambahkan property untuk note (alasan perubahan)
    public ?string $note = null;

    protected function resolveRecord($key): LeaveBalance
    {
        return LeaveBalance::with([
            'worker.user',
        ])->findOrFail($key);
    }

    // ✅ Tambahkan input note ke form
    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('hak_cuti')
                ->label('Hak Cuti')
                ->required()
                ->numeric(),

            Forms\Components\TextInput::make('sisa_cuti')
                ->label('Sisa Cuti')
                ->required()
                ->numeric(),

            Forms\Components\TextInput::make('utang_cuti')
                ->label('Cuti Carry Over')
                ->numeric(),

            Forms\Components\Textarea::make('note')
                ->label('Alasan Perubahan')
                ->required()
                ->columnSpanFull()
                ->helperText('Mohon isi alasan perubahan saldo cuti'),
        ];
    }

    protected function afterSave(): void
    {
        $old = $this->record->getOriginal();

        // Ambil "note" dari state form (bukan dari properti langsung)
        $note = $this->form->getState()['note'] ?? '-';

        LeaveBalanceLog::create([
            'leave_balance_id' => $this->record->id,
            'user_id' => auth()->id(),

            'old_hak_cuti' => $old['hak_cuti'],
            'new_hak_cuti' => $this->record->hak_cuti,

            'old_sisa_cuti' => $old['sisa_cuti'],
            'new_sisa_cuti' => $this->record->sisa_cuti,

            'old_utang_cuti' => $old['utang_cuti'],
            'new_utang_cuti' => $this->record->utang_cuti,

            'note' => $note, // ✅ pastikan nilainya tidak null
        ]);
    }

}
