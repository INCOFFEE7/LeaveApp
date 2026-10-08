<x-filament::page>
    <form wire:submit.prevent="submit" class="space-y-6">


        {{-- 🧩 Form bawaan Filament --}}
        {{ $this->form }}

        {{-- 💾 Tombol simpan --}}
        <div class="pt-4">
            <x-filament::button type="submit" wire:loading.attr="disabled">
                <x-filament::loading-indicator wire:loading class="mr-2 h-4 w-4" />
                Simpan
            </x-filament::button>
        </div>
    </form>

    {{-- 🔄 Reload avatar di pojok kanan setelah update --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('profile-updated', () => {
                const navbarAvatar = document.querySelector('img.fi-user-avatar');
                if (navbarAvatar) {
                    navbarAvatar.src = navbarAvatar.src.split('?')[0] + '?v=' + Date.now();
                }
                setTimeout(() => window.location.reload(), 800);
            });
        });
    </script>
</x-filament::page>
