<?php

namespace App\Classes;

use App\Enums\Sales\SalesTargetCategory;
use App\Enums\Sales\SalesTargetType;
use App\Enums\Subscriber\PredefinedOptionType;
use App\Filament\Tenant\Resources\SalesTargetResource;
use App\Models\Tenant;
use App\Models\Branch;
use App\Models\PredefinedOption;
use App\Models\SalesTarget;
use Awcodes\Shout\Components\Shout;
use Closure;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Colors\Color;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

class Core
{
    public static function tenant(?string $attr = null): Tenant | string | null
    {
        $tenant = Filament::getTenant();

        return $attr && $tenant ? $tenant->$attr : $tenant;
    }

    public static function currentPanel(): ?string
    {
        return Filament::getCurrentPanel()?->getId();
    }

    public static function weAreOnAdminPanel(): bool
    {
        return self::currentPanel() === 'admin';
    }

    public static function weAreOnTenantPanel(): bool
    {
        return self::currentPanel() === 'tenant';
    }

    public static function weAreOnSubscriberPanel(): bool
    {
        return self::currentPanel() === 'subscriber';
    }

    public static function defaultLocation(): array
    {
        if (getSelectedBranchId() || user()->branch_id) {
            $branch = Branch::find(getSelectedBranchId() ?? user()->branch_id);

            return [$branch->lat, $branch->lng];
        }

        return [self::settings('lat') ?? config('globals.default_lat'), self::settings('lng') ?? config('globals.default_lng')];
    }

    public static function settings(?string $attr = null): mixed
    {
        $tenant = self::tenant();
        if (! $tenant) {
            return null;
        }

        if (self::weAreOnAdminPanel()) {
            return null;
        }

        $cacheKey = "tenant_settings_{$tenant->id}";
        $settings = Cache::rememberForever($cacheKey, function () use ($tenant) {
            return $tenant->settings;
        });

        return $attr ? ($settings->$attr ?? null) : $settings;
    }

    public static function currency(Tenant | string | null $tenant = null): string
    {
        try {
            $tenant = $tenant ?? self::tenant();
            if (! $tenant) {
                return self::currencySymbol();
            }
            $currency = $tenant->settings->currency;
            if ($currency && $currency !== 'SAR') {
                return $currency;
            }

            return self::currencySymbol();
        } catch (\Throwable $th) {
            return self::currencySymbol();
        }
    }

    /**
     * Get the currency symbol for display.
     */
    public static function currencySymbol(Tenant | string | null $tenant = null): string
    {

        return "\xEE\xA4\x80"; // Unicode E900 in UTF-8 encoding

    }

    public static function clearSettingsCache(): void
    {
        $tenant = self::tenant();
        if ($tenant) {
            $cacheKey = "tenant_settings_{$tenant->id}";
            Cache::forget($cacheKey);
        }
        $cacheKey = "tenant_{$tenant->id}_branches";
        Cache::forget($cacheKey);
        Cache::forget('tenant_currency_' . $tenant->id);
    }

    public static function tenantFilter(): SelectFilter
    {
        return SelectFilter::make('tenant_id')
            ->options(fn() => Tenant::pluck('name', 'id'))
            ->visible(fn() => self::weAreOnAdminPanel())
            ->label(__('app.tenant'));
    }

    public static function branchFilter(?string $relationship = null, string $statePath = 'branch_id'): SelectFilter
    {
        if (! $relationship) {
            return SelectFilter::make($statePath)
                ->options(fn() => \App\Models\Branch::pluck('name', 'id'))
                ->default(fn() => getSelectedBranchId())
                ->label(__('app.branch'));
        }

        return SelectFilter::make($statePath)
            ->relationship($relationship, 'name')
            ->label(__('app.branch'));
    }

    public static function phoneInput(string $statePath = 'mobile', ?string $label = null, Closure | bool $required = true): PhoneInput
    {
        return PhoneInput::make($statePath)
            ->defaultCountry('SA')
            ->initialCountry('SA')
            // ->validateFor('SA')
            ->showFlags(true)
            ->separateDialCode(true)
            ->label($label ?? __('subscribers.mobile'))
            ->required($required);
    }

