<?php

namespace App\Filament\Resources\ContractTemplates\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;

class ContractTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->maxLength(65535)
                            ->rows(3)
                            ->columnSpanFull(),

                        TextInput::make('category')
                            ->maxLength(100)
                            ->placeholder('e.g., Legal, Service, Employment')
                            ->columnSpan(1),

                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->id())
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make('Template Content')
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
                    ]),

                Section::make('Visibility Settings')
                    ->schema([
                        Toggle::make('is_public')
                            ->label('Public Template')
                            ->helperText('Public templates are visible to all users')
                            ->columnSpan(1),

                        Toggle::make('is_system')
                            ->label('System Template')
                            ->helperText('System templates cannot be deleted')
                            ->columnSpan(1)
                            ->disabled(fn ($record) => $record && $record->is_system),

                        Placeholder::make('usage_count')
                            ->label('Times Used')
                            ->content(fn ($record) => $record?->usage_count ?? 0)
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->hidden(fn ($record) => $record === null),

                Section::make('Metadata')
                    ->schema([
                        Placeholder::make('created_at')
                            ->label('Created At')
                            ->content(fn ($record) => $record?->created_at?->diffForHumans() ?? '-'),

                        Placeholder::make('updated_at')
                            ->label('Updated At')
                            ->content(fn ($record) => $record?->updated_at?->diffForHumans() ?? '-'),
                    ])
                    ->columns(2)
                    ->hidden(fn ($record) => $record === null),
            ]);
    }
}
