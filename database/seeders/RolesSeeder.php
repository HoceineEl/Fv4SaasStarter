<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
  public function run(): void
  {
    $tenant = Tenant::first();

    if (! $tenant) {
      return;
    }

    $guard = 'web';

    app()['cache']->forget('spatie.permission.cache');

    $permissions = [
      'users.view',
      'users.create',
      'users.edit',
      'users.delete',
      'tenants.view',
      'tenants.create',
      'tenants.edit',
      'tenants.delete',
    ];

    foreach ($permissions as $name) {
      Permission::firstOrCreate([
        'name' => $name,
        'guard_name' => $guard,
      ]);
    }

    $roles = [
      'super_admin' => $permissions,
      'tenant_admin' => [
        'users.view',
        'users.create',
        'users.edit',
      ],
      'tenant_stuff' => [
        'users.view',
      ],
    ];

    foreach ($roles as $roleName => $perms) {
      $role = Role::where('name', $roleName)
        ->where('guard_name', $guard)
        ->when(config('permission.teams'), function ($q) use ($tenant) {
          $q->where('tenant_id', $tenant->id);
        })
        ->first();

      if (! $role) {
        $role = new Role();
        if (config('permission.teams')) {
          $role->tenant_id = $tenant->id;
        }
        $role->name = $roleName;
        $role->guard_name = $guard;
        $role->save();
      }

      $role->syncPermissions($perms);
    }
  }
}
