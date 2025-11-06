<?php

namespace App\Filament\Resources\ContractTemplates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ContractTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('category')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('description')
                    ->searchable()
                    ->limit(60)
                    ->toggleable(),

                IconColumn::make('is_public')
                    ->label('Public')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_system')
                    ->label('System')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('usage_count')
                    ->label('Uses')
                    ->numeric()
                    ->sortable()
                    ->suffix(' times'),

                TextColumn::make('user.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(function () {
                        return \App\Models\ContractTemplate::distinct()
                            ->pluck('category', 'category')
                            ->filter()
                            ->toArray();
                    })
                    ->searchable(),

                TernaryFilter::make('is_public')
                    ->label('Public Templates')
                    ->placeholder('All templates')
                    ->trueLabel('Public only')
                    ->falseLabel('Private only'),

                TernaryFilter::make('is_system')
                    ->label('System Templates')
                    ->placeholder('All templates')
                    ->trueLabel('System only')
                    ->falseLabel('User-created only'),

                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Created By'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if (!$record->is_system) {
                                    $record->delete();
                                }
                            });
                        }),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('usage_count', 'desc');
    }
}
