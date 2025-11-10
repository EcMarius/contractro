<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Licenses\Schemas\LicenseForm;
use App\Filament\Resources\Licenses\Tables\LicensesTable;
use App\Filament\Resources\LicenseResource\Pages;
use App\Models\License;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LicenseResource extends Resource
{
    protected static ?string $model = License::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Licenses';

    protected static ?int $navigationSort = 15;

    public static function getNavigationGroup(): ?string
    {
        return 'License Management';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return LicenseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LicensesTable::configure($table);
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
            'index' => Pages\ListLicenses::route('/'),
            'create' => Pages\CreateLicense::route('/create'),
            'view' => Pages\ViewLicense::route('/{record}'),
            'edit' => Pages\EditLicense::route('/{record}/edit'),
            'logs' => Pages\LicenseLogs::route('/{record}/logs'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Only admins can view licenses
        return auth()->user()?->hasRole('admin') ?? false;
    }
}
