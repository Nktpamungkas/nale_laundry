<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\UserActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'tenant' => ['nullable', 'string'],
        ]);

        $tenantSlug = trim((string) ($credentials['tenant'] ?? ''));
        $tenant = null;

        if ($tenantSlug !== '') {
            $tenant = Tenant::query()->where('slug', $tenantSlug)->first();

            if (! $tenant) {
                return back()->withErrors([
                    'tenant' => 'Tenant tidak ditemukan.',
                ])->onlyInput('email', 'tenant');
            }
        }

        $attempt = [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'is_active' => true,
            'tenant_id' => $tenant?->id,
        ];

        if (! Auth::attempt($attempt, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Email atau password tidak valid.',
            ])->onlyInput('email', 'tenant');
        }

        if (! $request->user()?->hasBackofficeAccess()) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'Akun ini tidak memiliki akses backoffice.',
            ])->onlyInput('email', 'tenant');
        }

        // Simpan tenant ke session agar middleware SetCurrentTenant dapat membaca.
        if ($request->user()?->isSuperAdmin()) {
            $request->session()->forget('impersonated_tenant_id');
        } else {
            $request->session()->put('impersonated_tenant_id', $request->user()->tenant_id);
        }

        $request->user()->forceFill(['last_login_at' => now()])->saveQuietly();

        UserActivityLog::query()->create([
            'tenant_id' => $request->user()->tenant_id,
            'user_id' => $request->user()->id,
            'action' => 'login',
            'path' => '/login',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->forget('impersonated_tenant_id');

        return redirect()->route('login');
    }
}
