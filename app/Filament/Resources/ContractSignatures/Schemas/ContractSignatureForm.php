<?php

namespace App\Filament\Resources\ContractSignatures\Schemas;

use Filament\Schemas\Components\DateTimePicker;
use Filament\Schemas\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\ViewField;
use Filament\Schemas\Schema;

class ContractSignatureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Signer Information')
                    ->schema([
                        Select::make('contract_id')
                            ->relationship('contract', 'title')
                            ->required()
                            ->searchable()
                            ->preload(),

                        TextInput::make('signer_name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Full name of the signer'),

                        TextInput::make('signer_email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Email address for notifications'),

                        TextInput::make('signer_role')
                            ->maxLength(255)
                            ->placeholder('e.g., Client, Contractor, Witness')
                            ->helperText('The role of the signer in this contract'),

                        TextInput::make('signing_order')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->helperText('Order in which this signature is required (1 = first)'),
                    ])->columns(2),

                Section::make('Signature Status')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'signed' => 'Signed',
                                'declined' => 'Declined',
                                'expired' => 'Expired',
                            ])
                            ->default('pending')
                            ->required()
                            ->disabled(fn ($record) => $record && $record->status === 'signed'),

                        DateTimePicker::make('requested_at')
                            ->default(now())
                            ->disabled(),

                        DateTimePicker::make('expires_at')
                            ->default(now()->addDays(14))
                            ->minDate(now())
                            ->helperText('Signature requests expire after this date'),

                        DateTimePicker::make('signed_at')
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->signed_at),

                        DateTimePicker::make('declined_at')
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->declined_at),

                        DateTimePicker::make('last_reminder_sent_at')
                            ->disabled()
                            ->label('Last Reminder Sent')
                            ->visible(fn ($record) => $record && $record->last_reminder_sent_at),
                    ])->columns(2),

                Section::make('Signature Details')
                    ->schema([
                        Select::make('signature_type')
                            ->options([
                                'drawn' => 'Drawn',
                                'typed' => 'Typed',
                                'uploaded' => 'Uploaded',
                            ])
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->signature_data),

                        Placeholder::make('signature_preview')
                            ->label('Signature Preview')
                            ->content(function ($record) {
                                if (!$record || !$record->signature_data) {
                                    return 'No signature yet';
                                }

                                if ($record->signature_type === 'typed') {
                                    return '<div style="font-family: \'Brush Script MT\', cursive; font-size: 32px; padding: 10px;">' .
                                           e($record->signature_data) . '</div>';
                                }

                                return '<img src="' . $record->signature_data . '" alt="Signature" style="max-width: 300px; border: 1px solid #ddd; padding: 10px;" />';
                            })
                            ->visible(fn ($record) => $record && $record->signature_data)
                            ->columnSpanFull(),

                        Textarea::make('decline_reason')
                            ->rows(3)
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->decline_reason)
                            ->columnSpanFull(),
                    ])->columns(2)
                    ->visible(fn ($record) => $record && ($record->signature_data || $record->decline_reason)),

                Section::make('Verification & Security')
                    ->schema([
                        TextInput::make('verification_token')
                            ->disabled()
                            ->helperText('Unique token for signature verification'),

                        TextInput::make('ip_address')
                            ->disabled()
                            ->label('IP Address')
                            ->visible(fn ($record) => $record && $record->ip_address),

                        Textarea::make('user_agent')
                            ->rows(2)
                            ->disabled()
                            ->label('Browser/Device')
                            ->visible(fn ($record) => $record && $record->user_agent),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }
}

