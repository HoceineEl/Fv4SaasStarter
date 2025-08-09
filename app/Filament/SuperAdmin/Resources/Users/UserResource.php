<?php

namespace App\Filament\SuperAdmin\Resources\Users;

use App\Classes\BaseResource;
use App\Models\Tenant;
use App\Models\User;
use App\Filament\SuperAdmin\Resources\Users\Schemas\UserForm;
use App\Filament\SuperAdmin\Resources\Users\Tables\UsersTable;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends BaseResource
{
  protected static ?string $model = User::class;

  protected static ?string $recordTitleAttribute = 'name';

  protected static bool $isScopedToTenant = false;

  public static function getEloquentQuery(): Builder
  {
    return parent::getEloquentQuery()->withoutGlobalScopes();
  }

  public static function form(Schema $schema): Schema
  {
    return UserForm::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return UsersTable::configure($table);
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListUsers::route('/'),
      'create' => Pages\CreateUser::route('/create'),
      'view' => Pages\ViewUser::route('/{record}'),
      'edit' => Pages\EditUser::route('/{record}/edit'),
    ];
  }
}
