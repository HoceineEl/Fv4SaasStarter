<?php

namespace App\Classes\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use MohamedSabil83\FilamentHijriPicker\Forms\Components\HijriDatePicker;
use GeniusTS\HijriDate\Date;
use GeniusTS\HijriDate\Hijri;
use GeniusTS\HijriDate\Translations\Arabic;
use Carbon\Carbon;

class HijriDatePickerSchema
{
    public static function make(
        string $name = 'dob',
        ?string $label = null,
        bool $required = false,
        int $columnSpan = 5,
        ?callable $afterStateUpdated = null,
        array $extraDatePickerOptions = [],
        array $extraHijriPickerOptions = []
    ): Grid {
        $label = $label ?? __('subscribers.dob');
        $hijriLabel = __('subscribers.hijri_dob');

        return Grid::make($columnSpan)
            ->schema([
                Toggle::make('is_it_hijri_' . $name)
                    ->label(__('subscribers.is_it_hijri_dob'))
                    ->dehydrated(false)
                    ->inline(false)
                    ->columnSpan(1)
                    ->live()
                    ->default(false),

                DatePicker::make($name)
                    ->label($label)
                    ->native(false)
                    ->columnSpan(2)
                    ->required($required)
                    ->hidden(fn(Get $get) => $get('is_it_hijri_' . $name))
                    ->live()
                    ->default(function ($record) use ($name) {
                        // Get default from record if available
                        if ($record && isset($record->{$name}) && $record->{$name}) {
                            return $record->{$name};
                        }
                        return null;
                    })
                    ->afterStateUpdated(function ($state, Set $set, Get $get) use ($name, $afterStateUpdated) {
                        if (!$state) {
                            return;
                        }

                        try {
                            Date::setTranslation(new Arabic);
                            $hijriDate = Hijri::convertToHijri($state);
                            $set('hijri_' . $name . '_placeholder', $hijriDate->format('l d F o'));
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title(__('subscribers.invalid_date'))
                                ->body(__('subscribers.please_enter_valid_date'))
                                ->send();
                        }

                        if ($afterStateUpdated) {
                            $afterStateUpdated($state, $set, $get);
                        }
                    })
                    ->when(
                        !empty($extraDatePickerOptions),
                        function ($component) use ($extraDatePickerOptions) {
                            foreach ($extraDatePickerOptions as $method => $value) {
                                if (method_exists($component, $method)) {
                                    $component->$method($value);
                                }
                            }
                            return $component;
                        }
                    ),

                HijriDatePicker::make('hijri_' . $name)
                    ->label($hijriLabel)
                    ->columnSpan(2)
                    ->required($required)
                    ->hidden(fn(Get $get) => !$get('is_it_hijri_' . $name))
                    ->live(debounce: 500)
                    ->default(function (Get $get, $record) use ($name) {
                        // Try to get default from existing Gregorian date
                        $dateValue = $get($name);
                        if (!$dateValue && $record && isset($record->{$name}) && $record->{$name}) {
                            $dateValue = $record->{$name};
                        }

                        if ($dateValue) {
                            try {
                                Date::setTranslation(new Arabic);
                                $date = is_string($dateValue) ? Carbon::parse($dateValue) : $dateValue;
                                $hijriDate = Hijri::convertToHijri($date);
                                return $hijriDate->format('Y-m-d');
                            } catch (\Exception $e) {
                                return null;
                            }
                        }

                        return null;
                    })
                    ->afterStateUpdated(function ($state, Set $set, Get $get) use ($name, $afterStateUpdated) {
                        if (!$state) {
                            return;
                        }

                        try {
                            $dateParts = explode('-', $state);
                            Date::setTranslation(new Arabic);
                            $gregorianDate = Hijri::convertToGregorian(
                                (int) $dateParts[2], // Day
                                (int) $dateParts[1], // Month
                                (int) $dateParts[0]  // Year
                            );
                            $set($name . '_placeholder', $gregorianDate->translatedFormat('l d F o'));
                            $set($name, $gregorianDate->format('Y-m-d'));
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title(__('subscribers.invalid_hijri_date'))
                                ->body(__('subscribers.please_enter_valid_hijri_date'))
                                ->send();
                        }

                        if ($afterStateUpdated) {
                            $afterStateUpdated($gregorianDate->format('Y-m-d'), $set, $get);
                        }
                    })
                    ->when(
                        !empty($extraHijriPickerOptions),
                        function ($component) use ($extraHijriPickerOptions) {
                            foreach ($extraHijriPickerOptions as $method => $value) {
                                if (method_exists($component, $method)) {
                                    $component->$method($value);
                                }
                            }
                            return $component;
                        }
                    ),

                Placeholder::make('hijri_' . $name . '_placeholder')
                    ->label($hijriLabel)
                    ->live()
                    ->content(function ($state, $record, Get $get) use ($name) {
                        if ($state) {
                            return $state;
                        }

                        // Try to get the date from the main field
                        $dateValue = $get($name);
                        if ($dateValue) {
                            try {
                                Date::setTranslation(new Arabic);
                                $date = is_string($dateValue) ? Carbon::parse($dateValue) : $dateValue;
                                return Hijri::convertToHijri($date)->format('l d F o');
                            } catch (\Exception $e) {
                                // Fall back to record if available
                            }
                        }

                        // Fall back to record data
                        if ($record && isset($record->{$name}) && $record->{$name}) {
                            try {
                                Date::setTranslation(new Arabic);
                                return Hijri::convertToHijri($record->{$name})->format('l d F o');
                            } catch (\Exception $e) {
                                return null;
                            }
                        }

                        return null;
                    })
                    ->hidden(fn(Get $get) => $get('is_it_hijri_' . $name)),

                Hidden::make($name),

                Placeholder::make($name . '_placeholder')
                    ->label($label)
                    ->live()
                    ->content(function ($record, $state, Get $get) use ($name) {
                        if ($state) {
                            return $state;
                        }

                        // Try to get the date from the main field
                        $dateValue = $get($name);
                        if ($dateValue) {
                            try {
                                $date = is_string($dateValue) ? Carbon::parse($dateValue) : $dateValue;
                                return $date->translatedFormat('l d F o');
                            } catch (\Exception $e) {
                                // Fall back to record if available
                            }
                        }

                        // Fall back to record data
                        if ($record && isset($record->{$name}) && $record->{$name}) {
                            try {
                                return $record->{$name}->translatedFormat('l d F o');
                            } catch (\Exception $e) {
                                return null;
                            }
                        }

                        return null;
                    })
                    ->hidden(fn(Get $get) => !$get('is_it_hijri_' . $name)),
            ]);
    }

    public static function makeDob(
        bool $required = false,
        int $columnSpan = 5,
        ?callable $afterStateUpdated = null,
        array $extraDatePickerOptions = [],
        array $extraHijriPickerOptions = []
    ): Grid {
        return self::make(
            name: 'dob',
            label: __('subscribers.dob'),
            required: $required,
            columnSpan: $columnSpan,
            afterStateUpdated: $afterStateUpdated,
            extraDatePickerOptions: $extraDatePickerOptions,
            extraHijriPickerOptions: $extraHijriPickerOptions
        );
    }

    public static function makeUserDob(
        bool $required = false,
        int $columnSpan = 5,
        ?callable $afterStateUpdated = null,
        array $extraDatePickerOptions = [],
        array $extraHijriPickerOptions = []
    ): Grid {
        return self::make(
            name: 'dob',
            label: __('users.dob'),
            required: $required,
            columnSpan: $columnSpan,
            afterStateUpdated: $afterStateUpdated,
            extraDatePickerOptions: array_merge([
                'maxDate' => now()->subYears(2),
                'displayFormat' => 'd/m/Y'
            ], $extraDatePickerOptions),
            extraHijriPickerOptions: $extraHijriPickerOptions
        );
    }
}
