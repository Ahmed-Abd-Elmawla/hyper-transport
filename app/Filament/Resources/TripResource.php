<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripResource\Pages;
use App\Filament\Resources\TripResource\RelationManagers;
use App\Models\Trip;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Trip Information')
                    ->icon('heroicon-o-information-circle')
                    ->iconColor('primary')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('company_id')
                                    ->label('Company')
                                    ->prefixIcon('heroicon-o-building-office-2')
                                    ->relationship('company', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Select a company')
                                    ->live()
                                    ->afterStateUpdated(function (callable $set) {
                                        $set('driver_id', null);
                                        $set('vehicle_id', null);
                                    }),

                                Forms\Components\Select::make('driver_id')
                                    ->label('Driver')
                                    ->prefixIcon('heroicon-o-user-circle')
                                    ->relationship(
                                        'driver',
                                        'name',
                                        fn (Builder $query, callable $get) => $query->where('company_id', $get('company_id'))
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Select a driver')
                                    ->disabled(fn (callable $get) => !$get('company_id')),

                                Forms\Components\Select::make('vehicle_id')
                                    ->label('Vehicle')
                                    ->prefixIcon('heroicon-o-truck')
                                    ->relationship(
                                        'vehicle',
                                        'plate_number',
                                        fn (Builder $query, callable $get) => $query->where('company_id', $get('company_id'))
                                    )
                                    ->getOptionLabelFromRecordUsing(fn (Vehicle $record): string => "{$record->brand} {$record->model} ({$record->plate_number})")
                                    ->searchable(['brand', 'model', 'plate_number'])
                                    ->preload()
                                    ->required()
                                    ->placeholder('Select a vehicle')
                                    ->disabled(fn (callable $get) => !$get('company_id')),

                                Forms\Components\TextInput::make('destination')
                                    ->label('Destination')
                                    ->prefixIcon('heroicon-o-map-pin')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter destination address'),
                            ]),
                    ]),

                Forms\Components\Section::make('Schedule & Status')
                    ->icon('heroicon-o-clock')
                    ->iconColor('primary')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('start_at')
                                    ->label('Start Date & Time')
                                    ->icon('heroicon-o-play-circle')
                                    ->required()
                                    ->placeholder('Select start date and time')
                                    ->native(false)
                                    ->displayFormat('M j, Y g:i A')
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if ($state) {
                                            $set('end_at', null);
                                        }
                                    }),

                                Forms\Components\DateTimePicker::make('end_at')
                                    ->label('End Date & Time')
                                    ->icon('heroicon-o-stop-circle')
                                    ->placeholder('Select end date and time')
                                    ->native(false)
                                    ->displayFormat('M j, Y g:i A')
                                    ->after('start_at')
                                    ->disabled(fn (callable $get) => !$get('start_at')),

                                Forms\Components\Select::make('status')
                                    ->label('Trip Status')
                                    ->prefixIcon('heroicon-o-flag')
                                    ->required()
                                    ->default('scheduled')
                                    ->options([
                                        'scheduled' => 'Scheduled',
                                        'in_progress' => 'In Progress',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                        'delayed' => 'Delayed',
                                    ])
                                    ->placeholder('Select trip status'),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Trip Notes')
                            // ->prefixIcon('heroicon-o-document-text')
                            ->placeholder('Add any additional notes about this trip...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
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
                    ->toggleable(),

                Tables\Columns\TextColumn::make('driver.name')
                    ->label('Driver')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('vehicle_display')
                    ->label('Vehicle')
                    ->getStateUsing(fn (Trip $record): string =>
                        $record->vehicle ? "{$record->vehicle->brand} {$record->vehicle->model} ({$record->vehicle->plate_number})" : 'N/A'
                    )
                    ->searchable(['vehicle.brand', 'vehicle.model', 'vehicle.plate_number'])
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('destination')
                    ->label('Destination')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn (Trip $record): string => $record->destination)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('start_at')
                    ->label('Start Time')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('end_at')
                    ->label('End Time')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->placeholder('Not completed')
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->icon('heroicon-o-flag')
                    ->colors([
                        'warning' => 'scheduled',
                        'primary' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                        'gray' => 'delayed',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('driver_id')
                    ->label('Driver')
                    ->relationship('driver', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'delayed' => 'Delayed',
                    ]),

                Tables\Filters\Filter::make('start_date')
                    ->form([
                        Forms\Components\DatePicker::make('start_from')
                            ->label('Start Date From'),
                        Forms\Components\DatePicker::make('start_until')
                            ->label('Start Date Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_at', '>=', $date),
                            )
                            ->when(
                                $data['start_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_at', '<=', $date),
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
            ->defaultSort('start_at', 'desc');
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
            'index' => Pages\ListTrips::route('/'),
            'create' => Pages\CreateTrip::route('/create'),
            'edit' => Pages\EditTrip::route('/{record}/edit'),
        ];
    }
}
