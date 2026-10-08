<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AuthenticatedSessionController extends Controller
{
    /**
     * Tampilkan halaman login.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Proses autentikasi login.
     */
    public function store(Request $request): RedirectResponse
    {
        // Backwards-compatible: accept either 'login' or 'email' from tests/scaffolding
        if (! $request->has('login') && $request->has('email')) {
            $request->merge(['login' => $request->input('email')]);
        }

        // Validasi input login
            // Accept either 'login' or legacy 'email' key (tests and some callers use 'email')
            $request->validate([
                'password' => ['required', 'string'],
            ]);

            // Prefer 'login' field; fall back to 'email' if provided
            $loginInput = $request->input('login') ?? $request->input('email');

            if (empty($loginInput)) {
                $request->validate([
                    'login' => ['required', 'string'],
                ]);
            }

            // Cek apakah input login adalah email atau payroll number
            $loginType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'payroll_number';

            // Autentikasi pengguna
            if (! Auth::attempt([
                $loginType => $loginInput,
                'password' => $request->input('password'),
            ], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        // Regenerasi session & buang URL redirect sebelumnya
    $request->session()->regenerate();
    $request->session()->forget('url.intended');

        // 🔑 Semua role masuk ke MainPanel
    return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Handle logout.
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/'); // arahkan ke landing page
    }
}
