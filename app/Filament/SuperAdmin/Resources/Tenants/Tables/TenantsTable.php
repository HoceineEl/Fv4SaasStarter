<?php

namespace App\Filament\SuperAdmin\Resources\Tenants\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('impersonateTenantAdmin')
                    ->label(__('tenants.impersonate'))
                    ->icon('heroicon-m-user-switch')
                    ->visible(fn () => Auth::user()?->canImpersonate())
                    ->action(function ($record) {
                        $admin = User::query()
                            ->whereHas('tenants', fn ($q) => $q->whereKey($record->getKey()))
                            ->whereHas('roles', fn ($q) => $q->where('name', 'panel_user'))
                            ->first();
                        if ($admin) {
                            Auth::user()->impersonate($admin);
                            return redirect()->to('/');
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
