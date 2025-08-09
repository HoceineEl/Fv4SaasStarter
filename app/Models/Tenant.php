<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\SaasModel;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Tenant extends Model implements HasName
{
  use HasFactory, LogsActivity, SaasModel;

  protected $fillable = [
    'name',
    'slug',
  ];

  public function getActivitylogOptions(): LogOptions
  {
    return LogOptions::defaults()
      ->logAll()
      ->logOnlyDirty()
      ->useLogName('tenants');
  }

  public function users(): BelongsToMany
  {
    return $this->belongsToMany(User::class);
  }

  public function getFilamentName(): string
  {
    return (string) $this->name;
  }
}
