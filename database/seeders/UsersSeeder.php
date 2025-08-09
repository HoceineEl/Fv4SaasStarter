<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
  public function run(): void
  {
    $tenant = Tenant::first();
    if (! $tenant) {
      return;
    }

    $user = User::firstOrCreate(
      ['email' => 'admin@example.com'],
      [
        'name' => 'Super Admin',
        'password' => Hash::make('password'),
      ]
    );

    $user->tenants()->syncWithoutDetaching([$tenant->id]);
    $user->latestTenant()->associate($tenant);
    $user->save();

    if (method_exists($user, 'assignRole')) {
      if (config('permission.teams')) {

        setPermissionsTeamId($tenant->id);
      }
      $user->assignRole('super_admin');
    }
  }
}
