<?php

namespace App\Filament\SuperAdmin\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;

class UserForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make()
          ->schema([
            TextInput::make('name')
              ->required(),
            TextInput::make('email')
              ->email()
              ->required(),
            TextInput::make('password')
              ->password()
              ->revealable()
              ->dehydrated(fn($state) => filled($state))
              ->required(fn($operation) => $operation === 'create'),
            CheckboxList::make('roles')
              ->relationship(titleAttribute: 'name')
              ->getOptionLabelFromRecordUsing(fn($record) => __('roles.roles.' . $record->name))
              ->pivotData(fn(Get $get) => [
                config('permission.column_names.team_foreign_key') => is_array($get('tenants')) && count($get('tenants')) ? $get('tenants')[0] : null,
              ])
              ->searchable(),
            Select::make('tenants')
              ->multiple()
              ->relationship('tenants', titleAttribute: 'name')
              ->preload()
              ->required()
              ->label(__('users.tenants')),
          ]),
      ]);
  }
}
