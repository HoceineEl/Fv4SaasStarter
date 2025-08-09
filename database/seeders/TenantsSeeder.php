<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantsSeeder extends Seeder
{
  public function run(): void
  {
    $name = 'منشأة تجريبية';
    $slug = 'demo';

    Tenant::firstOrCreate(
      ['slug' => $slug],
      ['name' => $name]
    );
  }
}
