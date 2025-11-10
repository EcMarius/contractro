<?php

namespace App\Filament\Resources\ContractSignatures\Pages;

use App\Filament\Resources\ContractSignatures\ContractSignatureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContractSignatures extends ListRecords
{
    protected static string $resource = ContractSignatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
