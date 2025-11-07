<?php

namespace App\Filament\Resources\LicenseResource\Pages;

use App\Filament\Resources\LicenseResource;
use Filament\Actions;
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
}
