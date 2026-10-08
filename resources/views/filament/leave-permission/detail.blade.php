@php
$statusClasses = match($record->status) {
    'pending' => 'bg-yellow-100 text-yellow-800',
    'in_progress' => 'bg-blue-100 text-blue-800',
    'approved' => 'bg-green-100 text-green-800',
    'rejected' => 'bg-red-100 text-red-800',
    default => 'bg-gray-100 text-gray-800'
};
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
    <div><strong>Nama Karyawan:</strong> {{ $record->worker->user->name ?? '-' }}</div>
    <div><strong>NIK:</strong> {{ $record->worker->user->payroll_number ?? '-' }}</div>
    <div><strong>Departemen:</strong> {{ $record->worker->department->department_name ?? '-' }}</div>
    <div><strong>Site:</strong> {{ $record->worker->site ?? '-' }}</div>
    <div><strong>Jenis Cuti:</strong> {{ $record->leaveType->leave_type_name ?? '-' }}</div>
    <div><strong>Tanggal Mulai:</strong> {{ $record->leave_date_from?->format('d M Y') ?? '-' }}</div>
    <div><strong>Tanggal Selesai:</strong> {{ $record->leave_date_to?->format('d M Y') ?? '-' }}</div>
    <div><strong>Total Hari Cuti:</strong> {{ $record->total_leave ?? '0' }} hari</div>
    <div><strong>Sisa Cuti:</strong> {{ $record->worker->leaveBalances->sortByDesc('year')->first()?->sisa_cuti ?? 0 }} hari</div>
    <div>
        <strong>Status:</strong>
        <span class="inline-block px-2 py-1 rounded {{ $statusClasses }}">
            {{ ucfirst($record->status) }}
        </span>
    </div>

    <div class="col-span-1 md:col-span-2">
        <strong>Catatan:</strong> {{ $record->note ?? '-' }}
    </div>

    <div class="col-span-1 md:col-span-2">
    <strong>Dokumen Pendukung:</strong>
    @if($record->supporting_document)
        <a href="{{ asset('storage/' . $record->supporting_document) }}"
           target="_blank"
           class="inline-block px-3 py-1 bg-blue-100 text-blue-800 font-semibold rounded hover:bg-blue-200 transition">
           Lihat Dokumen
        </a>
    @else
        <span>-</span>
    @endif
</div>
</div>
