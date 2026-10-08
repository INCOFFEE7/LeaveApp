<x-filament-widgets::widget>
    <x-filament::section heading="Kalender Cuti Saya">
        <div id="calendar" class="rounded-lg border dark:border-gray-700 p-4 bg-white dark:bg-gray-900"></div>
    </x-filament::section>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const calendarEl = document.getElementById('calendar');

                // Ambil data dari variabel Blade (dikirim dari widget class)
                const events = @json($events ?? []);

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
                    eventColor: '#3b82f6',
                    eventTextColor: '#fff',
                    nowIndicator: true,
                });

                calendar.render();
            });
        </script>
    @endpush
</x-filament-widgets::widget>
