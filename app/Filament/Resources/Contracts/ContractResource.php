<?php

namespace App\Filament\Resources\Contracts;

use App\Filament\Resources\Contracts\Pages;
use App\Models\Contract;
use App\Models\ContractType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Contracts';

    protected static ?string $navigationGroup = 'Contract Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contract Information')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->relationship('company', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Company'),

                        Forms\Components\Select::make('contract_type_id')
                            ->relationship('contractType', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Contract Type'),

                        Forms\Components\TextInput::make('contract_number')
                            ->label('Contract Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Auto-generated on save'),

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->label('Contract Title'),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000)
                            ->rows(3),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Ciornă',
                                'pending' => 'Așteptare semnătură',
                                'signed' => 'Semnat',
                                'active' => 'Activ',
                                'expired' => 'Expirat',
                                'terminated' => 'Reziliat',
                            ])
                            ->required()
                            ->default('draft'),

                        Forms\Components\Select::make('signing_method')
                            ->options([
                                'sms' => 'SMS Verification',
                                'handwritten' => 'Handwritten Signature',
                                'digital' => 'Digital Signature',
                            ])
                            ->required()
                            ->default('sms')
                            ->label('Signing Method'),
                    ])->columns(2),

                Forms\Components\Section::make('Contract Value & Dates')
                    ->schema([
                        Forms\Components\TextInput::make('value')
                            ->numeric()
                            ->prefix('RON')
                            ->label('Contract Value'),

                        Forms\Components\Select::make('currency')
                            ->options([
                                'RON' => 'RON - Romanian Leu',
                                'EUR' => 'EUR - Euro',
                                'USD' => 'USD - US Dollar',
                            ])
                            ->default('RON'),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date'),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->after('start_date'),

                        Forms\Components\DateTimePicker::make('signed_at')
                            ->label('Signed At')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),

                Forms\Components\Section::make('Contract Content')
                    ->schema([
                        Forms\Components\RichEditor::make('content')
                            ->label('Contract Text')
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'h2',
                                'h3',
                                'italic',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('Number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 40 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('contractType.name')
                    ->label('Type')
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending' => 'warning',
                        'signed' => 'info',
                        'active' => 'success',
                        'expired' => 'danger',
                        'terminated' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Ciornă',
                        'pending' => 'Așteptare',
                        'signed' => 'Semnat',
                        'active' => 'Activ',
                        'expired' => 'Expirat',
                        'terminated' => 'Reziliat',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('value')
                    ->money('RON')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Ciornă',
                        'pending' => 'Așteptare semnătură',
                        'signed' => 'Semnat',
                        'active' => 'Activ',
                        'expired' => 'Expirat',
                        'terminated' => 'Reziliat',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('contract_type_id')
                    ->relationship('contractType', 'name')
                    ->label('Contract Type')
                    ->multiple(),

                Tables\Filters\SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label('Company')
                    ->multiple()
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created from'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Contract Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('contract_number')
                            ->label('Contract Number')
                            ->copyable()
                            ->weight('bold')
                            ->size('lg'),

                        Infolists\Components\TextEntry::make('title')
                            ->label('Title'),

                        Infolists\Components\TextEntry::make('company.name')
                            ->label('Company'),

                        Infolists\Components\TextEntry::make('contractType.name')
                            ->label('Contract Type')
                            ->badge(),

                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'pending' => 'warning',
                                'signed' => 'info',
                                'active' => 'success',
                                'expired' => 'danger',
                                'terminated' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'draft' => 'Ciornă',
                                'pending' => 'Așteptare',
                                'signed' => 'Semnat',
                                'active' => 'Activ',
                                'expired' => 'Expirat',
                                'terminated' => 'Reziliat',
                                default => $state,
                            }),

                        Infolists\Components\TextEntry::make('signing_method')
                            ->label('Signing Method')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'sms' => 'SMS Verification',
                                'handwritten' => 'Handwritten Signature',
                                'digital' => 'Digital Signature',
                                default => $state,
                            }),

                        Infolists\Components\TextEntry::make('description')
                            ->columnSpanFull(),
                    ])->columns(2),

                Infolists\Components\Section::make('Financial Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('value')
                            ->money('RON'),

                        Infolists\Components\TextEntry::make('currency'),

                        Infolists\Components\TextEntry::make('start_date')
                            ->date(),

                        Infolists\Components\TextEntry::make('end_date')
                            ->date(),

                        Infolists\Components\TextEntry::make('signed_at')
                            ->dateTime()
                            ->placeholder('Not signed yet'),
                    ])->columns(3),

                Infolists\Components\Section::make('Contract Content')
                    ->schema([
                        Infolists\Components\TextEntry::make('content')
                            ->html()
                            ->columnSpanFull()
                            ->placeholder('No content available'),
                    ]),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('parties_count')
                            ->label('Total Parties')
                            ->state(fn($record) => $record->contractParties()->count()),

                        Infolists\Components\TextEntry::make('signed_parties')
                            ->label('Signed Parties')
                            ->state(fn($record) => $record->contractParties()->whereHas('signatures', fn($q) => $q->where('code_verified', true))->count()),

                        Infolists\Components\TextEntry::make('amendments_count')
                            ->label('Amendments')
                            ->state(fn($record) => $record->amendments()->count()),

                        Infolists\Components\TextEntry::make('attachments_count')
                            ->label('Attachments')
                            ->state(fn($record) => $record->attachments()->count()),

                        Infolists\Components\TextEntry::make('invoices_count')
                            ->label('Related Invoices')
                            ->state(fn($record) => $record->invoices()->count()),
                    ])->columns(5),
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
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'view' => Pages\ViewContract::route('/{record}'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
