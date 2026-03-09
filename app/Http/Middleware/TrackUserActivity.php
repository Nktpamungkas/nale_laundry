<?php

namespace App\Http\Middleware;

use App\Models\UserActivityLog;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Log lightweight activity + update last_active_at.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if (! $user) {
            return $response;
        }

        $now = now();
        if (! $user->last_active_at || $user->last_active_at->lt($now->copy()->subMinutes(5))) {
            $user->forceFill(['last_active_at' => $now])->saveQuietly();
        }

        $path = $request->path();
        // Batasi logging untuk area admin/superadmin agar tabel tidak membengkak.
        if (Str::startsWith($path, ['admin', 'superadmin'])) {
            UserActivityLog::query()->create([
                'tenant_id' => TenantContext::id(),
                'user_id' => $user->id,
                'action' => 'page_view',
                'path' => '/'.$path,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'meta' => [
                    'method' => $request->method(),
                ],
            ]);
        }

        return $response;
    }
}
