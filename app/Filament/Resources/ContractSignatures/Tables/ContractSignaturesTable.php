<?php

namespace App\Filament\Resources\ContractSignatures\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ContractSignaturesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('signer_name')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->description(fn ($record) => $record->signer_role),

                TextColumn::make('signer_email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->limit(30)
                    ->toggleable(),

                TextColumn::make('contract.title')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->url(fn ($record) => route('filament.admin.resources.contracts.view', $record->contract_id))
                    ->openUrlInNewTab()
                    ->description(fn ($record) => 'Contract #' . $record->contract->contract_number),

                BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'pending',
                        'success' => 'signed',
                        'danger' => 'declined',
                        'warning' => 'expired',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'signed',
                        'heroicon-o-x-circle' => 'declined',
                        'heroicon-o-exclamation-triangle' => 'expired',
                    ])
                    ->sortable(),

                TextColumn::make('signing_order')
                    ->label('Order')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('signature_type')
                    ->label('Type')
                    ->badge()
                    ->colors([
                        'info' => 'drawn',
                        'success' => 'typed',
                        'warning' => 'uploaded',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? 'N/A'))
                    ->toggleable(),

                TextColumn::make('signed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Not signed yet')
                    ->description(fn ($record) => $record->signed_at ? 'IP: ' . ($record->ip_address ?? 'Unknown') : null),

                TextColumn::make('declined_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Not declined'),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : 'secondary')
                    ->toggleable(),

                TextColumn::make('last_reminder_sent_at')
                    ->label('Last Reminder')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('No reminders sent'),

                TextColumn::make('requested_at')
                    ->label('Requested')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'signed' => 'Signed',
                        'declined' => 'Declined',
                        'expired' => 'Expired',
                    ])
                    ->multiple(),

                SelectFilter::make('contract')
                    ->relationship('contract', 'title')
                    ->searchable()
                    ->preload()
                    ->label('Contract'),

                SelectFilter::make('signature_type')
                    ->options([
                        'drawn' => 'Drawn',
                        'typed' => 'Typed',
                        'uploaded' => 'Uploaded',
                    ])
                    ->label('Signature Type'),

                TernaryFilter::make('is_expired')
                    ->label('Expired Signatures')
                    ->placeholder('All signatures')
                    ->trueLabel('Show expired only')
                    ->falseLabel('Hide expired')
                    ->queries(
                        true: fn ($query) => $query->where('expires_at', '<', now())->where('status', 'pending'),
                        false: fn ($query) => $query->where(function ($q) {
                            $q->where('expires_at', '>=', now())
                              ->orWhere('status', '!=', 'pending');
                        }),
                    ),

                TernaryFilter::make('needs_reminder')
                    ->label('Needs Reminder')
                    ->placeholder('All signatures')
                    ->trueLabel('Show pending reminders')
                    ->falseLabel('Hide pending reminders')
                    ->queries(
                        true: fn ($query) => $query->where('status', 'pending')
                            ->where('created_at', '<=', now()->subDays(3))
                            ->where(function ($q) {
                                $q->whereNull('last_reminder_sent_at')
                                  ->orWhere('last_reminder_sent_at', '<=', now()->subDays(3));
                            }),
                    ),
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
                                // Only allow deletion of pending signatures
                                if ($record->status === 'pending') {
                                    $record->delete();
                                }
                            });
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Delete Signature Requests')
                        ->modalDescription('Only pending signatures will be deleted. Signed or declined signatures cannot be deleted.')
                        ->modalSubmitActionLabel('Delete Pending'),
                ]),
            ])
            ->defaultSort('requested_at', 'desc');
    }
}

