<?php

namespace App\Filament\Resources\LeaderAssignmentResource\Pages;

use App\Filament\Resources\LeaderAssignmentResource;
use App\Models\LeaderAssignment;
use App\Models\Worker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateLeaderAssignment extends CreateRecord
{
    protected static string $resource = LeaderAssignmentResource::class;

    protected function handleRecordCreation(array $data): LeaderAssignment
    {
        return DB::transaction(function () use ($data) {
            $createdRecord = null;

            foreach ($data['worker_ids'] ?? [] as $workerId) {
                $worker = Worker::find($workerId);
                if ($worker) {
                    $createdRecord = LeaderAssignment::create([
                        'leader_id'     => $data['leader_id'],
                        'worker_id'     => $worker->id,
                        'department_id' => $worker->department_id,
                        'level'         => $worker->level,
                        'effective_date'=> $data['effective_date'] ?? now(),
                    ]);
                }
            }

            return $createdRecord ?? new LeaderAssignment();
        });
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Penugasan Leader berhasil disimpan')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
