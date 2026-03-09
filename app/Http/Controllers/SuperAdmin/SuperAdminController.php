<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\LaundryOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function dashboard(): View
    {
        $tenants = Tenant::query()
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $tenantPerformance = LaundryOrder::query()
            ->withoutGlobalScopes()
            ->select('tenant_id', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(grand_total) as omzet'))
            ->groupBy('tenant_id')
            ->get()
            ->keyBy('tenant_id');

        $allUsers = User::withoutGlobalScopes()
            ->with('tenant')
            ->orderBy('tenant_id')
            ->orderBy('name')
            ->get();

        $orderLast30 = LaundryOrder::withoutGlobalScopes()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $totalUsers = User::withoutGlobalScopes()->count();
        $totalTenants = Tenant::count();

        $activeUsers = UserActivityLog::query()
            ->select('user_id', DB::raw('COUNT(*) as hits'), DB::raw('MAX(created_at) as last_seen'))
            ->whereNotNull('user_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('user_id')
            ->orderByDesc('hits')
            ->with('user')
            ->take(10)
            ->get()
            ->map(function (UserActivityLog $row): UserActivityLog {
                $row->last_seen = $row->last_seen ? Carbon::parse($row->last_seen) : null;

                return $row;
            });

        $currentTenant = TenantContext::tenant();

        return view('superadmin.dashboard', [
            'tenants' => $tenants,
            'tenantPerformance' => $tenantPerformance,
            'activeUsers' => $activeUsers,
            'allUsers' => $allUsers,
            'currentTenant' => $currentTenant,
            'orderLast30' => $orderLast30,
            'totalUsers' => $totalUsers,
            'totalTenants' => $totalTenants,
        ]);
    }

    public function impersonate(Request $request): RedirectResponse
    {
        $request->validate([
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
        ]);

        $tenantId = $request->integer('tenant_id') ?: null;
        $request->session()->put('impersonated_tenant_id', $tenantId);

        return redirect()->route('superadmin.dashboard')
            ->with('status', $tenantId ? 'Tenant diganti.' : 'Mode global (semua tenant).');
    }

    public function storeTenant(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:80', 'alpha_dash', 'unique:tenants,slug'],
            'plan' => ['nullable', 'string', 'max:60'],
        ]);

        Tenant::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'plan' => $data['plan'] ?? null,
            'status' => 'active',
        ]);

        return back()->with('success', 'Tenant baru dibuat.');
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'in:owner,admin,kasir,operator'],
            'password' => ['required', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        User::query()->updateOrCreate(
            ['email' => $data['email'], 'tenant_id' => $data['tenant_id']],
            [
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'role' => $data['role'],
                'is_active' => $request->boolean('is_active', true),
                'password' => Hash::make($data['password']),
            ]
        );

        return back()->with('success', 'User berhasil dibuat/diupdate.');
    }
}
