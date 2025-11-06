<?php

namespace App\Filament\Resources\ContractSignatures\Pages;

use App\Filament\Resources\ContractSignatures\ContractSignatureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContractSignature extends EditRecord
{
    protected static string $resource = ContractSignatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
