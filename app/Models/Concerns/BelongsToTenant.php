<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function ($model): void {
            if (! $model->tenant_id && TenantContext::id()) {
                $model->tenant_id = TenantContext::id();
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenantId = TenantContext::id();

            if ($tenantId !== null) {
                $builder->where($builder->getModel()->getTable().'.tenant_id', $tenantId);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
