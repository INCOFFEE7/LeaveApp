<?php

namespace App\Filament\Resources\LeaderAssignmentResource\Pages;

use App\Filament\Resources\LeaderAssignmentResource;
use App\Models\LeaderAssignment;
use App\Models\Worker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditLeaderAssignment extends EditRecord
{
    protected static string $resource = LeaderAssignmentResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['worker_ids'] = LeaderAssignment::where('leader_id', $data['leader_id'])
            ->pluck('worker_id')
            ->toArray();

        return $data;
    }

    protected function handleRecordUpdate($record, array $data): LeaderAssignment
    {
        return DB::transaction(function () use ($record, $data) {
            LeaderAssignment::where('leader_id', $record->leader_id)->delete();

            $lastRecord = null;

            foreach ($data['worker_ids'] ?? [] as $workerId) {
                $worker = Worker::find($workerId);

                if ($worker) {
                    $lastRecord = LeaderAssignment::create([
                        'leader_id'     => $data['leader_id'],
                        'worker_id'     => $worker->id,
                        'department_id' => $worker->department_id,
                        'level'         => $worker->level,
                        'effective_date'=> $data['effective_date'] ?? now(),
                    ]);
                }
            }

            return $lastRecord ?? $record;
        });
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->title('Penugasan Leader berhasil diperbarui')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
