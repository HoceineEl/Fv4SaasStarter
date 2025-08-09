<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('name'),
        TextColumn::make('email'),
        TextColumn::make('roles.name')
          ->badge()
          ->formatStateUsing(fn($state) => __('roles.roles.' . $state))
          ->label(__('users.roles')),
      ]);
  }
}
