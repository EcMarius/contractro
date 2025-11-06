<?php

namespace App\Filament\Resources;

use App\Models\Integration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IntegrationResource extends Resource
{
    protected static ?string $model = Integration::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Integrări';

    protected static ?string $navigationGroup = 'Setări';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalii Integrare')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label('Companie')
                            ->relationship('company', 'name')
                            ->required(),

                        Forms\Components\Select::make('type')
                            ->label('Tip')
                            ->options([
                                Integration::TYPE_ANAF => 'ANAF e-Factura',
                                Integration::TYPE_SMS => 'SMS',
                                Integration::TYPE_EMAIL => 'Email',
                                Integration::TYPE_STORAGE => 'Storage',
                                Integration::TYPE_PAYMENT => 'Plăți',
                                Integration::TYPE_ACCOUNTING => 'Contabilitate',
                                Integration::TYPE_CRM => 'CRM',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\Select::make('provider')
                            ->label('Furnizor')
                            ->options(fn ($get) => match($get('type')) {
                                Integration::TYPE_ANAF => ['anaf_efactura' => 'ANAF e-Factura'],
                                Integration::TYPE_SMS => [
                                    'twilio' => 'Twilio',
                                    'clicksend' => 'ClickSend',
                                    'smslink' => 'SMS Link Romania',
                                ],
                                Integration::TYPE_EMAIL => [
                                    'mailgun' => 'Mailgun',
                                    'sendgrid' => 'SendGrid',
                                ],
                                Integration::TYPE_STORAGE => ['aws_s3' => 'AWS S3'],
                                Integration::TYPE_PAYMENT => ['stripe' => 'Stripe'],
                                default => [],
                            })
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->label('Nume')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Descriere')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Configurare')
                    ->schema([
                        Forms\Components\KeyValue::make('config')
                            ->label('Setări')
                            ->keyLabel('Cheie')
                            ->valueLabel('Valoare')
                            ->reorderable(false)
                            ->addable(true)
                            ->deletable(true)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Activ')
                            ->default(false),

                        Forms\Components\Toggle::make('is_test_mode')
                            ->label('Mod Test')
                            ->default(true)
                            ->helperText('Dezactivați pentru producție'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Companie')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nume')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tip')
                    ->colors([
                        'success' => Integration::TYPE_ANAF,
                        'primary' => Integration::TYPE_SMS,
                        'warning' => Integration::TYPE_EMAIL,
                        'secondary' => Integration::TYPE_STORAGE,
                    ]),

                Tables\Columns\TextColumn::make('provider')
                    ->label('Furnizor'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activ')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_test_mode')
                    ->label('Test')
                    ->boolean(),

                Tables\Columns\TextColumn::make('last_sync_at')
                    ->label('Ultima Sincronizare')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sync_count')
                    ->label('Sincronizări')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tip')
                    ->options([
                        Integration::TYPE_ANAF => 'ANAF',
                        Integration::TYPE_SMS => 'SMS',
                        Integration::TYPE_EMAIL => 'Email',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activ'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('test')
                    ->label('Testează')
                    ->icon('heroicon-o-beaker')
                    ->action(function (Integration $record) {
                        // Test integration
                        \Filament\Notifications\Notification::make()
                            ->title('Test încărcat')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\IntegrationResource\Pages\ListIntegrations::route('/'),
            'create' => \App\Filament\Resources\IntegrationResource\Pages\CreateIntegration::route('/create'),
            'edit' => \App\Filament\Resources\IntegrationResource\Pages\EditIntegration::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
}
