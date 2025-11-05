<?php

namespace App\Filament\Widgets;

use App\Models\Contract;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentContractsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Contract::query()->with(['company', 'contractType'])->latest()->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('Contract #')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->limit(40)
                    ->searchable(),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable(),

                Tables\Columns\TextColumn::make('contractType.name')
                    ->label('Type')
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
                    ->label('Value'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->since()
                    ->label('Created'),
            ])
            ->heading('Recent Contracts');
    }
}
