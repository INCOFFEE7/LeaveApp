<?php

namespace App\Observers;

use App\Models\LeaveApproval;
use App\Models\LeaveBalance;
use App\Models\DepartmentApprover;
use App\Models\LeaveStatusLog;
use App\Events\LeaveApprovalProgress;
use App\Events\LeaveApprovalFinal;
use App\Helpers\DateHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;


class LeaveApprovalObserverImagickVer
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
        $departmentId = $leave->worker->department_id;
        $currentLevel = $approval->level;

        DB::afterCommit(function () use ($leave, $approval, $departmentId, $currentLevel) {

            $nextApprover = DepartmentApprover::where('department_id', $departmentId)
                ->where('level', '>', $currentLevel)
                ->orderBy('level', 'asc')
                ->first();

            if ($nextApprover) {
                $leave->updateQuietly([
                    'status' => 'in_progress',
                    'current_level' => $nextApprover->level,
                ]);

                event(new LeaveApprovalProgress($leave, $nextApprover->level));

                Log::info("LeaveApprovalObserver: Pengajuan {$leave->id} masih ada level berikutnya.", [
                    'current_level' => $currentLevel,
                    'next_level' => $nextApprover->level,
                ]);

                return;
            }

            // Approver terakhir
            $leave->updateQuietly([
                'status' => 'approved',
                'current_level' => $currentLevel,
            ]);

            if ($leave->leaveType?->leave_type_name === 'Cuti Tahunan') {
                $this->deductLeaveBalance($leave);
            }

            event(new LeaveApprovalFinal($leave));
            Log::info("LeaveApprovalObserver: Approval final untuk pengajuan {$leave->id} dipicu.");

            try {
                // ---------------- Generate Barcode Leave ID ----------------
                $barcodeDir = storage_path('app/public/barcodes');
                if (!file_exists($barcodeDir)) mkdir($barcodeDir, 0755, true);
                $barcodePath = "{$barcodeDir}/leave_{$leave->id}.png";
                QrCode::format('png')->size(200)->generate('LEAVE-' . $leave->id, $barcodePath);

                // ---------------- Generate QR tiap approver ----------------
                $qrcodeDir = storage_path('app/public/qrcodes');
                if (!file_exists($qrcodeDir)) mkdir($qrcodeDir, 0755, true);

                foreach ($leave->approvals->sortBy('approved_at') as $a) {
                $text = "{$a->approver->name} ({$a->approver->getRoleNames()->first()}) - "
                      . optional($a->approved_at)->format('d M Y H:i');

                $qrPath = storage_path("app/public/qrcodes/approval_{$a->id}.png");

                // Convert PNG ke RGB agar DomPDF bisa baca
                $img = new \Imagick($qrPath);
                $img->setImageBackgroundColor('white');
                $img = $img->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $img->setImageFormat('png');
                $img->writeImage($qrPath);

                QrCode::format('png')->size(160)->generate($text, $qrPath);
                $a->qrPath = $qrPath; // path absolut
            }
                // ---------------- Generate PDF ----------------
                $reportDir = storage_path('app/public/reports');
                if (!file_exists($reportDir)) mkdir($reportDir, 0755, true);
                $pdfPath = "{$reportDir}/leave_{$leave->id}.pdf";

                $pdf = Pdf::loadView('pdf.leave-report', [
                    'leave' => $leave,
                    'leaveBarcodePath' => $barcodePath,
                ]);
                $pdf->save($pdfPath);

                // ---------------- Catat log status ----------------
                LeaveStatusLog::create([
                    'leave_permission_id' => $leave->id,
                    'changed_by' => $approval->approved_by,
                    'action' => 'approved',
                    'new_status' => 'approved',
                    'note' => $approval->note ?? null,
                    'report_path' => str_replace(base_path('public'), '', $pdfPath),
                ]);

                Log::info("PDF dan QR code untuk pengajuan {$leave->id} berhasil dibuat.", [
                    'pdf' => $pdfPath,
                    'barcode' => $barcodePath,
                ]);

            } catch (\Throwable $e) {
                Log::error("Gagal generate PDF/QR untuk pengajuan {$leave->id}: " . $e->getMessage());
            }

        });
    }

    public function deductLeaveBalance($leave): void
    {
        $worker = $leave->worker;
        $doh = $worker->date_of_hire ? Carbon::parse($worker->date_of_hire) : now();
        $workedYears = $doh->diffInYears(now());
        $year = $doh->copy()->addYears($workedYears)->year;

        $balance = LeaveBalance::where('worker_id', $worker->id)
            ->where('year', $year)->first();

        if (!$balance) {
            $balance = LeaveBalance::where('worker_id', $worker->id)
                ->latest('year')->first();
        }

        if (!$balance) {
            $balance = LeaveBalance::create([
                'worker_id' => $worker->id,
                'year' => $year,
                'hak_cuti' => 12,
                'sisa_cuti' => 12,
                'utang_cuti' => 0,
                'reset_at' => $doh->copy()->addYear(),
            ]);
        }

        $workdays = DateHelper::workdaysBetween($leave->leave_date_from, $leave->leave_date_to);
        if ($balance->sisa_cuti >= $workdays) {
            $balance->sisa_cuti -= $workdays;
        } else {
            $balance->utang_cuti += ($workdays - $balance->sisa_cuti);
            $balance->sisa_cuti = 0;
        }

        $balance->customNote = 'Pemotongan cuti otomatis setelah disetujui';
        $balance->customUserId = $worker->user_id;
        $balance->save();
    }
}

