<?php

namespace App\Imports;

use App\Models\LeaveBalanceLog;
use App\Models\LeaveBalance;
use App\Models\Worker;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LeaveBalanceLogImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $currentUserId = auth()->id() ?? 0; // ✅ Default 0 jika null (artisan command)
        $seenLogs = []; // ✅ Track untuk prevent duplikasi

        foreach ($rows as $idx => $row) {
            $worker = Worker::where('payroll_number', $row['worker_payroll'])->first();
            if (!$worker) {
                \Log::warning("Baris ".($idx+2)." - Worker tidak ditemukan: {$row['worker_payroll']}");
                continue;
            }

            $balance = LeaveBalance::firstOrCreate(
                ['worker_id' => $worker->id],
                ['hak_cuti' => 0, 'sisa_cuti' => 0, 'utang_cuti' => 0]
            );

            // ✅ Validasi numeric values
            $oldHak = (int)($row['old_hak_cuti'] ?? 0);
            $newHak = (int)($row['new_hak_cuti'] ?? 0);
            $oldSisa = (int)($row['old_sisa_cuti'] ?? 0);
            $newSisa = (int)($row['new_sisa_cuti'] ?? 0);
            $oldUtang = (int)($row['old_utang_cuti'] ?? 0);
            $newUtang = (int)($row['new_utang_cuti'] ?? 0);

            // ✅ Prevent duplikasi log (check kombinasi worker + hak_cuti dalam 1 hari)
            $logKey = $worker->id . '-' . $newHak . '-' . date('Y-m-d');
            if (in_array($logKey, $seenLogs)) {
                \Log::warning("Baris ".($idx+2)." - Duplikasi log untuk worker: {$row['worker_payroll']}");
                continue;
            }
            $seenLogs[] = $logKey;

            LeaveBalanceLog::create([
                'leave_balance_id' => $balance->id,
                'user_id' => $currentUserId ?: null, // ✅ Allow null user_id
                'old_hak_cuti' => $oldHak,
                'new_hak_cuti' => $newHak,
                'old_sisa_cuti' => $oldSisa,
                'new_sisa_cuti' => $newSisa,
                'old_utang_cuti' => $oldUtang,
                'new_utang_cuti' => $newUtang,
                'note' => $row['note'] ?? null,
                'created_at' => $row['created_at'] ?? now(),
                'updated_at' => $row['created_at'] ?? now(),
            ]);

            // Perbarui saldo terakhir
            $balance->update([
                'hak_cuti' => $row['new_hak_cuti'],
                'sisa_cuti' => $row['new_sisa_cuti'],
                'utang_cuti' => $row['new_utang_cuti'],
            ]);
        }
    }
}
