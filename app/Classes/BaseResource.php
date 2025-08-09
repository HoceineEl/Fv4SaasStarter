<?php

namespace App\Classes;

use BackedEnum;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BaseResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = 'tabler-point-filled';

    public static function langFile(): string
    {
        return str(parent::getSlug())->explode('/')->last();
    }

    public static function getModelLabel(): string
    {
        return __(static::langFile() . '.titleSingle');
    }

    public static function getPluralModelLabel(): string
    {
        return __(static::langFile() . '.title');
    }

    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    // @phpstan-ignore-next-line
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // if (static::getModel()::isUsingSoftDelete()) {
        //     $query
        //         ->withoutGlobalScopes([
        //             SoftDeletingScope::class,
        //         ]);
        // }

        if (static::getModel()::isUsingActionBy()) {
            $query->with(['createdBy', 'updatedBy']);
        }

        return $query;
    }
}
