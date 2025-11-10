<?php

namespace App\Filament\Resources\LicenseResource\Pages;

use App\Filament\Resources\LicenseResource;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LicenseLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = LicenseResource::class;

    protected string $view = 'filament.resources.license-resource.pages.license-logs';

    public $record;

    public function mount($record): void
    {
        $this->record = LicenseResource::getModel()::findOrFail($record);
    }

    public function getTitle(): string
    {
        return "License Check Logs - {$this->record->license_key}";
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->record->checkLogs()->getQuery()
            )
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('domain')
                    ->label('Domain Checked')
                    ->searchable()
                    ->icon('heroicon-o-globe-alt'),

                BadgeColumn::make('is_valid')
                    ->label('Result')
                    ->formatStateUsing(fn ($state) => $state ? 'Valid' : 'Invalid')
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => true,
                        'heroicon-o-x-circle' => false,
                    ]),

                TextColumn::make('check_type')
                    ->label('Check Type')
                    ->badge()
                    ->colors([
                        'primary' => 'api',
                        'info' => 'manual',
                        'secondary' => 'webhook',
                    ]),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->icon('heroicon-o-computer-desktop')
                    ->default('N/A'),

                TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->user_agent)
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('response_data.message')
                    ->label('Message')
                    ->default('N/A')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->response_data['message'] ?? null)
                    ->toggleable(),

                TextColumn::make('checked_at')
                    ->dateTime('M j, Y H:i:s')
                    ->sortable()
                    ->description(fn ($record) => $record->checked_at->diffForHumans()),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                //
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('checked_at', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Back to License')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => LicenseResource::getUrl('view', ['record' => $this->record])),
        ];
    }
}
