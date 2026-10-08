<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; color: #333; }
        .header { font-weight: bold; font-size: 18px; margin-bottom: 10px; }
        .info { background: #f7f7f7; padding: 10px; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="header">Laporan Cuti Disetujui - PT. Fluid Service Solution</div>
    <p>Halo {{ $leave->worker->user->name }},</p>

    <p>Pengajuan cuti Anda telah disetujui oleh semua approver.</p>

    <div class="info">
        <p><strong>Jenis Cuti:</strong> {{ $leave->leaveType->name }}</p>
        <p><strong>Periode:</strong> {{ $leave->start_date->format('d M Y') }} s/d {{ $leave->end_date->format('d M Y') }}</p>
        <p><strong>Status:</strong> {{ ucfirst($leave->status) }}</p>
    </div>

    <p>Laporan akhir cuti dalam bentuk PDF dilampirkan pada email ini.<br>
    Silakan simpan sebagai arsip pribadi Anda.</p>

    <p>Terima kasih,<br>
    <strong>HR & GA Department</strong><br>
    PT. Fluid Service Solution</p>
</body>
</html>
