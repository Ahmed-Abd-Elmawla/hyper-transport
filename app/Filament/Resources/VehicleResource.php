<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Vehicles';

    protected static ?string $modelLabel = 'Vehicle';

    protected static ?string $pluralModelLabel = 'Vehicles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Vehicle Information')
                    ->icon('heroicon-o-truck')
                    ->iconColor('primary')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('company_id')
                                    ->label('Company')
                                    ->relationship('company', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->prefixIcon('heroicon-o-building-office-2')
                                    ->placeholder('Select company'),
                                Forms\Components\TextInput::make('brand')
                                    ->label('Vehicle Brand')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-tag')
                                    ->placeholder('e.g., Ford, Toyota, Mercedes'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('model')
                                    ->label('Vehicle Model')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-cube')
                                    ->placeholder('e.g., Transit, Hiace, Sprinter'),
                                Forms\Components\TextInput::make('year')
                                    ->label('Manufacturing Year')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1900)
                                    ->maxValue(date('Y') + 1)
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->placeholder('e.g., 2023'),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed()
                    ->columns(2),

                Forms\Components\Section::make('Vehicle Details')
                    ->icon('heroicon-o-identification')
                    ->iconColor('primary')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\ColorPicker::make('color')
                                    ->label('Vehicle Color')
                                    ->required()
                                    ->placeholder('Select vehicle color'),
                                Forms\Components\TextInput::make('plate_number')
                                    ->label('Plate Number')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-rectangle-group')
                                    ->placeholder('e.g., ABC-1234')
                                    ->unique(ignoreRecord: true)
                                    ->rule('regex:/^[A-Z0-9\-\s]+$/i'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('capacity')
                                    ->label('Passenger Capacity')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->prefixIcon('heroicon-o-users')
                                    ->placeholder('Number of passengers')
                                    ->suffix('passengers'),
                                Forms\Components\Select::make('status')
                                    ->label('Vehicle Status')
                                    ->required()
                                    ->options([
                                        'available' => 'Available',
                                        'in_use' => 'In Use',
                                        'maintenance' => 'Under Maintenance',
                                        'out_of_service' => 'Out of Service',
                                    ])
                                    ->default('available')
                                    ->prefixIcon('heroicon-o-clipboard-document-check'),
                            ]),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Status')
                            ->helperText('Toggle to activate/deactivate vehicle')
                            ->default(true)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->persistCollapsed()
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable()
                    ->searchable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('brand')
                    ->label('Brand')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('model')
                    ->label('Model')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->label('Year')
                    ->sortable(),
                Tables\Columns\ColorColumn::make('color')
                    ->label('Color')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plate_number')
                    ->label('Plate #')
                    ->searchable()
                    ->copyable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('capacity')
                    ->label('Capacity')
                    ->numeric()
                    ->sortable()
                    ->suffix(' passengers'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'available',
                        'primary' => 'in_use',
                        'warning' => 'maintenance',
                        'danger' => 'out_of_service',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'available',
                        'heroicon-o-play-circle' => 'in_use',
                        'heroicon-o-wrench-screwdriver' => 'maintenance',
                        'heroicon-o-x-circle' => 'out_of_service',
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('brand')
                    ->options(function () {
                        return Vehicle::distinct('brand')->pluck('brand', 'brand')->toArray();
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'in_use' => 'In Use',
                        'maintenance' => 'Under Maintenance',
                        'out_of_service' => 'Out of Service',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\Filter::make('capacity')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('capacity_from')
                                    ->numeric()
                                    ->placeholder('Min capacity'),
                                Forms\Components\TextInput::make('capacity_to')
                                    ->numeric()
                                    ->placeholder('Max capacity'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['capacity_from'],
                                fn (Builder $query, $capacity): Builder => $query->where('capacity', '>=', $capacity),
                            )
                            ->when(
                                $data['capacity_to'],
                                fn (Builder $query, $capacity): Builder => $query->where('capacity', '<=', $capacity),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-o-trash'),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus-circle'),
            ]);
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
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
