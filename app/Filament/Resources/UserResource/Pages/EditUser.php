<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;
use App\Models\Worker;
use Illuminate\Support\Facades\Hash;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected array $workerData = [];

    protected function fillForm(): void
    {
        $user = $this->record;

        $this->form->fill([
            ...$user->attributesToArray(),
            'roles' => $user->roles()->pluck('id')->first(),
            'worker' => $user->worker ? $user->worker->only([
            'department_id', 'position', 'date_of_hire'
        ]) : [],
            'password' => null, // kosongkan agar tidak tampil hash-nya
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Simpan worker agar bisa diproses di afterSave
        $this->workerData = $data['worker'] ?? [];

        // Tangani role_id
        $roleId = $data['roles'] ?? null;
        if ($roleId) {
            $role = Role::find($roleId);
            if ($role) {
                $data['role_id'] = $role->id;
            }
        }

        // Enkripsi password hanya jika diisi
        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        unset($data['roles'], $data['worker']);

        return $data;
    }

    protected function afterSave(): void
    {
        // ðŸ”¹ Update role
        $roleId = $this->data['role_id'] ?? null;
        if ($roleId) {
            $role = Role::find($roleId);
            if ($role) {
                $this->record->syncRoles([$role->name]);
            }
        }

        // ðŸ”¹ Update worker
        if (!empty($this->workerData)) {
            Worker::updateOrCreate(
                ['user_id' => $this->record->id],
                $this->workerData
            );
        }
    }
}
