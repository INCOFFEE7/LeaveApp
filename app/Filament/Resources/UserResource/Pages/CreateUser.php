<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Role;
use App\Models\Worker;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected array $roles = [];
    protected array $workerData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Simpan role dan worker data agar bisa diproses setelah create
        $this->roles = $data['roles'] ?? [];
        $this->workerData = $data['worker'] ?? [];

        // Tangani role_id di tabel users
        $roleId = $this->roles;
        if ($roleId) {
            $role = Role::find($roleId);
            if ($role) {
                $data['role_id'] = $role->id;
            }
        }

        // Hapus field non-user dari array utama
        unset($data['roles'], $data['worker']);

        // Enkripsi password
        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // ðŸ”¹ Sinkronisasi role
        if (!empty($this->roles)) {
            $role = Role::find($this->roles);
            if ($role) {
                $this->record->syncRoles([$role->name]);
            }
        }

        // ðŸ”¹ Buat worker jika data diisi
        if (!empty($this->workerData)) {
            Worker::updateOrCreate(
                ['user_id' => $this->record->id],
                $this->workerData
            );
        }
    }
}
