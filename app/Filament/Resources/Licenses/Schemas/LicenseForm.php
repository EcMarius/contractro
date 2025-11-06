<?php

namespace App\Filament\Resources\Licenses\Schemas;

use Filament\Schemas\Components\DateTimePicker;
use Filament\Schemas\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Toggle;
use Filament\Schemas\Schema;

class LicenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('License Information')
                    ->schema([
                        TextInput::make('license_key')
                            ->label('License Key')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated on creation')
                            ->helperText('Automatically generated unique license key'),

                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Customer'),

                        TextInput::make('domain')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('example.com')
                            ->helperText('Domain where this license will be used'),
                    ])->columns(2),

                Section::make('Product Details')
                    ->schema([
                        TextInput::make('product_name')
                            ->default('Contract Platform')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('product_version')
                            ->maxLength(255)
                            ->placeholder('1.0.0'),

                        Select::make('type')
                            ->options([
                                'trial' => 'Trial (14 days)',
                                'monthly' => 'Monthly',
                                'yearly' => 'Yearly',
                                'lifetime' => 'Lifetime',
                            ])
                            ->default('monthly')
                            ->required()
                            ->live()
                            ->helperText('License duration type'),

                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'suspended' => 'Suspended',
                                'expired' => 'Expired',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('active')
                            ->required(),
                    ])->columns(2),

                Section::make('Dates')
                    ->schema([
                        DateTimePicker::make('issued_at')
                            ->default(now())
                            ->required()
                            ->label('Issue Date'),

                        DateTimePicker::make('expires_at')
                            ->label('Expiration Date')
                            ->helperText('Leave empty for lifetime licenses')
                            ->hidden(fn ($get) => $get('type') === 'lifetime'),
                    ])->columns(2),

                Section::make('Usage Statistics')
                    ->schema([
                        Placeholder::make('check_count')
                            ->label('Total Checks')
                            ->content(fn ($record) => $record?->check_count ?? 0),

                        Placeholder::make('last_checked_at')
                            ->label('Last Checked')
                            ->content(fn ($record) => $record?->last_checked_at?->diffForHumans() ?? 'Never'),

                        Placeholder::make('ip_address')
                            ->label('Last IP Address')
                            ->content(fn ($record) => $record?->ip_address ?? 'N/A'),
                    ])->columns(3)
                    ->visible(fn ($record) => $record !== null),

                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('notes')
                            ->rows(3)
                            ->placeholder('Internal notes about this license...')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }
}
