<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Worker;
use App\Models\Department;
use App\Models\LeaveBalance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use Spatie\Permission\Models\Role as SpatieRole;

class UserWorkerImport implements ToCollection, WithHeadingRow
{
    protected int $batchSize = 500; // commit per 500 rows

    public function collection(Collection $rows)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '1024M');
        DB::disableQueryLog();

        // Preload departments
        $departments = Department::pluck('id', 'department_name')
            ->mapWithKeys(fn($id, $name) => [strtoupper($name) => $id])
            ->toArray();
        $unassignedDeptId = $departments['UNASSIGNED'] ?? null;
        if (!$unassignedDeptId) throw new \Exception("Department 'UNASSIGNED' harus ada.");

        // Default Spatie role
        $defaultRole = SpatieRole::where('name', 'employee')->first();
        if (!$defaultRole) throw new \Exception("Role 'employee' tidak ditemukan.");

        // $defaultPasswordHash = Hash::make('12345678');

        // Normalize input rows
        $inputRows = [];
        $seenPayrolls = []; // ✅ Track payroll untuk prevent duplikasi
        foreach ($rows as $row) {
            $row = collect($row)->mapWithKeys(fn($v, $k) => [strtolower(trim($k)) => trim($v)]);
            $inputRows[] = $row->toArray();
        }

        // Collect payroll numbers
        $payrolls = [];
        foreach ($inputRows as $r) {
            $pn = $r['p/n'] ?? $r['pn'] ?? null;
            if ($pn) $payrolls[] = $pn;
        }
        $payrolls = array_values(array_unique($payrolls));

        // Existing users
        $existingUsers = User::whereIn('payroll_number', $payrolls)
            ->get(['id', 'payroll_number'])
            ->keyBy('payroll_number')
            ->map(fn($u) => $u->id)
            ->toArray();

        $usersToInsert = [];
        $workersToInsert = [];
        $now = now()->format('Y-m-d H:i:s');

        foreach ($inputRows as $idx => $r) {
            $payroll = $r['p/n'] ?? $r['pn'] ?? null;
            $name = $r['name'] ?? null;
            if (!$payroll || !$name) {
                Log::warning("Skip baris ".($idx+2)." karena payroll atau name kosong", $r);
                continue;
            }

            // ✅ Prevent duplikasi dalam file yang sama
            if (in_array($payroll, $seenPayrolls)) {
                Log::warning("Skip baris ".($idx+2)." karena payroll duplikat dalam file: {$payroll}");
                continue;
            }
            $seenPayrolls[] = $payroll;

            // ✅ Validasi input
            $validationErrors = $this->validateRow($r, $idx);
            if (!empty($validationErrors)) {
                foreach ($validationErrors as $error) {
                    Log::warning("Baris ".($idx+2)." tidak valid: {$error}");
                }
                continue;
            }

            // Parse date_of_hire
            $dateOfHire = null;
            $rawDate = $r['date of hire'] ?? $r['date_of_hire'] ?? null;
            if (!empty($rawDate)) {
                if (is_numeric($rawDate)) {
                    try {
                        $dateOfHire = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate))->format('Y-m-d');
                    } catch (\Throwable) {
                        $dateOfHire = null;
                    }
                } else {
                    $ts = strtotime($rawDate);
                    $dateOfHire = $ts ? date('Y-m-d', $ts) : null;
                }
            }

            $companyRaw = strtoupper(trim($r['company'] ?? ''));
            $statusRaw = strtoupper(trim($r['status'] ?? ''));
            $statusMap = ['P' => 'P', 'K' => 'C', 'O' => 'O'];
            $employmentStatus = $companyRaw !== 'FSS' ? 'O' : ($statusMap[$statusRaw] ?? 'P');

            $deptName = strtoupper(trim($r['department'] ?? ''));
            $deptId = $departments[$deptName] ?? $unassignedDeptId;
            $cabangRaw = strtoupper(trim($r['cabang'] ?? ''));

            $roleIdFromExcel = $r['role_id'] ?? null;
            $roleFromExcel = $roleIdFromExcel ? SpatieRole::find($roleIdFromExcel) : null;

            $isExisting = isset($existingUsers[$payroll]);

            if ($isExisting) {
                $userId = $existingUsers[$payroll];
                $existingWorker = Worker::where('user_id', $userId)->first();
                $changes = [];

                if ($existingWorker) {
                    if ($existingWorker->worker_name !== $name) $changes['worker_name'] = $name;
                    if ($existingWorker->company !== $companyRaw) $changes['company'] = $companyRaw;
                    if ($existingWorker->department_id !== $deptId) $changes['department_id'] = $deptId;
                    if ($existingWorker->position !== ($r['position'] ?? null)) $changes['position'] = $r['position'] ?? null;
                    if ($existingWorker->cabang !== $cabangRaw) $changes['cabang'] = $cabangRaw;
                    if ($existingWorker->employment_status !== $employmentStatus) $changes['employment_status'] = $employmentStatus;
                    if ($existingWorker->date_of_hire !== $dateOfHire) $changes['date_of_hire'] = $dateOfHire;

                    if (!empty($changes)) {
                        $changes['updated_at'] = $now;
                        $existingWorker->update($changes);
                    }
                } else {
                    Worker::create([
                        'user_id' => $userId,
                        'worker_name' => $name,
                        'company' => $companyRaw,
                        'department_id' => $deptId,
                        'position' => $r['position'] ?? null,
                        'cabang' => $cabangRaw,
                        'employment_status' => $employmentStatus,
                        'date_of_hire' => $dateOfHire,
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $existingUser = User::find($userId);
                if ($existingUser && $existingUser->name !== $name) {
                    $existingUser->update(['name' => $name, 'updated_at' => $now]);
                }

                // --- SYNC ROLE SPATIE ---
                if ($existingUser) {
                    if ($roleFromExcel) {
                        if (!$existingUser->hasRole($roleFromExcel->name)) {
                            $existingUser-> syncRolesAndUpdateId($roleFromExcel->name);
                        }
                    } elseif (!$existingUser->hasRole($defaultRole->name)) {
                        $existingUser->syncRolesAndUpdateId($defaultRole->name);
                    }
                }

                continue;
            }

            // Prepare new user insert (tanpa role_id)
            $usersToInsert[] = [
                'payroll_number' => $payroll,
                'name' => $name,
                'email' => $this->generateEmail($payroll), // ✅ Generate email from payroll
                'password' => Hash::make(\Illuminate\Support\Str::random(16)), // ✅ Random password
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'role_id' => $roleIdFromExcel ?? $defaultRole->id,
            ];

            $workersToInsert[] = [
                'payroll_number'    => $payroll,
                'worker_name'       => $name,
                'company'           => $companyRaw,
                'department_id'     => $deptId,
                'position'          => $r['position'] ?? null,
                'cabang'            => $cabangRaw,
                'employment_status' => $employmentStatus,
                'date_of_hire'      => $dateOfHire,
                'is_active'         => 1,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];
        }

        // --- BULK INSERT ---
        try {
            DB::beginTransaction();

            // Users
            foreach (array_chunk($usersToInsert, $this->batchSize) as $chunk) {
                User::insert($chunk);
            }

            $newPayrolls = array_column($usersToInsert, 'payroll_number');
            $allRelevantPayrolls = array_merge($newPayrolls, array_keys($existingUsers));
            $userIdMap = User::whereIn('payroll_number', $allRelevantPayrolls)
                ->get(['id', 'payroll_number'])
                ->keyBy('payroll_number')
                ->map(fn($u) => $u->id)
                ->toArray();

            // Sync roles untuk user baru
            foreach ($newPayrolls as $pn) {
                $userId = $userIdMap[$pn] ?? null;
                if (!$userId) continue;

                $user = User::find($userId);
                $roleId = $usersToInsert[array_search($pn, array_column($usersToInsert, 'payroll_number'))]['role_id'] ?? $defaultRole->id;
                $role = SpatieRole::find($roleId);
                if ($role) {
                    $user->syncRoles($role->name);
                } else {
                    $user->syncRolesAndUpdateId($defaultRole->name);
                }
            }

            // Workers
            $finalWorkerInserts = [];
            foreach ($workersToInsert as $w) {
                $pn = $w['payroll_number'];
                $userId = $userIdMap[$pn] ?? null;
                if (!$userId) continue;
                $finalWorkerInserts[] = [
                    'user_id' => $userId,
                    'worker_name' => $w['worker_name'],
                    'company' => $w['company'],
                    'department_id' => $w['department_id'],
                    'position' => $w['position'],
                    'cabang' => $w['cabang'],
                    'employment_status' => $w['employment_status'],
                    'date_of_hire' => $w['date_of_hire'],
                    'is_active' => $w['is_active'],
                    'created_at' => $w['created_at'],
                    'updated_at' => $w['updated_at'],
                ];
            }

            // Bulk insert Worker
            foreach (array_chunk($finalWorkerInserts, $this->batchSize) as $chunk) {
                Worker::insert($chunk);

                // Ambil ulang mapping worker_id berdasarkan user_id
                $newWorkerIds = Worker::whereIn('user_id', array_column($finalWorkerInserts, 'user_id'))
                    ->pluck('id', 'user_id')
                    ->toArray();

                // Buat LeaveBalance manual untuk worker baru
                foreach ($finalWorkerInserts as $w) {
                    if ($w['date_of_hire'] && isset($newWorkerIds[$w['user_id']])) {
                        $workerId = $newWorkerIds[$w['user_id']];
                        $dateOfHire = Carbon::parse($w['date_of_hire']);

                        // ✅ cek apakah sudah bekerja minimal 1 tahun
                        if ($dateOfHire->diffInYears(Carbon::now()) >= 1) {
                            $year = $dateOfHire->copy()->addYear()->year;

                            LeaveBalance::create([
                                'worker_id' => $workerId,
                                'year' => $year,
                                'hak_cuti' => 12,
                                'sisa_cuti' => 12,
                                'utang_cuti' => 0,
                                'reset_at' => $dateOfHire->copy()->addYear(),
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            Log::info("Import selesai. Users inserted: ".count($usersToInsert).", Workers inserted: ".count($finalWorkerInserts));
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Import gagal: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * ✅ Generate email dari payroll number
     * Format: {payroll}@company.com
     * Jika sudah ada, tambahkan suffix: {payroll}+1@company.com, {payroll}+2@company.com, dst
     */
    private function generateEmail($payroll)
    {
        $baseEmail = $payroll . '@company.com';
        $email = $baseEmail;
        $counter = 1;

        while (User::where('email', $email)->exists()) {
            $email = $payroll . '+' . $counter . '@company.com';
            $counter++;
        }

        return $email;
    }

    /**
     * ✅ Validasi input row
     * Returns array of error messages (empty = valid)
     */
    private function validateRow($row, $idx)
    {
        $errors = [];
        $payroll = $row['p/n'] ?? $row['pn'] ?? null;
        $name = $row['name'] ?? null;
        $payroll = str_pad($payroll, 4, '0', STR_PAD_LEFT);
        // ✅ Validasi payroll: 3–4 digit angka, boleh diawali nol
        if (!preg_match('/^[0-9]{3,4}$/', $payroll)) {
            $errors[] = "Payroll harus 3–4 digit angka, diterima: {$payroll}";
        }

        // Validasi name (minimal 3 karakter)
        if (strlen($name) < 3) {
            $errors[] = "Nama minimal 3 karakter, diterima: {$name}";
        }

        // Validasi department jika ada
        $deptName = strtoupper(trim($row['department'] ?? ''));
        if (!empty($deptName) && $deptName !== 'UNASSIGNED') {
            if (!Department::where('department_name', $deptName)->exists()) {
                $errors[] = "Department tidak ditemukan: {$deptName}";
            }
        }

        // Validasi date_of_hire jika ada (harus valid date format)
        $rawDate = $row['date of hire'] ?? $row['date_of_hire'] ?? null;
        if (!empty($rawDate)) {
            if (!is_numeric($rawDate)) {
                $ts = strtotime($rawDate);
                if ($ts === false) {
                    $errors[] = "Format tanggal tidak valid: {$rawDate}";
                }
            }
        }

        return $errors;
    }
}
