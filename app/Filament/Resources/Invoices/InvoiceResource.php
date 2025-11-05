<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Resources\Invoices\Pages;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'Invoices';

    protected static ?string $navigationGroup = 'Contract Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Information')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->relationship('company', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Company'),

                        Forms\Components\Select::make('contract_id')
                            ->relationship('contract', 'contract_number')
                            ->searchable()
                            ->preload()
                            ->label('Related Contract (Optional)'),

                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Invoice Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Auto-generated on save'),

                        Forms\Components\TextInput::make('series')
                            ->required()
                            ->maxLength(10)
                            ->default('FACT')
                            ->label('Invoice Series'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Ciornă',
                                'issued' => 'Emisă',
                                'paid' => 'Plătită',
                                'overdue' => 'Restanță',
                                'cancelled' => 'Anulată',
                            ])
                            ->required()
                            ->default('draft'),

                        Forms\Components\Select::make('currency')
                            ->options([
                                'RON' => 'RON - Romanian Leu',
                                'EUR' => 'EUR - Euro',
                                'USD' => 'USD - US Dollar',
                            ])
                            ->required()
                            ->default('RON'),
                    ])->columns(2),

                Forms\Components\Section::make('Client Information')
                    ->schema([
                        Forms\Components\TextInput::make('client_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Client Name'),

                        Forms\Components\TextInput::make('client_cui')
                            ->maxLength(50)
                            ->label('Client CUI'),

                        Forms\Components\Textarea::make('client_address')
                            ->maxLength(500)
                            ->rows(2)
                            ->label('Client Address'),
                    ])->columns(2),

                Forms\Components\Section::make('Dates')
                    ->schema([
                        Forms\Components\DatePicker::make('issue_date')
                            ->required()
                            ->default(now())
                            ->label('Issue Date'),

                        Forms\Components\DatePicker::make('due_date')
                            ->required()
                            ->after('issue_date')
                            ->default(now()->addDays(30))
                            ->label('Due Date'),

                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Payment Date')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(3),

                Forms\Components\Section::make('Invoice Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->schema([
                                Forms\Components\TextInput::make('description')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Description')
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->label('Quantity'),

                                Forms\Components\TextInput::make('unit')
                                    ->required()
                                    ->maxLength(20)
                                    ->default('buc')
                                    ->label('Unit'),

                                Forms\Components\TextInput::make('unit_price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('RON')
                                    ->minValue(0)
                                    ->label('Unit Price'),
                            ])
                            ->columns(5)
                            ->defaultItems(1)
                            ->minItems(1)
                            ->columnSpanFull()
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? null),
                    ]),

                Forms\Components\Section::make('Tax & Total')
                    ->schema([
                        Forms\Components\TextInput::make('vat_rate')
                            ->required()
                            ->numeric()
                            ->suffix('%')
                            ->default(19)
                            ->minValue(0)
                            ->maxValue(100)
                            ->label('VAT Rate'),

                        Forms\Components\Placeholder::make('calculations')
                            ->label('Calculations')
                            ->content('Subtotal, VAT, and Total will be calculated automatically on save')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('client_name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'issued' => 'info',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'cancelled' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Ciornă',
                        'issued' => 'Emisă',
                        'paid' => 'Plătită',
                        'overdue' => 'Restanță',
                        'cancelled' => 'Anulată',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('RON')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('issue_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null),

                Tables\Columns\TextColumn::make('payment_date')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Not paid')
                    ->toggledHiddenByDefault(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->defaultSort('issue_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Ciornă',
                        'issued' => 'Emisă',
                        'paid' => 'Plătită',
                        'overdue' => 'Restanță',
                        'cancelled' => 'Anulată',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label('Company')
                    ->multiple()
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('issue_date')
                    ->form([
                        Forms\Components\DatePicker::make('issued_from')
                            ->label('Issued from'),
                        Forms\Components\DatePicker::make('issued_until')
                            ->label('Issued until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['issued_from'], fn($q, $date) => $q->whereDate('issue_date', '>=', $date))
                            ->when($data['issued_until'], fn($q, $date) => $q->whereDate('issue_date', '<=', $date));
                    }),

                Tables\Filters\TernaryFilter::make('overdue')
                    ->label('Overdue Invoices')
                    ->queries(
                        true: fn($query) => $query->where('status', 'issued')->where('due_date', '<', now()),
                        false: fn($query) => $query->where(fn($q) => $q->where('status', '!=', 'issued')->orWhere('due_date', '>=', now())),
                    ),
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
                Infolists\Components\Section::make('Invoice Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('invoice_number')
                            ->label('Invoice Number')
                            ->copyable()
                            ->weight('bold')
                            ->size('lg'),

                        Infolists\Components\TextEntry::make('series')
                            ->label('Series'),

                        Infolists\Components\TextEntry::make('company.name')
                            ->label('Company'),

                        Infolists\Components\TextEntry::make('contract.contract_number')
                            ->label('Related Contract')
                            ->placeholder('No contract'),

                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'issued' => 'info',
                                'paid' => 'success',
                                'overdue' => 'danger',
                                'cancelled' => 'warning',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'draft' => 'Ciornă',
                                'issued' => 'Emisă',
                                'paid' => 'Plătită',
                                'overdue' => 'Restanță',
                                'cancelled' => 'Anulată',
                                default => $state,
                            }),

                        Infolists\Components\TextEntry::make('currency'),
                    ])->columns(3),

                Infolists\Components\Section::make('Client Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('client_name'),
                        Infolists\Components\TextEntry::make('client_cui')
                            ->label('CUI'),
                        Infolists\Components\TextEntry::make('client_address')
                            ->columnSpanFull(),
                    ])->columns(2),

                Infolists\Components\Section::make('Dates')
                    ->schema([
                        Infolists\Components\TextEntry::make('issue_date')
                            ->date(),
                        Infolists\Components\TextEntry::make('due_date')
                            ->date()
                            ->color(fn ($record) => $record->isOverdue() ? 'danger' : null),
                        Infolists\Components\TextEntry::make('payment_date')
                            ->date()
                            ->placeholder('Not paid yet'),
                    ])->columns(3),

                Infolists\Components\Section::make('Financial Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('amount')
                            ->label('Subtotal (without VAT)')
                            ->money('RON'),

                        Infolists\Components\TextEntry::make('vat_rate')
                            ->label('VAT Rate')
                            ->suffix('%'),

                        Infolists\Components\TextEntry::make('vat_amount')
                            ->label('VAT Amount')
                            ->money('RON'),

                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Total Amount')
                            ->money('RON')
                            ->weight('bold')
                            ->size('lg'),
                    ])->columns(4),

                Infolists\Components\Section::make('Invoice Items')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->schema([
                                Infolists\Components\TextEntry::make('description')
                                    ->columnSpan(2),
                                Infolists\Components\TextEntry::make('quantity'),
                                Infolists\Components\TextEntry::make('unit'),
                                Infolists\Components\TextEntry::make('unit_price')
                                    ->money('RON'),
                            ])
                            ->columns(5)
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->columnSpanFull()
                            ->placeholder('No notes'),
                    ])
                    ->collapsible()
                    ->collapsed(),
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'overdue')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
