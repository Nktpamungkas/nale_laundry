<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentTenant
{
    /**
     * Resolve tenant for authenticated user and set shared context.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            TenantContext::forget();

            return $next($request);
        }

        if ($user->isSuperAdmin()) {
            $impersonatedTenantId = $request->session()->get('impersonated_tenant_id');
            $tenant = $impersonatedTenantId
                ? Tenant::query()->find($impersonatedTenantId)
                : null;

            if (! $tenant && ($request->is('admin/*') || $request->is('admin'))) {
                return redirect()->route('superadmin.dashboard')
                    ->with('error', 'Pilih tenant dulu sebelum masuk area admin.');
            }
        } else {
            $tenant = $user->tenant;

            if (! $tenant) {
                abort(403, 'Tenant tidak ditemukan untuk akun ini.');
            }
        }

        TenantContext::set($tenant);

        $response = $next($request);

        TenantContext::forget();

        return $response;
    }
}
