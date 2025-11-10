<?php

namespace App\Filament\Resources\LicenseResource\Pages;

use App\Filament\Resources\LicenseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLicense extends CreateRecord
{
    protected static string $resource = LicenseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'License created successfully';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate license key if not provided
        if (empty($data['license_key'])) {
            $data['license_key'] = \App\Models\License::generateLicenseKey();
        }

        // Set issued_at to now if not provided
        if (empty($data['issued_at'])) {
            $data['issued_at'] = now();
        }

        return $data;
    }
}
