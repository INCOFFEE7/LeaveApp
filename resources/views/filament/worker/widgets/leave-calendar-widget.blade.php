<x-filament-widgets::widget>
    <x-filament::section heading="Kalender Cuti & Hari Libur">
        <div id="calendar" class="rounded-lg border dark:border-gray-700 p-4 bg-white dark:bg-gray-900"></div>
    </x-filament::section>

    @push('scripts')
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {

                const calendarEl = document.getElementById('calendar');
                if (!calendarEl) return;

                // Gabungkan events cuti + libur
                const events = [
                    ...@json($leaves ?? []),
                    ...@json($holidays ?? [])
                ];

                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    height: 600,
                    locale: 'id',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,listWeek'
                    },
                    events: events,
                    nowIndicator: true,
                    eventDisplay: 'block',
                    eventTextColor: '#fff',
                });

                calendar.render();
            });
        </script>
    @endpush
</x-filament-widgets::widget>
