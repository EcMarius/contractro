<?php

namespace App\Filament\Resources\Licenses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LicensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('license_key')
                    ->label('License Key')
                    ->searchable()
                    ->copyable()
                    ->tooltip('Click to copy')
                    ->icon('heroicon-o-clipboard')
                    ->iconPosition('after'),

                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => route('filament.admin.resources.users.view', $record->user_id))
                    ->color('primary'),

                TextColumn::make('domain')
                    ->label('Domain')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-globe-alt')
                    ->iconPosition('before'),

                TextColumn::make('product_name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->default('N/A'),

                BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'info' => 'trial',
                        'success' => 'monthly',
                        'primary' => 'yearly',
                        'warning' => 'lifetime',
                    ])
                    ->icons([
                        'heroicon-o-beaker' => 'trial',
                        'heroicon-o-calendar' => 'monthly',
                        'heroicon-o-calendar-days' => 'yearly',
                        'heroicon-o-infinity' => 'lifetime',
                    ])
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'suspended',
                        'danger' => 'expired',
                        'secondary' => 'cancelled',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'active',
                        'heroicon-o-pause-circle' => 'suspended',
                        'heroicon-o-x-circle' => 'expired',
                        'heroicon-o-no-symbol' => 'cancelled',
                    ])
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->default('Never')
                    ->color(fn ($record) => $record?->is_expiring_soon ? 'warning' : null)
                    ->icon(fn ($record) => $record?->is_expiring_soon ? 'heroicon-o-exclamation-triangle' : null)
                    ->tooltip(fn ($record) => $record?->expires_at ?
                        $record->days_until_expiration . ' days remaining' : null),

                TextColumn::make('check_count')
                    ->label('Checks')
                    ->sortable()
                    ->icon('heroicon-o-shield-check')
                    ->iconPosition('before')
                    ->default('0'),

                TextColumn::make('last_checked_at')
                    ->label('Last Check')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->default('Never')
                    ->description(fn ($record) => $record?->last_checked_at ?
                        $record->last_checked_at->diffForHumans() : null),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default(['active']),

                SelectFilter::make('type')
                    ->label('License Type')
                    ->multiple()
                    ->options([
                        'trial' => 'Trial',
                        'monthly' => 'Monthly',
                        'yearly' => 'Yearly',
                        'lifetime' => 'Lifetime',
                    ]),

                SelectFilter::make('user_id')
                    ->label('Customer')
                    ->relationship('user', 'name')
                    ->searchable(),

                TernaryFilter::make('expiring_soon')
                    ->label('Expiring Soon (30 days)')
                    ->queries(
                        true: fn (Builder $query) => $query->expiringSoon(30),
                        false: fn (Builder $query) => $query->whereDoesntHave('licenses',
                            fn ($q) => $q->expiringSoon(30)),
                    ),

                TernaryFilter::make('expired')
                    ->label('Expired')
                    ->queries(
                        true: fn (Builder $query) => $query->expired(),
                        false: fn (Builder $query) => $query->whereDoesntHave('licenses',
                            fn ($q) => $q->expired()),
                    ),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('renew')
                    ->label('Renew')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->renew())
                    ->visible(fn ($record) => in_array($record->status, ['expired', 'active']))
                    ->successNotificationTitle('License renewed successfully'),

                Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->suspend())
                    ->visible(fn ($record) => $record->status === 'active')
                    ->successNotificationTitle('License suspended'),

                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->activate())
                    ->visible(fn ($record) => $record->status === 'suspended')
                    ->successNotificationTitle('License activated'),

                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->cancel())
                    ->visible(fn ($record) => in_array($record->status, ['active', 'suspended']))
                    ->successNotificationTitle('License cancelled'),

                Action::make('viewLogs')
                    ->label('View Logs')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn ($record) => route('filament.admin.resources.licenses.logs', $record))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    Action::make('bulk_suspend')
                        ->label('Suspend')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->status === 'active') {
                                    $record->suspend();
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Selected licenses suspended'),

                    Action::make('bulk_activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->status === 'suspended') {
                                    $record->activate();
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Selected licenses activated'),

                    ExportBulkAction::make()
                        ->label('Export')
                        ->icon('heroicon-o-arrow-down-tray'),

                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
