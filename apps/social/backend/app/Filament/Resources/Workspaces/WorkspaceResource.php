<?php

namespace App\Filament\Resources\Workspaces;

use App\Filament\Resources\Workspaces\Pages\ManageWorkspaces;
use App\Models\Workspace;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkspaceResource extends Resource
{
    protected static ?string $model = Workspace::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Select::make('owner_id')
                    ->relationship('owner', 'name')
                    ->required(),
                TextInput::make('timezone')
                    ->required()
                    ->default('Europe/Istanbul'),
                TextInput::make('locale')
                    ->required()
                    ->default('tr'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                DateTimePicker::make('trial_ends_at'),
                DateTimePicker::make('read_only_at'),
                DateTimePicker::make('trial_lapsed_at'),
                DateTimePicker::make('deletion_scheduled_at'),
                Toggle::make('require_owner_2fa')
                    ->required(),
                Toggle::make('approval_required')
                    ->required(),
                Toggle::make('ads_auto_apply')
                    ->required(),
                Toggle::make('ads_kill_switch')
                    ->required(),
                TextInput::make('ads_daily_cap_minor')
                    ->required()
                    ->numeric()
                    ->default(50000),
                TextInput::make('ads_monthly_cap_minor')
                    ->required()
                    ->numeric()
                    ->default(1000000),
                TextInput::make('ads_max_increase_percent')
                    ->required()
                    ->numeric()
                    ->default(20),
                TextInput::make('ads_cooldown_hours')
                    ->required()
                    ->numeric()
                    ->default(24),
                Toggle::make('system_user_enabled')
                    ->required(),
                Textarea::make('settings')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('owner.name')
                    ->searchable(),
                TextColumn::make('timezone')
                    ->searchable(),
                TextColumn::make('locale')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('trial_ends_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('read_only_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('trial_lapsed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('deletion_scheduled_at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('require_owner_2fa')
                    ->boolean(),
                IconColumn::make('approval_required')
                    ->boolean(),
                IconColumn::make('ads_auto_apply')
                    ->boolean(),
                IconColumn::make('ads_kill_switch')
                    ->boolean(),
                TextColumn::make('ads_daily_cap_minor')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ads_monthly_cap_minor')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ads_max_increase_percent')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ads_cooldown_hours')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('system_user_enabled')
                    ->boolean(),
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
            'index' => ManageWorkspaces::route('/'),
        ];
    }
}
