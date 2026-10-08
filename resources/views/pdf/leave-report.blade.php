<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: "Times New Roman", serif;
            font-size: 11pt;
            line-height: 1.35;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 6px 7px;
            vertical-align: top;
        }

        .border {
            border: 1px solid #000;
        }

        .center { text-align: center; }
        .right  { text-align: right; }
        .bold   { font-weight: bold; }
        .small  { font-size: 9pt; }

        .title {
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 4px;
        }

        /* Approval */
        .approval td {
            text-align: center;
            vertical-align: bottom;
            height: 110px;
            padding-top: 30px;
        }

        .line {
            border-top: 1px solid #000;
            margin: 6px 12px 4px;
        }

        .qr {
            margin-top: 4px;
        }

        .section {
            margin-top: 8px;
        }
    </style>
</head>
<body>

@php
    $leaveType = strtolower($leave->leaveType->leave_type_name ?? '');

    $reasons = [
        'field_break' => str_contains($leaveType, 'field'),
        'leave'    => str_contains($leaveType, 'annual') || str_contains($leaveType, 'tahunan'),
    ];

    $reasons['others'] = ! $reasons['field_break']
        && ! $reasons['leave'];
@endphp

<!-- ================= HEADER ================= -->
<table class="border">
    <tr>
        <td class="bold">PT. FLUID SERVICE SOLUTION</td>
    </tr>
</table>

<table class="border">
    <tr>
        <td class="center title" style="margin-left:1rem">PERMIT SLIP</td>
        <td class="right bold small" width="20%">HRD-09</td>
    </tr>
</table>

<!-- ================= INFORMASI UTAMA ================= -->
<table class="border section">
    <tr>
        <td width="32%">
            <strong><u>PERMISSION IS HEREBY GRANTED TO</u></strong><br>
            <span class="small">IZIN DIBERIKAN KEPADA</span>
        </td>
        <td width="38%">: {{ $leave->worker->user->name }}</td>
        <td width="10%" class="bold">NIK</td>
        <td width="20%">: {{ $leave->worker->user->payroll_number }}</td>
    </tr>

    <tr>
        <td>
            <strong><u>CLASSIFICATION</u></strong><br>
            <span class="small">JABATAN</span>
        </td>
        <td>: {{ $leave->worker->position }}</td>
        <td colspan="2"></td>
    </tr>

    <tr>
        <td>
            <strong><u>DATE OF HIRE</u></strong><br>
            <span class="small">TANGGAL DITERIMA BEKERJA</span>
        </td>
        <td>: {{ \Carbon\Carbon::parse($leave->worker->date_of_hire)->format('d M Y') }}</td>
        <td colspan="2"></td>
    </tr>
</table>

<!-- ================= ALASAN ================= -->
<table class="border section">
    <tr>
        <td width="32%">
            <strong><u>REASON FOR BEING ABSENT</u></strong><br>
            <span class="small">ALASAN TIDAK MASUK KERJA</span>
        </td>
        <td width="68%" class="small">
            [{{ $reasons['field_break'] ? 'X' : ' ' }}] FIELD BREAK &nbsp;&nbsp;
            [{{ $reasons['leave'] ? 'X' : ' ' }}] LEAVE &nbsp;&nbsp;
            [{{ $reasons['others'] ? 'X' : ' ' }}] OTHERS &nbsp;&nbsp;
            [{{ $reasons['vacation'] ? 'X' : ' ' }}] VACATION PAY
        </td>
    </tr>
    @if($reasons['field_break'] && $leave->on_site_from && $leave->on_site_to)
    <tr>
        <td class="small">
            <span class="label">ON SITE PERIOD:</span><br>
            {{ \Carbon\Carbon::parse($leave->on_site_from)->format('d M Y') }}
            s/d
            {{ \Carbon\Carbon::parse($leave->on_site_to)->format('d M Y') }}
        </td>
    </tr>
    @endif
</table>

<!-- ================= PERIODE ================= -->
<table class="border section">
    <tr>
        <td width="32%">
            <strong><u>TO BE ABSENT FROM</u></strong><br>
            <span class="small">TIDAK MASUK KERJA SEJAK</span>
        </td>
        <td width="68%">
            : {{ \Carbon\Carbon::parse($leave->leave_date_from)->format('d M Y') }}
            s/d
            {{ \Carbon\Carbon::parse($leave->leave_date_to)->format('d M Y') }}
        </td>
    </tr>

    <tr>
        <td>
            <strong><u>DATE REPORTING BACK</u></strong><br>
            <span class="small">TANGGAL MELAPOR KEMBALI</span>
        </td>
        <td>
            :
            {{ $leave->return_to_work_date
                ? \Carbon\Carbon::parse($leave->return_to_work_date)->format('d M Y')
                : '-' }}
        </td>
    </tr>
</table>

<!-- ================= PERSETUJUAN ================= -->
<table class="border approval section">
    <tr>
        <td colspan="3" class="small center">
            <strong><u>RECOMMENDED FOR APPROVAL</u></strong><br>
            DIKETAHUI DAN DISETUJUI OLEH<br><br>
            BALIKPAPAN, {{ \Carbon\Carbon::now()->format('d M Y') }}
        </td>
    </tr>

    @php
        $approvals = $leave->approvals
            ->whereNotNull('approved_at')
            ->sortBy('approved_at')
            ->values();
    @endphp

    <tr>
        @foreach ($approvals->take(3) as $index => $a)
            @php
                $label = match ($index) {
                    0 => 'DEPT HEAD / ATASAN LANGSUNG',
                    1 => 'LEADER SITE / PJO',
                    2 => 'HR & GA MANAGER',
                };

                $qrBase64 = null;
                if (!empty($a->qrPath) && file_exists($a->qrPath)) {
                    $qrBase64 = base64_encode(file_get_contents($a->qrPath));
                }
            @endphp

            <td width="33%">
                <span class="small">{{ $label }}</span><br><br>

                @if($qrBase64)
                    <img src="data:image/png;base64,{{ $qrBase64 }}" width="65" class="qr">
                @endif

                <div class="line"></div>

                <strong class="small">{{ strtoupper($a->approver->name) }}</strong><br>
                <span class="small">{{ strtoupper($a->approver->getRoleNames()->first()) }}</span>
            </td>
        @endforeach

        @for ($i = $approvals->count(); $i < 3; $i++)
            <td width="33%">
                <span class="small">&nbsp;</span><br><br>
                <div class="line"></div>
            </td>
        @endfor
    </tr>
</table>

<!-- ================= NOTE ================= -->
<table class="border section">
    <tr>
        <td class="small">
            <strong>NOTE:</strong><br><u>
            ABSENCE FROM WORKING WITHOUT PERMISSION WILL SUBJECT EMPLOYEE TO
            DISCIPLINARY ACTION SUSPENSION OR TERMINATION</u><br>
            <em>TIDAK HADIR KERJA TANPA IZIN AKAN DIAMBIL TINDAKAN SKORSING ATAU DIBERHENTIKAN</em>
        </td>
    </tr>
</table>

<!-- ================= CC ================= -->
<table class="border section">
    <tr>
        <td class="small">
            <strong>CC:</strong><br>
            HRGA DEPT.<br>
            DEPT. HEAD<br>
            EMPLOYEE
        </td>
    </tr>
</table>

</body>
</html>
