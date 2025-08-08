<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tenant extends Model implements HasName
{
  use HasFactory;

  protected $fillable = [
    'name',
    'slug',
  ];

  public function users(): BelongsToMany
  {
    return $this->belongsToMany(User::class);
  }

  public function getFilamentName(): string
  {
    return (string) $this->name;
  }
}
