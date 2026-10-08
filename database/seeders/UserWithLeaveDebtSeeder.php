<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Worker;
use App\Models\Department;
use App\Models\LeaveBalance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class UserWithLeaveDebtSeeder extends Seeder
{
    public function run(): void
    {
        // Cari role worker
        $role = Role::where('name', 'worker')->first();
        if (!$role) {
            $this->command->warn("Role 'worker' belum ada.");
            return;
        }

        // Cari departemen Finance Accounting and Tax
        $department = Department::where('department_name', 'Finance Accounting and Tax')->first();
        if (!$department) {
            $this->command->warn("Departemen 'Finance Accounting and Tax' belum ditemukan.");
            return;
        }


        // Buat user baru
        $user = User::updateOrCreate(
            ['email' => 'hutangworker@fss.com'],
            [
                'payroll_number' => 2001,
                'name' => 'Worker Hutang',
                'role_id' => $role->id,
                'password' => Hash::make('password'),
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $user->assignRole('worker');
        $this->command->info("User baru dengan hutang cuti berhasil dibuat: {$user->name}");

        // Buat worker
        $worker = Worker::updateOrCreate(
            ['user_id' => $user->id],
            [
                'company' => 'FSS',
                'worker_name' => $user->name,
                'department_id' => $department->id,
                'position' => 'Staff',
                'date_of_hire' => '2021-10-22',
                'is_active' => 1,
                'created_by' => $user->id ?? null,
                'updated_by' => $user->id ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Tambahkan LeaveBalance dengan hutang cuti di periode lalu
        $year = Carbon::now()->year - 1;

        // Ambil bulan & hari dari date_of_hire
        $doh = Carbon::parse($worker->date_of_hire);
        $resetAt = Carbon::create($year, $doh->month, $doh->day);

        LeaveBalance::updateOrCreate(
            ['worker_id' => $worker->id, 'year' => $year],
            [
                'hak_cuti' => 12,
                'sisa_cuti' => -3,
                'utang_cuti' => 3,
                'reset_at' => $resetAt,
            ]
        );

        $this->command->info("LeaveBalance hutang cuti ditambahkan untuk {$worker->worker_name}.");
    }
}
