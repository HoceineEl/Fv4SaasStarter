<?php

namespace App\Filament\Resources\Users;

use App\Classes\BaseResource;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends BaseResource
{
  protected static ?string $model = User::class;

  protected static ?string $recordTitleAttribute = 'name';

  protected static string|BackedEnum|null $navigationIcon = 'tabler-users';

  public static function getEloquentQuery(): Builder
  {
    $tenantId = Filament::getTenant()?->id;

    return parent::getEloquentQuery()
      ->when($tenantId, fn(Builder $q) => $q->whereHas('tenants', fn(Builder $t) => $t->whereKey($tenantId)));
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
