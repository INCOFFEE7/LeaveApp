<x-filament-panels::page>
    <div class="space-y-4">
        @forelse ($notifications as $notif)
            <div class="p-4 border rounded-lg @if(!$notif->is_read) bg-blue-50 @endif">
                <h4 class="font-semibold">{{ $notif->title }}</h4>
                <p class="text-sm text-gray-600">{{ $notif->message }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $notif->created_at->diffForHumans() }}</p>
            </div>
        @empty
            <p class="text-gray-500">Tidak ada notifikasi.</p>
        @endforelse
    </div>
</x-filament-panels::page>
