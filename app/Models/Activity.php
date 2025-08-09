<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\SaasModel;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
  use SaasModel;
  protected $fillable = [
    'tenant_id',
  ];

  protected static function booted(): void
  {
    static::creating(function (Activity $activity): void {
      if (is_null($activity->tenant_id)) {
        $tenantId = Filament::getTenant()?->id;
        if ($tenantId) {
          $activity->tenant_id = $tenantId;
        }
      }
    });

    if (app()->runningInConsole()) {
      return;
    }

    $panelId = Filament::getCurrentPanel()?->getId();
    if ($panelId !== 'super-admin') {
      static::addGlobalScope('tenant', function (Builder $query): void {
        $tenantId = Filament::getTenant()?->id;
        if ($tenantId) {
          $query->where('tenant_id', $tenantId);
        }
      });
    }
  }
}
