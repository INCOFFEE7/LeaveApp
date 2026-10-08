<?php

namespace App\Http\Controllers;

use App\Models\LeavePermission;
use App\Models\LeaveStatusLog;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\LeaveApprovedMail;

class LeavePermissionReportController extends Controller
{
    public function generateFinalReport($id)
    {
        $leave = LeavePermission::with([
            'worker.user',
            'worker.department',
            'leaveType',
            'approvals.approver',
        ])->findOrFail($id);

        if ($leave->status !== 'approved') {
            Log::warning("Laporan cuti $id belum final approved, diabaikan.");
            return false;
        }

        // --------------------------
        // Generate Barcode Leave ID
        // --------------------------
        $barcodePath = "public/barcodes/leave_{$leave->id}.png";
        Storage::makeDirectory('public/barcodes');
        QrCode::format('png')
            ->size(200)
            ->generate('LEAVE-' . $leave->id, Storage::path($barcodePath));
        $leaveBarcodePath = Storage::path($barcodePath); // path fisik untuk PDF

        // --------------------------
        // Generate QR untuk tiap approver
        // --------------------------
        Storage::makeDirectory('public/qrcodes');
        foreach ($leave->approvals->sortBy('approved_at') as $approval) {
            $text = "{$approval->approver->name} "
                  . "({$approval->approver->getRoleNames()->first()}) - "
                  . optional($approval->approved_at)->format('d M Y H:i');

            $qrPath = "public/qrcodes/approval_{$approval->id}.png";
            QrCode::format('png')->size(160)
                ->generate($text, Storage::path($qrPath));

            $approval->qrPath = Storage::path($qrPath); // simpan path fisik
        }

        // --------------------------
        // Generate PDF menggunakan Blade
        // --------------------------
        $pdf = Pdf::loadView('pdf.leave-report', [
            'leave' => $leave,
            'leaveBarcodePath' => $leaveBarcodePath,
        ]);

        $pdfPath = "public/reports/leave_{$leave->id}.pdf";
        Storage::makeDirectory('public/reports');
        $pdf->save(Storage::path($pdfPath));

        // --------------------------
        // Simpan ke log perubahan cuti
        // --------------------------
        try {
            LeaveStatusLog::create([
                'leave_permission_id' => $leave->id,
                'changed_by' => auth()->id() ?? $leave->approvals->last()?->approver_id,
                'action' => 'Final Approved',
                'new_status' => 'approved',
                'note' => 'Laporan cuti akhir disetujui dan diarsipkan.',
                'report_path' => Storage::url($pdfPath),
            ]);
        } catch (\Throwable $e) {
            Log::error("Gagal menyimpan log laporan cuti {$leave->id}: " . $e->getMessage());
        }

        // --------------------------
        // Kirim ke email pengaju
        // --------------------------
        if ($leave->worker?->user?->email) {
            try {
                Mail::to($leave->worker->user->email)
                    ->send(new LeaveApprovedMail($leave, Storage::path($pdfPath)));
            } catch (\Throwable $e) {
                Log::error("Gagal mengirim email laporan cuti {$leave->id}: " . $e->getMessage());
            }
        }

        Log::info("Laporan cuti final {$leave->id} berhasil dibuat dan disimpan di log.");

        return true;
    }
}
