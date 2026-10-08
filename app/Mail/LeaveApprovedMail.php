<?php

namespace App\Mail;

use App\Models\LeavePermission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeaveApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $leave;
    public $pdfPath;

    public function __construct(LeavePermission $leave, string $pdfPath)
    {
        $this->leave = $leave;
        $this->pdfPath = $pdfPath;
    }

    public function build()
    {
        return $this->subject('Laporan Cuti Disetujui - PT FSS')
            ->view('emails.leave-approved')
            ->attach($this->pdfPath, [
                'as' => 'Laporan_Cuti.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
