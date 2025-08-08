<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait ScopedByTenant
{
  protected static string $tenantForeignKey = 'tenant_id';

  public static function bootScopedByTenant(): void
  {
    static::addGlobalScope(static::getTenantScopeName(), function (Builder $query): void {
      if (! Filament::hasTenancy()) {
        return;
      }

      $tenant = Filament::getTenant();

      if ($tenant === null) {
        return;
      }

      $query->where(
        $query->getModel()->getTable() . '.' . static::$tenantForeignKey,
        $tenant->getKey()
      );
    });

    static::creating(function (Model $model): void {
      if (! Filament::hasTenancy()) {
        return;
      }

      $tenant = Filament::getTenant();

      if ($tenant === null) {
        return;
      }

      if (empty($model->getAttribute(static::$tenantForeignKey))) {
        $model->setAttribute(static::$tenantForeignKey, $tenant->getKey());
      }
    });
  }
  public function tenant(): BelongsTo
  {
    return $this->belongsTo(\App\Models\Tenant::class);
  }
  protected static function getTenantScopeName(): string
  {
    return Filament::getTenancyScopeName();
  }
}
