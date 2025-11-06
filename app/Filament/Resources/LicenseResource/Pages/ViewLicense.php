<?php

namespace App\Filament\Resources\LicenseResource\Pages;

use App\Filament\Resources\LicenseResource;
use Filament\Actions;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewLicense extends ViewRecord
{
    protected static string $resource = LicenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('viewLogs')
                ->label('View Logs')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(fn ($record) => LicenseResource::getUrl('logs', ['record' => $record])),
            Actions\Action::make('renew')
                ->label('Renew License')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn ($record) => $record->renew())
                ->visible(fn ($record) => in_array($record->status, ['expired', 'active']))
                ->successNotificationTitle('License renewed successfully'),
            Actions\Action::make('suspend')
                ->label('Suspend')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn ($record) => $record->suspend())
                ->visible(fn ($record) => $record->status === 'active')
                ->successNotificationTitle('License suspended'),
            Actions\Action::make('activate')
                ->label('Activate')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn ($record) => $record->activate())
                ->visible(fn ($record) => $record->status === 'suspended')
                ->successNotificationTitle('License activated'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('License Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('license_key')
                                    ->label('License Key')
                                    ->copyable()
                                    ->icon('heroicon-o-key')
                                    ->badge(),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn ($record) => match ($record->status) {
                                        'active' => 'success',
                                        'suspended' => 'warning',
                                        'expired' => 'danger',
                                        'cancelled' => 'secondary',
                                    }),
                                TextEntry::make('user.name')
                                    ->label('Customer')
                                    ->icon('heroicon-o-user')
                                    ->url(fn ($record) => route('filament.admin.resources.users.view', $record->user_id)),
                                TextEntry::make('user.email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable(),
                                TextEntry::make('domain')
                                    ->icon('heroicon-o-globe-alt')
                                    ->copyable(),
                                TextEntry::make('product_name')
                                    ->label('Product')
                                    ->default('N/A'),
                                TextEntry::make('product_version')
                                    ->label('Version')
                                    ->default('N/A'),
                                TextEntry::make('type')
                                    ->badge()
                                    ->color(fn ($record) => match ($record->type) {
                                        'trial' => 'info',
                                        'monthly' => 'success',
                                        'yearly' => 'primary',
                                        'lifetime' => 'warning',
                                    }),
                            ]),
                    ]),

                Section::make('Dates')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('issued_at')
                                    ->dateTime('M j, Y H:i')
                                    ->icon('heroicon-o-calendar'),
                                TextEntry::make('expires_at')
                                    ->dateTime('M j, Y H:i')
                                    ->default('Never (Lifetime)')
                                    ->icon('heroicon-o-calendar')
                                    ->color(fn ($record) => $record?->is_expiring_soon ? 'warning' : null)
                                    ->badge(fn ($record) => $record?->is_expiring_soon)
                                    ->formatStateUsing(fn ($record, $state) =>
                                        $state . ($record?->is_expiring_soon ? ' (Expires Soon!)' : '')
                                    ),
                                TextEntry::make('days_until_expiration')
                                    ->label('Days Remaining')
                                    ->default('Lifetime')
                                    ->suffix(' days')
                                    ->color(fn ($record, $state) => match (true) {
                                        $state === null => null,
                                        $state <= 7 => 'danger',
                                        $state <= 30 => 'warning',
                                        default => 'success',
                                    }),
                                TextEntry::make('created_at')
                                    ->dateTime('M j, Y H:i')
                                    ->icon('heroicon-o-clock'),
                            ]),
                    ]),

                Section::make('Usage Statistics')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('check_count')
                                    ->label('Total Checks')
                                    ->icon('heroicon-o-shield-check')
                                    ->default('0'),
                                TextEntry::make('last_checked_at')
                                    ->dateTime('M j, Y H:i')
                                    ->default('Never')
                                    ->description(fn ($record) => $record?->last_checked_at ?
                                        $record->last_checked_at->diffForHumans() : null),
                                TextEntry::make('ip_address')
                                    ->label('Last IP')
                                    ->icon('heroicon-o-computer-desktop')
                                    ->default('N/A'),
                            ]),
                    ]),

                Section::make('Notes')
                    ->schema([
                        TextEntry::make('notes')
                            ->default('No notes')
                            ->markdown(),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($record) => empty($record->notes)),
            ]);
    }
}
