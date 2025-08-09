<?php

namespace App\Filament\SuperAdmin\Resources\Users\Tables;

use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

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
      ])
      ->recordActions([
        Action::make('impersonate')
          ->label(__('users.impersonate'))
          ->icon('heroicon-m-arrow-right-start-on-rectangle')
          ->visible(fn ($record) => Auth::user()?->canImpersonate() && $record->canBeImpersonated())
          ->url(fn ($record): string => route('impersonate', $record->getKey()))
          ->postToUrl(),
      ]);
  }
}
