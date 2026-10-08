<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sistem Pengajuan Cuti | PT. Fluid Service Solution</title>

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    body { font-family: 'Inter', sans-serif; }
  </style>
</head>

<body class="bg-white text-slate-800">

  <!-- Header -->
  <header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
      <div class="text-xl font-bold text-orange-600">
        PT. Fluid Service Solution
      </div>

      @guest
        <a href="{{ route('login') }}"
           class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-semibold transition">
          Masuk
        </a>
      @else
        <div x-data="{ open: false }" class="relative">
          <button x-on:click="open = !open"
                  class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm bg-gray-100 hover:bg-gray-200 text-gray-700">
            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M5.121 17.804A4 4 0 018 16h8a4 4 0 013 1.804M12 12a4 4 0 100-8 4 4 0 000 8z"/>
            </svg>
            <span>Halo, {{ Auth::user()->name }}</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>

          <!-- Dropdown -->
          <div x-show="open" @click.away="open = false" x-transition
               class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-md border z-50">
            <ul class="py-1 text-sm text-gray-700">
              <li>
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button type="submit"
                          class="w-full text-left px-4 py-2 hover:bg-gray-100 text-red-600 transition">
                    Keluar
                  </button>
                </form>
              </li>
            </ul>
          </div>
        </div>
      @endguest
    </div>
  </header>

  <!-- Hero Section -->
  <section class="bg-gradient-to-b from-white via-orange-50 to-orange-100 min-h-[80vh] flex items-center">
    <div class="max-w-7xl mx-auto px-6 py-20 grid md:grid-cols-2 gap-10 items-center">
      <div class="space-y-6">
        <h1 class="text-4xl md:text-5xl font-extrabold text-orange-600 leading-tight">
          Sistem Pengajuan Cuti PT. Fluid Service Solution
        </h1>
        <p class="text-lg text-slate-700 leading-relaxed">
          Ajukan cuti dengan mudah dan cepat melalui sistem digital kami yang transparan dan terintegrasi.
        </p>

        @guest
          <a href="{{ route('login') }}"
             class="inline-block bg-orange-600 hover:bg-orange-700 text-white font-semibold px-6 py-3 rounded-lg transition">
            Masuk ke Sistem
          </a>
        @else
          @php
            $permissionToDashboard = [
                'dashboard.view.admin'   => \App\Filament\Pages\Dashboard::getUrl(panel: 'main'),
                'dashboard.view.employee'  => \App\Filament\Pages\Dashboard::getUrl(panel: 'main'),
                'dashboard.view.general' => \App\Filament\Pages\Dashboard::getUrl(panel: 'main'),
            ];
            $userPermissions = auth()->user()?->getAllPermissions()->pluck('name') ?? collect();
            $availableDashboards = $userPermissions->intersect(array_keys($permissionToDashboard));
          @endphp

          @if($availableDashboards->count() === 1)
              @php $perm = $availableDashboards->first(); @endphp
              <a href="{{ $permissionToDashboard[$perm] }}"
              class="inline-block bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-3 rounded-lg transition">
                  Masuk ke {{ ucfirst(str_replace('dashboard.view.', '', $perm)) }} Dashboard
              </a>
          @elseif($availableDashboards->count() > 1)
              <div class="space-y-2">
                  <p class="text-slate-700 font-medium">Pilih dashboard:</p>
                  @foreach($availableDashboards as $perm)
                      <a href="{{ $permissionToDashboard[$perm] }}"
                      class="block bg-orange-500 hover:bg-orange-600 text-white px-5 py-2 rounded shadow transition text-center">
                          Masuk ke {{ ucfirst(str_replace('dashboard.view.', '', $perm)) }} Dashboard
                      </a>
                  @endforeach
              </div>
          @else
              <p class="text-slate-600">Anda tidak memiliki akses ke dashboard manapun.</p>
          @endif
        @endguest
      </div>

      <!-- Hero Image -->
      <div class="hidden md:block">
        <img src="{{ asset('images/a-day-off-pana.svg') }}" alt="Ilustrasi Liburan"
             class="w-full max-w-md mx-auto rounded-xl shadow-lg">
      </div>
    </div>
  </section>

  <!-- Fitur Utama -->
  <section class="bg-white py-24 px-4 sm:px-6">
    <div class="max-w-7xl mx-auto text-center">
      <h2 class="text-3xl font-bold text-slate-800 mb-14">Fitur Utama</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
        <div class="bg-white border border-orange-100 p-6 rounded-2xl shadow-sm hover:shadow-md transition text-left">
          <h3 class="text-xl font-semibold text-orange-600 mb-2">Pengajuan Cuti Online</h3>
          <p class="text-slate-600">Ajukan cuti dari mana saja tanpa harus datang langsung atau menggunakan dokumen fisik.</p>
        </div>
        <div class="bg-white border border-orange-100 p-6 rounded-2xl shadow-sm hover:shadow-md transition text-left">
          <h3 class="text-xl font-semibold text-orange-600 mb-2">Verifikasi Berjenjang</h3>
          <p class="text-slate-600">Permohonan cuti diproses otomatis sesuai dengan struktur atasan yang telah ditentukan.</p>
        </div>
        <div class="bg-white border border-orange-100 p-6 rounded-2xl shadow-sm hover:shadow-md transition text-left">
          <h3 class="text-xl font-semibold text-orange-600 mb-2">Dashboard Real-time</h3>
          <p class="text-slate-600">Lihat status cuti, riwayat persetujuan, dan rekap karyawan dalam satu tempat.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="bg-orange-50 py-16 px-4 sm:px-6">
    <div class="max-w-4xl mx-auto text-center space-y-6">
      <h3 class="text-3xl font-bold text-orange-700">Kelola cuti lebih efisien dan terintegrasi</h3>
      <p class="text-slate-700 text-lg leading-relaxed pb-8">Gunakan sistem kami untuk meningkatkan produktivitas dan transparansi di lingkungan kerja.</p>
      @guest
        <a href="{{ route('login') }}"
           class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-lg font-semibold transition">
          Masuk Sekarang
        </a>
      @endguest
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-slate-100 border-t py-6 text-center text-slate-600 text-sm">
    © {{ date('Y') }} PT FSS. Sistem Informasi Pengajuan Cuti.
  </footer>

</body>
</html>
