<?php

namespace App\Filament\Resources\Subscriptions;

use App\Filament\Resources\Subscriptions\Pages\ManageSubscriptions;
use App\Models\Subscription;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('workspace_id')
                    ->relationship('workspace', 'name')
                    ->required(),
                Select::make('plan_id')
                    ->relationship('plan', 'name')
                    ->required(),
                TextInput::make('status')
                    ->required(),
                TextInput::make('period')
                    ->required()
                    ->default('monthly'),
                DateTimePicker::make('trial_ends_at'),
                DateTimePicker::make('current_period_start'),
                DateTimePicker::make('current_period_end'),
                Select::make('pending_plan_id')
                    ->relationship('pendingPlan', 'name'),
                TextInput::make('pending_period'),
                Toggle::make('cancel_at_period_end')
                    ->required(),
                TextInput::make('gateway'),
                TextInput::make('gateway_subscription_id'),
                TextInput::make('dunning_attempts')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('dunning_started_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('workspace.name')
                    ->searchable(),
                TextColumn::make('plan.name')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('period')
                    ->searchable(),
                TextColumn::make('trial_ends_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('current_period_start')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('current_period_end')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('pendingPlan.name')
                    ->searchable(),
                TextColumn::make('pending_period')
                    ->searchable(),
                IconColumn::make('cancel_at_period_end')
                    ->boolean(),
                TextColumn::make('gateway')
                    ->searchable(),
                TextColumn::make('gateway_subscription_id')
                    ->searchable(),
                TextColumn::make('dunning_attempts')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('dunning_started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSubscriptions::route('/'),
        ];
    }
}
