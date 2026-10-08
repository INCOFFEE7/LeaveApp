@props([
    'events' => [],
    'yearModel' => 'year',
])

<div class="flex items-center gap-2 mb-4">
    <label for="year" class="text-sm text-gray-600 dark:text-gray-300">Pilih Tahun:</label>
    <select id="year" wire:model="{{ $yearModel }}" class="form-select border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-md shadow-sm">
        @for ($i = now()->year; $i >= now()->year - 5; $i--)
            <option value="{{ $i }}">{{ $i }}</option>
        @endfor
    </select>
</div>

{{-- Legend --}}
<div class="flex items-center space-x-4 mb-3 text-sm text-gray-700 dark:text-gray-300">
    <div class="flex items-center space-x-1"><div class="w-4 h-4 bg-green-500 rounded-sm"></div><span>Disetujui</span></div>
    <div class="flex items-center space-x-1"><div class="w-4 h-4 bg-yellow-400 rounded-sm"></div><span>Menunggu</span></div>
    <div class="flex items-center space-x-1"><div class="w-4 h-4 bg-red-500 rounded-sm"></div><span>Ditolak</span></div>
    <div class="flex items-center space-x-1"><div class="w-4 h-4 border border-gray-400 dark:border-gray-300 rounded-sm"></div><span>Hari Ini</span></div>
</div>

<div id="leave-calendar" class="w-full max-w-7xl mx-auto rounded-md shadow ring-1 ring-gray-200 dark:ring-gray-700 bg-white dark:bg-gray-900 p-4"></div>

@push('styles')
<link rel="stylesheet" href="https://uicdn.toast.com/calendar/latest/toastui-calendar.min.css" />
<style>
        /* Tampilan hari ini */
        .toastui-calendar-cell.toastui-calendar-daygrid-cell--today {
            border: 2px dashed #9ca3af !important;
        }

        /* DARK MODE styling */
        html.dark .toastui-calendar {
            background-color: #11182700;
            color: #e5e7eb;
        }

        html.dark .toastui-calendar-month-daygrid-cell {
            border-color: #374151;
        }

        html.dark .toastui-calendar-day-name {
            background-color: #1f2937;
            color: #f3f4f6;
        }

        /* Tampilan nama hari */
        .toastui-calendar-day-name-container {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }

        .toastui-calendar-day-name {
            text-align: center;
            font-weight: 600;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
        }

        /* Layout grid kalender */
        .toastui-calendar-layout {
        background-color: transparent !important;
        }

        .toastui-calendar-month-daygrid {
            display: grid !important;
        }

        .toastui-calendar-month-daygrid-cell {
            min-height: 110px;
            border: 1px solid #e5e7eb;
            padding: 4px;
            box-sizing: border-box;
        }

        /* Hilangkan garis selection */
        .toastui-calendar-accumulated,
        .toastui-calendar-grid-selection {
            background-color: transparent !important;
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
        }

        /* Perbaikan event text */
        .toastui-calendar-event {
            display: block;
            font-size: 12px;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-height: 20px; /* untuk menghindari stacking */
            margin-bottom: 2px;
        }

        .toastui-calendar-template-monthDayName{
            font-size: 16px;
            font-weight: 100;
        }

        .toastui-calendar-weekday-events .toastui-calendar-more {
            display: none !important;
        }

        .toastui-calendar-see-more-container {
            height: fit-content !important;
        }

        /* Batas tinggi grid sel hari di bulan */
        .toastui-calendar-month-daygrid-cell {
            min-height: 80px;
            max-height: 120px;
            overflow: hidden;
            padding: 4px;
            border: 1px solid #e5e7eb;
            box-sizing: border-box;
        }

        /* Ukuran font dan padding untuk tanggal */
        .toastui-calendar-daygrid-cell-date {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 2px;
        }

        /* Batasi jumlah event ditampilkan per hari */
        .toastui-calendar-event {
            font-size: 12px;
            line-height: 1.2;
            max-height: 36px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .toastui-calendar-day-name-container, .toastui-calendar-day-names {
           position: static;
        }

        #leave-calendar {
            width: 100%;
            height: 20rem;
            overflow-x: auto;
        }
    </style>

@endpush

@push('scripts')
<script src="https://uicdn.toast.com/calendar/latest/toastui-calendar.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const calendar = new tui.Calendar('#leave-calendar', {
            defaultView: 'month',
            taskView: false,
            useFormPopup: false,
            useDetailPopup: true,
             month: {
                isAlways6Weeks: true,
                startDayOfWeek: 0,
                dayHeight: 150,
                maxEventsPerDay: null, // ⬅️ Tambahkan baris ini
            },
        });

        const events = @json($events);
        calendar.createEvents(events);

        window.addEventListener('calendarUpdated', event => {
            calendar.clear();
            calendar.createEvents(event.detail.events);
            calendar.render(true);
        });

        calendar.setDate(new Date());
    });

    calendar.setDate(new Date());

    calendar.on('clickEvent', function(event) {
        if(event.event.url) {
            window.open(event.event.url, '_blank');
        }
    });

    document.getElementById('year').addEventListener('change', function() {
    Livewire.emit('refreshCalendar', this.value);
    });
</script>
@endpush
