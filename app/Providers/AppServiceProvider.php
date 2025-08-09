<?php

declare(strict_types=1);

namespace App\Providers;

use Filament\Actions\DeleteAction;
use Filament\Actions\MountableAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use BezhanSalleh\FilamentLanguageSwitch\Enums\Placement;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Filament\Actions\Action;
use Illuminate\Database\Schema\Blueprint;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::unguard();

        Model::preventLazyLoading(! $this->app->environment('production'));

        Model::preventSilentlyDiscardingAttributes(! $this->app->environment('production'));

        Model::automaticallyEagerLoadRelationships();

        JsonResource::withoutWrapping();

        TextEntry::configureUsing(function (TextEntry $entry) {
            $entry->numeric(locale: 'en');
        });

        Table::configureUsing(function (Table $table) {
            $table
                ->paginationPageOptions([5, 10, 15, 20, 50])
                ->defaultPaginationPageOption(5)
                ->modifyQueryUsing(function ($query) {
                    $tableName = $query->getModel()->getTable();
                    return $query->orderBy($tableName . '.created_at', 'desc');
                })
                ->emptyStateHeading(__('app.empty_state_heading'))
                ->emptyStateDescription(__('app.empty_state_description'))
                ->emptyStateIcon('heroicon-o-exclamation-circle')
                ->deferLoading()
                ->searchOnBlur()
                ->filtersTriggerAction(function (Action $action) {
                    $action->button()->label(__('app.filters_trigger_action'));
                })
                ->filtersFormColumns(2);
        });

        Page::formActionsAlignment(Alignment::Right);

        // MountableAction::configureUsing(function (MountableAction $action) {
        //     $action
        //         ->modalFooterActionsAlignment(Alignment::Right)
        //         ->modalSubmitActionLabel(__('app.save'));
        // });

        $translateWithResourcePrefix = static function (string $name): string {
            $prefix = '';
            if (
                method_exists(Livewire::current(), 'getResource') &&
                method_exists(Livewire::current()->getResource(), 'langFile')
            ) {
                /** @phpstan-ignore-next-line */
                $prefix = Livewire::current()->getResource()::langFile() . '.';
            }

            return __($prefix . $name);
        };

        BaseFilter::configureUsing(function (BaseFilter $filter) use ($translateWithResourcePrefix) {
            $filter->label(fn() => $translateWithResourcePrefix($filter->getName()));
        });

        Column::configureUsing(function (Column $column) use ($translateWithResourcePrefix) {
            $column
                ->searchable()
                ->sortable()
                ->placeholder(__('app.N\A'))
                ->label(fn() => $translateWithResourcePrefix($column->getName()))
                ->toggleable();
        });

        ImageColumn::configureUsing(function (ImageColumn $column) use ($translateWithResourcePrefix) {
            $column->label(fn() => $translateWithResourcePrefix($column->getName()));
        });

        Field::configureUsing(function (Field $field) use ($translateWithResourcePrefix) {
            $field->label(fn() => $translateWithResourcePrefix($field->getName()));
        });

        Select::configureUsing(function (Select $field) {
            $field->searchable();
        });

        ToggleButtons::configureUsing(function (ToggleButtons $field) {
            $field->inline();
        });

        DeleteAction::configureUsing(function (DeleteAction $action) {
            $action->color('pink');
        });

        ViewAction::configureUsing(function (ViewAction $action) {
            $action->color('blue');
        });

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['ar', 'en'])
                ->displayLocale('ar')
                ->visible(outsidePanels: true)
                ->outsidePanelRoutes(['login', 'password.request', 'password.reset'])
                ->outsidePanelPlacement(Placement::BottomRight)
                ->circular();
        });


        Blueprint::macro('actionBy', function () {
            /** @var Blueprint $this */
            $this->unsignedBigInteger('created_by')->nullable();
            $this->unsignedBigInteger('updated_by')->nullable();
        });

        Blueprint::macro('dropActionBy', function () {
            /** @var Blueprint $this */
            $this->dropColumn(['created_by', 'updated_by']);
        });
    }
}