    public static function soldByInput(SalesTargetCategory $targetCategory = SalesTargetCategory::COURSE_SUBSCRIPTION, SalesTargetType $targetType = SalesTargetType::COUNT): Component
    {
        $sellers = tenant()->sellers()
            ->whereHas('salesTargets', function ($query) use ($targetCategory) {
                $query
                    ->active()
                    // ->where('target_type', $targetType)
                    ->where('target_category', $targetCategory);
            })
            ->pluck('users.name', 'users.id')
            ->toArray();
        $hasSellers = ! empty($sellers);
        $refreshAction = Action::make('refresh')
            ->label(__('app.refresh'))
            ->icon('tabler-refresh')
            ->action(fn() => $sellers = tenant()->sellers()
                ->whereHas('salesTargets', function ($query) use ($targetCategory) {
                    $query->active()
                        // ->where('target_type', $targetType)
                        ->where('target_category', $targetCategory);
                })
                ->pluck('users.name', 'users.id')
                ->toArray());
        $actions = [
            $refreshAction,
        ];
        $actions[] = Action::make('add-sales-target')
            ->label(__('sales-records.add-sales-target-for-this-category'))
            ->icon('tabler-plus')
            ->color('info')
            ->url(fn() => SalesTargetResource::getUrl('index', ['targetCategory' => $targetCategory, 'targetType' => $targetType, 'load_action' => true]), true);

        if (! $hasSellers) {
            return Shout::make('soldBy')
                ->content(fn() => __('sales-records.no_sellers_available'))
                ->hintActions($actions)
                ->type('info');
        }

        return Select::make('soldBy')
            ->options(fn() => $sellers)
            ->preload()
            ->columnSpan(1)
            ->disabled(! $hasSellers)
            ->default(fn() => ! $hasSellers ? null : (user()?->isSalesEmployee() ? auth()->id() : null))
            ->hintActions($actions)
            ->label(__('sales-records.sold_by'));
    }

    public static function salesTargetInput(SalesTargetCategory $targetCategory = SalesTargetCategory::COURSE_SUBSCRIPTION, string $statePath = 'salesTargetId'): Component
    {
        $salesTargets = SalesTarget::query()
            ->active()
            ->where('target_category', $targetCategory)
            ->with('employee')
            ->get()
            ->mapWithKeys(function ($target) {
                return [$target->id => $target->name];
            })
            ->toArray();

        $hasSalesTargets = ! empty($salesTargets);

        $refreshAction = Action::make('refresh')
            ->label(__('app.refresh'))
            ->icon('tabler-refresh')
            ->action(function () use (&$salesTargets, $targetCategory) {
                $salesTargets = SalesTarget::query()
                    ->active()
                    ->where('target_category', $targetCategory)
                    ->with('employee')
                    ->get()
                    ->mapWithKeys(function ($target) {
                        return [$target->id => $target->name];
                    })
                    ->toArray();
            });

        $actions = [
            $refreshAction,
        ];

        $actions[] = Action::make('add-sales-target')
            ->label(__('sales-records.add-sales-target-for-this-category'))
            ->icon('tabler-plus')
            ->color('info')
            ->url(fn() => SalesTargetResource::getUrl('index', ['targetCategory' => $targetCategory, 'load_action' => true]), true);

        if (! $hasSalesTargets) {
            return Shout::make($statePath)
                ->content(fn() => __('sales-records.no_sales_targets_available'))
                ->hintActions($actions)
                ->type('info');
        }

        return Select::make($statePath)
            ->options(fn() => $salesTargets)
            ->preload()
            ->columnSpan(1)
            ->disabled(! $hasSalesTargets)
            ->hintActions($actions)
            ->label(__('sales-records.sales_target'));
    }

    public static function Colors(): array
    {
        return collect(Color::all())
            ->filter(function ($color) {
                return
                    $color !== Color::Gray &&
                    $color !== Color::Zinc &&
                    $color !== Color::Neutral &&
                    $color !== Color::Slate;
            })
            ->toArray();
    }

    public static function effectiveMobileColumn(?string $label = null, string $attribute = 'effectiveMobile', PhoneInputNumberType $displayFormat = PhoneInputNumberType::E164): PhoneColumn
    {
        $label = $label ?? __('subscribers.mobile');

        return PhoneColumn::make($attribute)
            ->label($label)
            ->searchable(query: function (Builder $query, string $search): Builder {
                try {
                    $formattedSearch = format_phone($search, 'SA');

                    return $query
                        ->where('mobile', 'like', "%{$formattedSearch}%")
                        ->orWhereHas('profile.parent', function (Builder $query) use ($formattedSearch) {
                            $query->where('mobile', 'like', "%{$formattedSearch}%");
                        })
                        ->orWhereHas('profile', function (Builder $query) use ($formattedSearch) {
                            $query->where('parent_mobile', 'like', "%{$formattedSearch}%");
                        });
                } catch (\Exception $e) {
                    return $query
                        ->where('mobile', 'like', "%{$search}%")
                        ->orWhereHas('profile.parent', function (Builder $query) use ($search) {
                            $query->where('mobile', 'like', "%{$search}%");
                        })
                        ->orWhereHas('profile', function (Builder $query) use ($search) {
                            $query->where('parent_mobile', 'like', "%{$search}%");
                        });
                }
            })
            ->description(fn($record) => $record->parentMobile === $record->effectiveMobile ? __('subscribers.parent_mobile') : null)
            ->sortable(['mobile'])
            ->url(fn($record) => $record->whatsappLink, true)
            ->displayFormat($displayFormat);
    }

