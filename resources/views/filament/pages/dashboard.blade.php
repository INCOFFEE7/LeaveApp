<x-filament::page>
    <div class="space-y-4">
        @foreach (static::getWidgets() as $widget)
            @livewire($widget)
        @endforeach
    </div>
</x-filament::page>
