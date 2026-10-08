<?php

namespace App\Observers;

use App\Models\LeaveApproval;
use App\Models\DepartmentApprover;
use App\Models\LeaveStatusLog;
use App\Events\LeaveApprovalProgress;
use App\Events\LeaveApprovalFinal;
use App\Helpers\DateHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;

class LeaveApprovalObserver
{
    public function created(LeaveApproval $approval)
    {
        if ($approval->status === 'approved') {
            $this->handleApproval($approval);
        }
    }

    public function updated(LeaveApproval $approval)
    {
        if ($approval->isDirty('status') && $approval->status === 'approved') {
            $this->handleApproval($approval);
        }
    }

    protected function handleApproval(LeaveApproval $approval): void
    {
        $leave = $approval->leavePermission;

        if (!$approval->approved_by) {
            Log::error('Approval tanpa approved_by', ['approval_id' => $approval->id]);
            return;
        }

        $departmentId = $leave->worker->department_id;
        $currentLevel = $approval->level;

        DB::afterCommit(function () use ($leave, $approval, $departmentId, $currentLevel) {

            Log::info("MASUK afterCommit");

            // ================= NEXT APPROVER =================
            $nextApprover = DepartmentApprover::where('department_id', $departmentId)
                ->where('level', '>', $currentLevel)
                ->orderBy('level')
                ->first();

            if ($nextApprover) {
                $leave->updateQuietly([
                    'status' => 'in_progress',
                    'current_level' => $nextApprover->level,
                ]);

                event(new LeaveApprovalProgress($leave, $nextApprover->level));

                Log::info("Masih ada approval berikutnya", [
                    'leave_id' => $leave->id,
                    'next_level' => $nextApprover->level,
                ]);

                return;
            }

            // ================= FINAL APPROVAL =================
            $leave->updateQuietly([
                'status' => 'approved',
                'current_level' => $currentLevel,
            ]);

            // ================= POTONG CUTI =================
            if ($leave->leaveType?->leave_type_name === 'Cuti Tahunan') {
                try {
                    $this->deductLeaveBalance($leave, $approval);
                } catch (\Throwable $e) {
                    Log::error("Gagal potong saldo cuti: " . $e->getMessage());
                }
            }

            // ================= FIELD BREAK =================
            if ($leave->leaveType?->code === 'field_break') {
                try {
                    $this->handleFieldBreak($leave, $approval);
                } catch (\Throwable $e) {
                    Log::error("Gagal proses field break: " . $e->getMessage());
                }
            }

            // ================= LOG (WAJIB) =================
            $statusLog = LeaveStatusLog::create([
                'leave_permission_id' => $leave->id,
                'changed_by' => $approval->approved_by,
                'action' => 'approved',
                'new_status' => 'approved',
                'note' => $approval->note ?? null,
                'report_path' => null,
            ]);

            event(new LeaveApprovalFinal($leave));

            // ================= OPTIONAL (QR + PDF) =================
            try {

                // BARCODE
                $barcodeDir = storage_path('app/public/barcodes');
                if (!file_exists($barcodeDir)) mkdir($barcodeDir, 0755, true);

                $barcodePath = "{$barcodeDir}/leave_{$leave->id}.png";

                QrCode::format('png')
                    ->size(200)
                    ->backgroundColor(255,255,255)
                    ->generate('LEAVE-' . $leave->id, $barcodePath);

                // QR APPROVAL
                $qrcodeDir = storage_path('app/public/qrcodes');
                if (!file_exists($qrcodeDir)) mkdir($qrcodeDir, 0755, true);

                foreach ($leave->approvals->sortBy('approved_at') as $a) {
                    $text = "{$a->approver->name} - " .
                        optional($a->approved_at)->format('d M Y H:i');

                    $qrPath = "{$qrcodeDir}/approval_{$a->id}.png";

                    QrCode::format('png')
                        ->size(160)
                        ->backgroundColor(255,255,255)
                        ->generate($text, $qrPath);

                    $a->qrPath = $qrPath;
                }

                // PDF
                $reportDir = storage_path('app/public/reports');
                if (!file_exists($reportDir)) mkdir($reportDir, 0755, true);

                $pdfPath = "{$reportDir}/leave_{$leave->id}.pdf";

                Pdf::loadView('pdf.leave-report', [
                    'leave' => $leave,
                    'leaveBarcodePath' => $barcodePath,
                ])->save($pdfPath);

                // UPDATE LOG PATH
                $statusLog->update([
                    'report_path' => str_replace(base_path('public'), '', $pdfPath),
                ]);

                Log::info("PDF berhasil dibuat", ['leave_id' => $leave->id]);

            } catch (\Throwable $e) {
                Log::error("Gagal generate PDF/QR: " . $e->getMessage());
            }

        });
    }

    // ================= CUTI TAHUNAN =================
    public function deductLeaveBalance($leave, $approval): void
    {
        $leaveYear = Carbon::parse($leave->leave_date_from)->year;

        $balance = \App\Services\EnsureLeaveBalanceForYear::handle(
            workerId: $leave->worker_id,
            year: $leaveYear,
            actorUserId: $approval->approved_by,
            note: 'Auto saldo cuti'
        );

        $workdays = DateHelper::workdaysBetween(
            $leave->leave_date_from,
            $leave->leave_date_to
        );

        $sisaAfter = $balance->sisa_cuti - $workdays;

        if ($sisaAfter < 0) {
            $balance->sisa_cuti = 0;
            $balance->utang_cuti += abs($sisaAfter);
        } else {
            $balance->sisa_cuti = $sisaAfter;
        }

        $balance->save();
    }

    // ================= FIELD BREAK =================
    protected function handleFieldBreak($leave, $approval): void
    {
        $earned = $leave->adjusted_earned_leave ?? $leave->earned_leave;

        if ($earned <= 0) return;

        $leaveYear = Carbon::parse($leave->leave_date_from)->year;

        $balance = \App\Services\EnsureLeaveBalanceForYear::handle(
            workerId: $leave->worker_id,
            year: $leaveYear,
            actorUserId: $approval->approved_by,
            note: 'Saldo field break'
        );

        $balance->sisa_cuti += $earned;
        $balance->save();

        \App\Models\LeaveBalanceLog::create([
            'leave_balance_id' => $balance->id,
            'user_id' => $approval->approved_by,
            'change' => +$earned,
            'note' => $leave->adjustment_note ?? 'Field Break',
            'created_by' => $approval->approved_by,
        ]);
    }
}
