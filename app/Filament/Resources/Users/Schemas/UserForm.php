<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;

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
              ->pivotData(function () {
                $tenantId = Filament::getTenant()?->id;
                return [
                  config('permission.column_names.team_foreign_key') => $tenantId,
                ];
              })
              ->searchable(),
            Select::make('tenants')
              ->relationship('tenants', titleAttribute: 'name')
              ->preload()
              ->multiple()
              ->hidden()
              ->dehydrated(false),
          ]),
      ]);
  }
}
