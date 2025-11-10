<?php

namespace App\Filament\Resources\ContractSignatures\Pages;

use App\Filament\Resources\ContractSignatures\ContractSignatureResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContractSignature extends ViewRecord
{
    protected static string $resource = ContractSignatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
