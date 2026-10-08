<x-guest-layout>
    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <div class="text-center">
            <h1 class="text-2xl font-semibold text-gray-800">
                Masuk ke <span class="text-indigo-600">Sistem Cuti</span>
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Gunakan email atau nomor payroll Anda
            </p>
        </div>

        <!-- Email -->
        <div>
            <x-input-label for="login" value="Email atau Nomor Payroll" />
            <div class="flex items-center border rounded-md bg-white shadow-sm focus-within:ring-2 focus-within:ring-indigo-500">
                <span class="px-3 text-gray-500">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5.121 17.804A13.937 13.937 0 0112 15c2.486 0 4.779.676 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </span>
                <input id="login" name="login" type="text" required autofocus autocomplete="username"
                    class="w-full px-3 py-2 bg-transparent focus:outline-none text-gray-900 placeholder-gray-400" />
            </div>
            <x-input-error :messages="$errors->get('login')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Password" />
            <div class="flex items-center border rounded-md bg-white shadow-sm focus-within:ring-2 focus-within:ring-indigo-500">
                <span class="px-3 text-gray-500">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 11c1.657 0 3 1.343 3 3v1H9v-1c0-1.657 1.343-3 3-3z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 13l1 9h12l1-9" />
                    </svg>
                </span>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                    class="w-full px-3 py-2 bg-transparent focus:outline-none text-gray-900 placeholder-gray-400" />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <input id="remember_me" type="checkbox"
                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
            <label for="remember_me" class="ms-2 text-sm text-gray-700">
                Ingat saya
            </label>
        </div>

        <!-- Button -->
        <div>
            <x-primary-button class="w-full justify-center bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 transition duration-150 ease-in-out">
                MASUK
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