    /**
     * Create a predefined option select field
     */
    public static function createPredefinedOptionField(string $name, string $label, string | PredefinedOptionType $type): Select
    {
        return Select::make($name)
            ->label($label)
            ->options(fn() => PredefinedOption::getOptionsForType($type, true))
            ->searchable()
            ->createOptionForm([
                TextInput::make('value')
                    ->label(__('predefined-options.value'))
                    ->required(),
            ])
            ->createOptionUsing(function (array $data) use ($type) {
                $option = PredefinedOption::firstOrCreate([
                    'type' => $type,
                    'value' => $data['value'],
                ]);

                return $option->id;
            })

            ->getSearchResultsUsing(function (string $search) use ($type) {
                return PredefinedOption::where('type', $type)
                    ->where('value', 'like', "%{$search}%")
                    ->pluck('value', 'id');
            })
            ->getOptionLabelUsing(fn($value) => PredefinedOption::find($value)?->value);
    }

    /**
     * Get the default branch ID for a new subscriber
     *
     * @return int|null The ID of the first branch from the current tenant
     */
    public static function getDefaultBranchId(Tenant | string | null $tenant = null): ?int
    {
        if (getSelectedBranchId()) {
            return getSelectedBranchId();
        }

        $user = auth()->user();

        if ($user && is_int($user->branch_id)) {
            return $user->branch_id;
        }

        $tenant = $tenant ?? self::tenant();
        if (! $tenant) {
            return null;
        }

        return $tenant->branches()->first()?->id;
    }

    /**
     * Create a text column with Arabic text search capabilities
     *
     * @param  string  $key  Column key
     * @param  string|null  $label  Column label
     * @param  string|null  $relationship  Optional relationship name for related columns
     * @param  callable|null  $descriptionFn  Optional function to generate description
     * @param  array  $additionalSearchFields  Additional fields to search in (e.g., ['email'] for description fields)
     */
    public static function normalizedSearchColumn(
        string $key,
        ?string $label = null,
        ?string $relationship = null,
        ?callable $descriptionFn = null,
        array $additionalSearchFields = []
    ): \Filament\Tables\Columns\TextColumn {
        $column = \Filament\Tables\Columns\TextColumn::make($key)
            ->searchable(isIndividual: false, query: function (\Illuminate\Database\Eloquent\Builder $query, string $search) use ($key, $relationship, $additionalSearchFields): \Illuminate\Database\Eloquent\Builder {
                $nameVariations = normalizeArabicTextAsArray($search);
                $searchTerms = array_merge([$search], $nameVariations ?? []);

                return $query->where(function (\Illuminate\Database\Eloquent\Builder $query) use ($searchTerms, $key, $relationship, $additionalSearchFields): \Illuminate\Database\Eloquent\Builder {
                    foreach ($searchTerms as $term) {
                        $query->orWhere(function (\Illuminate\Database\Eloquent\Builder $subQuery) use ($term, $key, $relationship, $additionalSearchFields) {
                            if ($relationship) {
                                $subQuery->whereHas($relationship, function (\Illuminate\Database\Eloquent\Builder $relationQuery) use ($term, $key): \Illuminate\Database\Eloquent\Builder {
                                    return $relationQuery->where($key, 'like', "%{$term}%");
                                });
                            } else {
                                $subQuery->where($key, 'like', "%{$term}%");
                            }

                            foreach ($additionalSearchFields as $additionalField) {
                                if ($relationship) {
                                    $subQuery->orWhereHas($relationship, function (\Illuminate\Database\Eloquent\Builder $relationQuery) use ($term, $additionalField): \Illuminate\Database\Eloquent\Builder {
                                        return $relationQuery->where($additionalField, 'like', "%{$term}%");
                                    });
                                } else {
                                    $subQuery->orWhere($additionalField, 'like', "%{$term}%");
                                }
                            }
                        });
                    }

                    return $query;
                });
            });

        if ($label) {
            $column->label($label);
        }

        if ($descriptionFn) {
            $column->description($descriptionFn);
        }

        return $column;
    }

    public static function invoiceType(Tenant | string | null $tenant = null): \App\Enums\Invoice\InvoiceType
    {
        $tenant = $tenant ?: self::tenant();

        return $tenant?->settings?->invoice_type ?? \App\Enums\Invoice\InvoiceType::TaxPhase1;
    }

    public static function isNoTax(Tenant | string | null $tenant = null): bool
    {
        $tenant = $tenant ?: self::tenant();

        return $tenant?->settings?->invoice_type === \App\Enums\Invoice\InvoiceType::NoTax;
    }
}
