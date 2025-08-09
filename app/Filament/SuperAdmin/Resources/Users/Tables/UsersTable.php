<?php

namespace App\Filament\SuperAdmin\Resources\Users\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('name'),
        TextColumn::make('email'),
        TextColumn::make('tenants.name')
          ->badge()
          ->label(__('users.tenants')),
        TextColumn::make('roles.name')
          ->badge()
          ->formatStateUsing(fn($state) => __('roles.roles.' . $state))
          ->label(__('users.roles')),
      ])
      ->filters([
        SelectFilter::make('tenants')
          ->relationship('tenants', 'name')
          ->label(__('users.tenants')),
        SelectFilter::make('roles')
          ->relationship('roles', 'name')
          ->label(__('users.roles')),
      ]);
  }
}
