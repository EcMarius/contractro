<?php

namespace App\Filament\Resources\Companies;

use App\Filament\Resources\Companies\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Companies';

    protected static ?string $navigationGroup = 'Contract Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Company Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Company Name'),

                        Forms\Components\TextInput::make('cui')
                            ->label('CUI (Romanian Fiscal Code)')
                            ->maxLength(50)
                            ->helperText('Format: RO12345678')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state && !Company::validateCUI($state)) {
                                    $set('cui_error', 'Invalid CUI');
                                } else {
                                    $set('cui_error', null);
                                }
                            }),

                        Forms\Components\TextInput::make('reg_com')
                            ->label('Commercial Register')
                            ->maxLength(50),

                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->label('Owner'),
                    ])->columns(2),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->maxLength(500)
                            ->rows(2),

                        Forms\Components\TextInput::make('city')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('county')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('country')
                            ->maxLength(100)
                            ->default('România'),

                        Forms\Components\TextInput::make('postal_code')
                            ->maxLength(20),

                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('website')
                            ->url()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Banking Information')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('iban')
                            ->maxLength(50)
                            ->placeholder('RO49AAAA1B31007593840000'),
                    ])->columns(2),

                Forms\Components\Section::make('Logo')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_path')
                            ->label('Company Logo')
                            ->image()
                            ->disk('public')
                            ->directory('company-logos')
                            ->maxSize(2048)
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,
                                '16:9',
                                '4:3',
                                '1:1',
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-company-logo.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('cui')
                    ->label('CUI')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('contracts_count')
                    ->counts('contracts')
                    ->label('Contracts')
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoices_count')
                    ->counts('invoices')
                    ->label('Invoices')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Owner')
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Company Details')
                    ->schema([
                        Infolists\Components\ImageEntry::make('logo_path')
                            ->label('Logo')
                            ->height(100),

                        Infolists\Components\TextEntry::make('name')
                            ->label('Company Name'),

                        Infolists\Components\TextEntry::make('cui')
                            ->label('CUI'),

                        Infolists\Components\TextEntry::make('reg_com')
                            ->label('Commercial Register'),

                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Owner'),
                    ])->columns(2),

                Infolists\Components\Section::make('Contact Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('address'),
                        Infolists\Components\TextEntry::make('city'),
                        Infolists\Components\TextEntry::make('county'),
                        Infolists\Components\TextEntry::make('country'),
                        Infolists\Components\TextEntry::make('postal_code'),
                        Infolists\Components\TextEntry::make('phone'),
                        Infolists\Components\TextEntry::make('email'),
                        Infolists\Components\TextEntry::make('website')
                            ->url(fn($record) => $record->website),
                    ])->columns(2),

                Infolists\Components\Section::make('Banking Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('bank_name'),
                        Infolists\Components\TextEntry::make('iban'),
                    ])->columns(2),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('contracts_count')
                            ->label('Total Contracts')
                            ->state(fn($record) => $record->contracts()->count()),

                        Infolists\Components\TextEntry::make('active_contracts_count')
                            ->label('Active Contracts')
                            ->state(fn($record) => $record->contracts()->where('status', 'active')->count()),

                        Infolists\Components\TextEntry::make('invoices_count')
                            ->label('Total Invoices')
                            ->state(fn($record) => $record->invoices()->count()),

                        Infolists\Components\TextEntry::make('total_revenue')
                            ->label('Total Revenue')
                            ->state(fn($record) => number_format($record->invoices()->where('status', 'paid')->sum('total_amount'), 2) . ' RON'),
                    ])->columns(4),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'view' => Pages\ViewCompany::route('/{record}'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
