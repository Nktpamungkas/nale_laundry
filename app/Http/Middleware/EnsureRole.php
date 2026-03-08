<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $allowedRoles = collect($roles)
            ->map(fn (string $role) => trim($role))
            ->filter()
            ->values()
            ->all();

        if (! $user->hasAnyRole($allowedRoles)) {
            abort(403, 'Anda tidak memiliki hak akses ke modul ini.');
        }

        return $next($request);
    }
}
