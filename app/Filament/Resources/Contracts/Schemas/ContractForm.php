<?php

namespace App\Filament\Resources\Contracts\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;

class ContractForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contract Information')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('contract_number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->columnSpan(1),

                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'pending_signature' => 'Pending Signature',
                                'partially_signed' => 'Partially Signed',
                                'signed' => 'Signed',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'expired' => 'Expired',
                            ])
                            ->default('draft')
                            ->required()
                            ->columnSpan(1),

                        Select::make('template_id')
                            ->relationship('template', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),

                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => auth()->id())
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make('Contract Details')
                    ->schema([
                        RichEditor::make('content')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                                'undo',
                                'redo',
                            ]),

                        TextInput::make('contract_value')
                            ->numeric()
                            ->prefix('$')
                            ->maxValue(999999999.99)
                            ->columnSpan(1),

                        DatePicker::make('effective_date')
                            ->columnSpan(1),

                        DatePicker::make('expiration_date')
                            ->columnSpan(1),

                        DatePicker::make('signed_at')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make('Metadata')
                    ->schema([
                        Placeholder::make('created_at')
                            ->label('Created At')
                            ->content(fn ($record) => $record?->created_at?->diffForHumans() ?? '-'),

                        Placeholder::make('updated_at')
                            ->label('Updated At')
                            ->content(fn ($record) => $record?->updated_at?->diffForHumans() ?? '-'),

                        Placeholder::make('signatures_count')
                            ->label('Signatures')
                            ->content(fn ($record) => $record ? ($record->signatures()->where('status', 'signed')->count() . ' / ' . $record->signatures()->count()) : '-'),
                    ])
                    ->columns(3)
                    ->hidden(fn ($record) => $record === null),
            ]);
    }
}
