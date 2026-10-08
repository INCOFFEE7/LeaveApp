<x-filament::page>

    {{-- Profil Karyawan --}}
    <x-filament::section heading="Profil Karyawan">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-filament::card>
                <div class="space-y-2">
                    <p class="text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center">
                        <x-heroicon-o-user class="w-5 h-5 mr-2 text-primary-600 dark:text-primary-400" />
                        {{ $worker->worker_name ?? 'Nama tidak ditemukan' }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        NIP/Payroll: <span class="font-semibold">{{ $worker->payroll_number ?? '-' }}</span>
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Departemen: <span class="font-semibold">{{ $worker->department->department_name ?? '-' }}</span>
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Bergabung sejak:
                        <span class="font-semibold">
                            {{ \Carbon\Carbon::parse($worker->join_date ?? now())->translatedFormat('d F Y') }}
                        </span>
                    </p>
                </div>
            </x-filament::card>

            {{-- Saldo Cuti --}}
            <x-filament::card>
                <div class="space-y-4">
                    <p class="text-lg font-bold text-gray-800 dark:text-gray-100 flex items-center">
                        <x-heroicon-o-briefcase class="w-5 h-5 mr-2 text-primary-600 dark:text-primary-400" />
                        Saldo Cuti Tahun {{ now()->year }}
                    </p>
                    <div class="grid grid-cols-3 text-center">
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $leaveBalances['hak_cuti'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Total</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-yellow-500">{{ $leaveBalances['terpakai'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Terpakai</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-green-600">{{ $leaveBalances['sisa_cuti'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Sisa</p>
                        </div>
                    </div>
                </div>
            </x-filament::card>
        </div>
    </x-filament::section>

    {{-- Statistik Pengajuan Cuti --}}
    <x-filament::section heading="Statistik Pengajuan Cuti">
        <div class="grid grid-cols-3 text-center gap-4">
            <div class="p-4 bg-yellow-50 rounded-lg">
                <p class="text-2xl font-bold text-yellow-500">{{ $leaveStats['pending'] ?? 0 }}</p>
                <p class="text-xs text-gray-500">Menunggu</p>
            </div>
            <div class="p-4 bg-green-50 rounded-lg">
                <p class="text-2xl font-bold text-green-600">{{ $leaveStats['approved'] ?? 0 }}</p>
                <p class="text-xs text-gray-500">Disetujui</p>
            </div>
            <div class="p-4 bg-red-50 rounded-lg">
                <p class="text-2xl font-bold text-red-600">{{ $leaveStats['rejected'] ?? 0 }}</p>
                <p class="text-xs text-gray-500">Ditolak</p>
            </div>
        </div>
    </x-filament::section>

    {{-- Kalender Cuti --}}
    <x-filament::section heading="Kalender Cuti">
        <div id="calendar" class="rounded-lg border dark:border-gray-700 p-4 bg-white dark:bg-gray-900"></div>
    </x-filament::section>

    {{-- Riwayat Cuti Terakhir --}}
    <x-filament::section heading="Riwayat Pengajuan Terakhir">
        @if(!empty($recentLeaveRequests) && count($recentLeaveRequests) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Tanggal</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Status</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($recentLeaveRequests as $item)
                            <tr>
                                <td class="px-4 py-2">{{ $item['tanggal'] }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs rounded-full
                                        @if($item['status'] === 'Approved') bg-green-100 text-green-700
                                        @elseif($item['status'] === 'Pending') bg-yellow-100 text-yellow-700
                                        @elseif($item['status'] === 'Rejected') bg-red-100 text-red-700
                                        @else bg-gray-100 text-gray-700
                                        @endif">
                                        {{ $item['status'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">{{ $item['keterangan'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada pengajuan cuti.</p>
        @endif
    </x-filament::section>

    {{-- FullCalendar --}}
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const calendarEl = document.getElementById('calendar');
                const events = @json($calendarEvents ?? []);

                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    height: 550,
                    locale: 'id',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,listWeek'
                    },
                    events: events,
                    eventDisplay: 'block',
                    nowIndicator: true,
                    eventColor: '#3b82f6',
                    eventTextColor: '#fff',
                });

                calendar.render();

                window.addEventListener('calendarUpdated', event => {
                    calendar.removeAllEventSources();
                    calendar.addEventSource(event.detail.events);
                    calendar.refetchEvents();
                });
            });
        </script>
    @endpush

</x-filament::page>
