<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

trait SaasModel
{
  public static function booted(): void
  {
    parent::booted();

    static::creating(function ($model) {
      $userId = auth()->check() ? auth()->id() : 0;
      $model->setAttribute('created_by', $userId);
    });

    static::updating(function ($model) {
      $userId = auth()->check() ? auth()->id() : 0;
      $model->setAttribute('updated_by', $userId);
    });
  }

  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(\App\Models\User::class, 'created_by');
  }

  public function updatedBy(): BelongsTo
  {
    return $this->belongsTo(\App\Models\User::class, 'updated_by');
  }

  public static function isUsingActionBy(): bool
  {
    return true;
  }

  public static function isUsingSoftDelete(): bool
  {
    return in_array(SoftDeletes::class, class_uses((new static)), true)
      && ! (new static)->forceDeleting;
  }
}
