<?php

namespace App\Filament\Resources\ContractSignatures;

use App\Filament\Resources\ContractSignatures\Pages\CreateContractSignature;
use App\Filament\Resources\ContractSignatures\Pages\EditContractSignature;
use App\Filament\Resources\ContractSignatures\Pages\ListContractSignatures;
use App\Filament\Resources\ContractSignatures\Pages\ViewContractSignature;
use App\Filament\Resources\ContractSignatures\Schemas\ContractSignatureForm;
use App\Filament\Resources\ContractSignatures\Tables\ContractSignaturesTable;
use App\Models\ContractSignature;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContractSignatureResource extends Resource
{
    protected static ?string $model = ContractSignature::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    public static function form(Schema $schema): Schema
    {
        return ContractSignatureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContractSignaturesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContractSignatures::route('/'),
            'create' => CreateContractSignature::route('/create'),
            'view' => ViewContractSignature::route('/{record}'),
            'edit' => EditContractSignature::route('/{record}/edit'),
        ];
    }
}
