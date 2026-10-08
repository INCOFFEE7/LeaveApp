<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveType;
use App\Models\User;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Cari user dengan payroll_number 1001 (misalnya admin)
        $admin = User::where('payroll_number', 1001)->first();

        // Jika tidak ditemukan, tampilkan peringatan
        if (! $admin) {
            $this->command->warn("⚠️ Admin dengan NIP 1001 tidak ditemukan. Seeder dibatalkan.");
            return;
        }

        // Masukkan data jenis cuti
        $leaveTypes = [
            [
                'leave_type_name' => 'Sakit',
                'is_active' => true,
                'created_by' => $admin->id,  // gunakan user.id
                'updated_by' => $admin->id,
            ],
            [
                'leave_type_name' => 'Izin',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'leave_type_name' => 'Cuti Tahunan',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'leave_type_name' => 'Field Break',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
        ];

        // Insert ke database
        foreach ($leaveTypes as $type) {
            LeaveType::create($type);
        }
    }
}
